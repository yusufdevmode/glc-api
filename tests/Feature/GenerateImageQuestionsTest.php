<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\stimulus;
use App\Models\Test as PsychometricTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class GenerateImageQuestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_image_questions_and_options_as_one_batch(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->createUser());

        [$test, $stimulus] = $this->createTestAndStimulus();

        $response = $this->post(
            "/api/tests/{$test->id}/generate-image-questions",
            $this->payload($stimulus, 7)
        );

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.questions_created', 7)
            ->assertJsonPath('data.test_id', $test->id)
            ->assertJsonPath('data.stimulus_id', $stimulus->id);

        $questions = Question::with('options')
            ->where('test_id', $test->id)
            ->orderBy('order')
            ->get();

        $this->assertCount(7, $questions);
        $this->assertSame(range(1, 7), $questions->pluck('order')->all());

        foreach ($questions as $question) {
            $this->assertSame($stimulus->id, $question->stimulus_id);
            $this->assertCount(5, $question->options);
            $this->assertSame(1, $question->options->where('is_correct', true)->count());
            Storage::disk('public')->assertExists($question->image);
            $this->assertSame(
                [1600, 400],
                array_slice(
                    getimagesize(
                        Storage::disk('public')->path($question->image)
                    ),
                    0,
                    2
                )
            );

            foreach ($question->options as $option) {
                Storage::disk('public')->assertExists($option->image);
            }
        }

        $correctAnswerCounts = $questions
            ->flatMap
            ->options
            ->where('is_correct', true)
            ->countBy('label');

        $this->assertLessThanOrEqual(
            1,
            $correctAnswerCounts->max() - $correctAnswerCounts->min()
        );
    }

    public function test_it_rolls_back_database_and_files_when_generation_fails_midway(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->createUser());

        [$test, $stimulus] = $this->createTestAndStimulus();
        $createdQuestions = 0;

        Event::listen(
            'eloquent.created: '.Question::class,
            function () use (&$createdQuestions) {
                $createdQuestions++;

                if ($createdQuestions === 2) {
                    throw new RuntimeException('Simulated failure');
                }
            }
        );

        try {
            $response = $this->post(
                "/api/tests/{$test->id}/generate-image-questions",
                $this->payload($stimulus, 5)
            );
        } finally {
            Event::forget('eloquent.created: '.Question::class);
        }

        $response
            ->assertStatus(500)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('options', 0);
        $this->assertSame(
            [],
            Storage::disk('public')->allFiles(
                "generated-image-questions/{$test->id}"
            )
        );
    }

    public function test_it_rejects_more_than_120_questions_without_duplicates(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->createUser());

        [$test, $stimulus] = $this->createTestAndStimulus();
        $payload = $this->payload($stimulus, 121);
        $payload['allow_duplicates'] = false;

        $this->withHeader('Accept', 'application/json')
            ->post(
                "/api/tests/{$test->id}/generate-image-questions",
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('question_count');

        $this->assertDatabaseCount('questions', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Admin Test',
            'username' => 'admin-test',
            'password' => 'password',
            'role' => 'admin',
        ]);
    }

    /**
     * @return array{PsychometricTest, stimulus}
     */
    private function createTestAndStimulus(): array
    {
        $test = PsychometricTest::create([
            'name' => 'Tes Sikap Kerja',
            'group' => 'Sikap Kerja',
            'category' => 'P',
            'type' => 'PG',
            'duration' => 600,
            'passing_grade' => 0,
            'order' => 1,
        ]);

        $stimulus = stimulus::create([
            'test_id' => $test->id,
            'stimulus_text' => 'Pilih gambar yang hilang.',
        ]);

        return [$test, $stimulus];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(stimulus $stimulus, int $questionCount): array
    {
        return [
            'stimulus_id' => $stimulus->id,
            'images' => [
                UploadedFile::fake()->image('a.png', 100, 100),
                UploadedFile::fake()->image('b.png', 110, 100),
                UploadedFile::fake()->image('c.png', 100, 110),
                UploadedFile::fake()->image('d.png', 120, 100),
                UploadedFile::fake()->image('e.png', 100, 120),
            ],
            'question_count' => $questionCount,
            'question_text' => 'Pilih gambar yang hilang.',
            'duration' => 10,
            'allow_duplicates' => false,
        ];
    }
}
