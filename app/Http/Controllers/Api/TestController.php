<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\stimulus;
use App\Models\Test;
use App\Services\ImageQuestionGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class TestController extends Controller
{
    public function index()
    {
        $tests = Test::with([
            'packages',
            'stimulus',
        ])
            ->withCount('questions')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data test berhasil diambil',
            'data' => $tests,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',

            'group' => 'nullable|string|max:255',

            'category' => 'required|in:P,A',

            'type' => 'required|in:PG,ES,IP,PW',

            'duration' => 'required|integer',

            'passing_grade' => 'nullable|integer|min:0',

            'weight' => 'nullable|integer|min:0|max:100',

            'description' => 'nullable|string',

            'order' => 'nullable|integer',
        ]);

        $test = Test::create([
            'name' => $validated['name'],
            'group' => $validated['group'] ?? null,
            'category' => $validated['category'],
            'type' => $validated['type'],
            'duration' => $validated['duration'],
            'passing_grade' => $validated['passing_grade'] ?? 0,
            'weight' => $validated['weight'] ?? null,
            'description' => $validated['description'] ?? null,
            'order' => $validated['order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test berhasil dibuat',
            'data' => $test,
        ], 201);
    }

    public function show(string $id)
    {
        $test = Test::with([
            'packages',
            'stimulus',
            'questions.stimulus',
            'questions.options',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail test berhasil diambil',
            'data' => $test,
        ]);
    }

    public function generateImageQuestions(
        Request $request,
        Test $test,
        ImageQuestionGenerator $generator
    ) {
        $validated = $request->validate([
            'stimulus_id' => [
                'required',
                'integer',
                Rule::exists('stimuli', 'id')
                    ->where('test_id', $test->id),
            ],
            'images' => 'required|array|size:5',
            'images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            'question_count' => 'required|integer|min:1|max:1000',
            'question_text' => 'required|string|max:1000',
            'duration' => 'nullable|integer|min:1',
            'allow_duplicates' => 'nullable|boolean',
        ]);

        $allowDuplicates = $request->boolean('allow_duplicates');

        if (
            ! $allowDuplicates &&
            $validated['question_count'] > ImageQuestionGenerator::MAX_UNIQUE_QUESTIONS
        ) {
            throw ValidationException::withMessages([
                'question_count' => sprintf(
                    'Maksimal %d soal unik dari lima gambar. Aktifkan allow_duplicates untuk membuat lebih banyak soal.',
                    ImageQuestionGenerator::MAX_UNIQUE_QUESTIONS
                ),
            ]);
        }

        $stimulus = stimulus::findOrFail($validated['stimulus_id']);

        try {
            $result = $generator->generate(
                $test,
                $stimulus,
                $validated['images'],
                $validated['question_count'],
                $validated['question_text'],
                $validated['duration'] ?? null,
                $allowDuplicates
            );
        } catch (Throwable $exception) {
            Log::error('Gagal generate soal gambar', [
                'test_id' => $test->id,
                'stimulus_id' => $stimulus->id,
                'question_count' => $validated['question_count'],
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Soal gagal dibuat. Seluruh soal dan file dari proses ini sudah dibatalkan.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => sprintf(
                '%d soal gambar berhasil dibuat',
                $result['questions_created']
            ),
            'data' => $result,
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $test = Test::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',

            'group' => 'nullable|string|max:255',

            'category' => 'required|in:P,A',

            'type' => 'required|in:PG,ES,IP,PW',

            'duration' => 'required|integer',

            'passing_grade' => 'nullable|integer|min:0',

            'weight' => 'nullable|integer|min:0|max:100',

            'description' => 'nullable|string',

            'order' => 'nullable|integer',
        ]);

        $test->update([
            'name' => $validated['name'],
            'group' => $validated['group'] ?? null,
            'category' => $validated['category'],
            'type' => $validated['type'],
            'duration' => $validated['duration'],
            'passing_grade' => $validated['passing_grade'] ?? 0,
            'weight' => $validated['weight'] ?? null,
            'description' => $validated['description'] ?? null,
            'order' => $validated['order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test berhasil diperbarui',
            'data' => $test->load([
                'packages',
                'stimulus',
            ]),
        ]);
    }

    public function destroy(string $id)
    {
        $test = Test::findOrFail($id);

        $test->delete();

        return response()->json([
            'success' => true,
            'message' => 'Test berhasil dihapus',
            'data' => null,
        ]);
    }
}
