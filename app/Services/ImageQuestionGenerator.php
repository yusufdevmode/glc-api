<?php

namespace App\Services;

use App\Models\stimulus;
use App\Models\Test;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ImageQuestionGenerator
{
    public const MAX_UNIQUE_QUESTIONS = 120;

    private const LABELS = ['A', 'B', 'C', 'D', 'E'];

    private const CANVAS_WIDTH = 1600;

    private const CANVAS_HEIGHT = 400;

    private const CANVAS_PADDING = 20;

    private const CELL_GAP = 16;

    /**
     * @param  array<int, UploadedFile>  $images
     * @return array<string, int|string|bool>
     */
    public function generate(
        Test $test,
        stimulus $stimulus,
        array $images,
        int $questionCount,
        string $questionText,
        ?int $duration,
        bool $allowDuplicates
    ): array {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('Ekstensi GD diperlukan untuk membuat gambar soal.');
        }

        if (! $allowDuplicates && $questionCount > self::MAX_UNIQUE_QUESTIONS) {
            throw new RuntimeException('Jumlah soal melebihi kombinasi unik yang tersedia.');
        }

        $generationId = (string) Str::uuid();
        $directory = "generated-image-questions/{$test->id}/{$generationId}";
        $disk = Storage::disk('public');

        try {
            $optionImagePaths = $this->storeOptionImages(
                $disk,
                $directory,
                $images
            );
            $variants = $this->makeVariants($questionCount);
            $questionImagePaths = [];

            foreach ($variants as $index => $variant) {
                $questionImagePath = sprintf(
                    '%s/questions/%04d.webp',
                    $directory,
                    $index + 1
                );

                $this->createCompositeImage(
                    $disk,
                    $questionImagePath,
                    $images,
                    $variant['display_order']
                );
                $questionImagePaths[$index] = $questionImagePath;
            }

            $questionsCreated = DB::transaction(function () use (
                $test,
                $stimulus,
                $questionCount,
                $questionText,
                $duration,
                $optionImagePaths,
                $variants,
                $questionImagePaths
            ) {
                $lockedTest = Test::query()
                    ->whereKey($test->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $nextOrder = ((int) $lockedTest->questions()->max('order')) + 1;

                foreach ($variants as $index => $variant) {
                    $question = $lockedTest->questions()->create([
                        'stimulus_id' => $stimulus->id,
                        'question_text' => $questionText,
                        'image' => $questionImagePaths[$index],
                        'type' => 'PG',
                        'duration' => $duration,
                        'point' => 1,
                        'order' => $nextOrder + $index,
                    ]);

                    foreach (self::LABELS as $optionIndex => $label) {
                        $question->options()->create([
                            'label' => $label,
                            'option_text' => "Gambar {$label}",
                            'image' => $optionImagePaths[$optionIndex],
                            'is_correct' => $optionIndex === $variant['missing_index'],
                            'point' => $optionIndex === $variant['missing_index'] ? 1 : 0,
                        ]);
                    }
                }

                return $questionCount;
            });
        } catch (Throwable $exception) {
            // File tidak ikut transaksi database, sehingga seluruh folder batch
            // harus dihapus secara eksplisit ketika proses gagal.
            $disk->deleteDirectory($directory);

            throw $exception;
        }

        return [
            'generation_id' => $generationId,
            'test_id' => $test->id,
            'stimulus_id' => $stimulus->id,
            'questions_created' => $questionsCreated,
            'allow_duplicates' => $allowDuplicates,
        ];
    }

    /**
     * @param  array<int, UploadedFile>  $images
     * @return array<int, string>
     */
    private function storeOptionImages($disk, string $directory, array $images): array
    {
        $paths = [];

        foreach (self::LABELS as $index => $label) {
            $image = $images[$index];
            $extension = strtolower($image->extension() ?: 'png');
            $path = $disk->putFileAs(
                "{$directory}/options",
                $image,
                "{$label}.{$extension}"
            );

            if (! $path) {
                throw new RuntimeException("Gagal menyimpan gambar pilihan {$label}.");
            }

            $paths[$index] = $path;
        }

        return $paths;
    }

    /**
     * @return array<int, array{missing_index: int, display_order: array<int, int>}>
     */
    private function makeVariants(int $questionCount): array
    {
        $permutations = [];
        $positions = array_keys(self::LABELS);

        foreach ($positions as $missingIndex) {
            $visibleIndexes = array_values(array_diff($positions, [$missingIndex]));
            $permutations[$missingIndex] = $this->permutations($visibleIndexes);
            shuffle($permutations[$missingIndex]);
        }

        $cursors = array_fill(0, count(self::LABELS), 0);
        $variants = [];

        while (count($variants) < $questionCount) {
            $missingIndexes = $positions;
            shuffle($missingIndexes);

            foreach ($missingIndexes as $missingIndex) {
                if (count($variants) >= $questionCount) {
                    break;
                }

                if ($cursors[$missingIndex] >= count($permutations[$missingIndex])) {
                    shuffle($permutations[$missingIndex]);
                    $cursors[$missingIndex] = 0;
                }

                $variants[] = [
                    'missing_index' => $missingIndex,
                    'display_order' => $permutations[$missingIndex][$cursors[$missingIndex]],
                ];
                $cursors[$missingIndex]++;
            }
        }

        return $variants;
    }

    /**
     * @param  array<int, int>  $items
     * @return array<int, array<int, int>>
     */
    private function permutations(array $items): array
    {
        if (count($items) <= 1) {
            return [$items];
        }

        $result = [];

        foreach ($items as $index => $item) {
            $remaining = $items;
            array_splice($remaining, $index, 1);

            foreach ($this->permutations(array_values($remaining)) as $permutation) {
                $result[] = array_merge([$item], $permutation);
            }
        }

        return $result;
    }

    /**
     * @param  array<int, UploadedFile>  $images
     * @param  array<int, int>  $displayOrder
     */
    private function createCompositeImage(
        $disk,
        string $path,
        array $images,
        array $displayOrder
    ): void {
        $canvas = imagecreatetruecolor(
            self::CANVAS_WIDTH,
            self::CANVAS_HEIGHT
        );

        if ($canvas === false) {
            throw new RuntimeException('Gagal membuat kanvas gambar soal.');
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        $cellSize = (int) floor(
            min(
                self::CANVAS_HEIGHT - (2 * self::CANVAS_PADDING),
                (
                    self::CANVAS_WIDTH
                    - (2 * self::CANVAS_PADDING)
                    - (3 * self::CELL_GAP)
                ) / 4
            )
        );
        $contentWidth = (4 * $cellSize) + (3 * self::CELL_GAP);
        $startX = intdiv(self::CANVAS_WIDTH - $contentWidth, 2);
        $cellY = intdiv(self::CANVAS_HEIGHT - $cellSize, 2);

        try {
            foreach ($displayOrder as $position => $imageIndex) {
                $sourceContents = file_get_contents($images[$imageIndex]->getRealPath());

                if ($sourceContents === false) {
                    throw new RuntimeException('Gagal membaca salah satu gambar sumber.');
                }

                $source = imagecreatefromstring($sourceContents);

                if ($source === false) {
                    throw new RuntimeException('Format salah satu gambar tidak dapat diproses.');
                }

                try {
                    $sourceWidth = imagesx($source);
                    $sourceHeight = imagesy($source);
                    $scale = min(
                        $cellSize / $sourceWidth,
                        $cellSize / $sourceHeight
                    );
                    $targetWidth = max(1, (int) round($sourceWidth * $scale));
                    $targetHeight = max(1, (int) round($sourceHeight * $scale));
                    $cellX = $startX
                        + ($position * ($cellSize + self::CELL_GAP));
                    $targetX = $cellX + intdiv($cellSize - $targetWidth, 2);
                    $targetY = $cellY + intdiv($cellSize - $targetHeight, 2);

                    imagecopyresampled(
                        $canvas,
                        $source,
                        $targetX,
                        $targetY,
                        0,
                        0,
                        $targetWidth,
                        $targetHeight,
                        $sourceWidth,
                        $sourceHeight
                    );
                } finally {
                    imagedestroy($source);
                }
            }

            ob_start();
            $encoded = imagewebp($canvas, null, 85);
            $contents = ob_get_clean();

            if (! $encoded || $contents === false || $contents === '') {
                throw new RuntimeException('Gagal mengubah kanvas menjadi gambar WebP.');
            }

            if (! $disk->put($path, $contents)) {
                throw new RuntimeException('Gagal menyimpan gambar soal.');
            }
        } finally {
            imagedestroy($canvas);
        }
    }
}
