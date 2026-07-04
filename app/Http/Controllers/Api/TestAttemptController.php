<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\TestAttempt;
use App\Http\Controllers\Controller;
use App\Models\UserAnswer;

class TestAttemptController extends Controller
{
    public function index()
    {
        $data = TestAttempt::with([
            'user',
            'package',
            'test' => function ($q) {
                $q->withCount('questions');
            }
        ])
        ->latest()
        ->get();

        return response()->json([
            'success' => true,
            'message' => 'List attempt',
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'test_id' => 'required|exists:tests,id',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date',
            'score' => 'nullable|integer',
            'status' => 'nullable',
        ]);

        $data = TestAttempt::create([
            'user_id' => $user->id,
            'package_id' => $validated['package_id'],
            'test_id' => $validated['test_id'],
            'start_time' => $validated['start_time'] ?? now(),
            'end_time' => $validated['end_time'] ?? null,
            'score' => $validated['score'] ?? 0,
            'status' => $validated['status'] ?? 'in_progress',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test attempt created',
            'data' => $data
        ]);
    }

    public function show(string $id)
    {
        $data = TestAttempt::with([
            'user',
            'package',
            'test',
            'userAnswers'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Test attempt id',
            'data' => $data
        ]);
    }

    public function myAttemptsByPackage(Request $request, string $packageId)
    {
        $user = $request->user();

        $data = TestAttempt::with([
            'package',
            'test' => function ($q) {
                $q->withCount('questions');
            },
        ])
        ->where('user_id', $user->id)
        ->where('package_id', $packageId)
        ->latest()
        ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data test attempt berhasil diambil',
            'data' => $data
        ]);
    }

    public function update(Request $request, string $id)
    {
        $attempt = TestAttempt::with('test.questions.options')->findOrFail($id);

        // Ambil semua jawaban user untuk attempt ini.
        $userAnswers = UserAnswer::where('test_attempt_id', $id)
            ->with([
                'option',
                'question.options',
            ])
            ->get();

        $correctAnswers = 0;
        $rawScore = 0;

        foreach ($userAnswers->groupBy('question_id') as $answers) {
            $question = $answers->first()->question;

            if (!$question) {
                continue;
            }

            $selectedOptionIds = $answers
                ->pluck('option_id')
                ->filter()
                ->unique()
                ->sort()
                ->values();

            if ($selectedOptionIds->isEmpty()) {
                continue;
            }

            $correctOptions = $question->options
                ->where('is_correct', 1);

            $correctOptionIds = $correctOptions
                ->pluck('id')
                ->sort()
                ->values();

            $isCorrect = $correctOptionIds->isNotEmpty()
                && $selectedOptionIds->all() === $correctOptionIds->all();

            if (!$isCorrect) {
                continue;
            }

            $correctAnswers++;

            if ($attempt->test->type == 'IP') {
                $rawScore += $correctOptions->sum(function ($option) {
                    return $option->point ?? 0;
                });
            } else {
                $rawScore++;
            }
        }

        $maxRawScore = $attempt->test->type == 'IP'
            ? $attempt->test->questions->sum(function ($question) {
                return $question->options
                    ->where('is_correct', 1)
                    ->sum(function ($option) {
                        return $option->point ?? 0;
                    });
            })
            : $attempt->test->questions->count();

        $maxScore = $attempt->test->weight ?? 100;
        $score = $maxRawScore > 0
            ? (int) round(($rawScore / $maxRawScore) * $maxScore)
            : 0;

        // Update attempt
        $attempt->update([
            'end_time' => now(),
            'status'   => 'finished',
            'score'    => $score,
        ]);

        return response()->json([
            'message' => 'Ujian berhasil diakhiri',
            'data'    => [
                'attempt' => $attempt,
                'correct' => $correctAnswers,
                'score'   => $score,
            ]
        ]);
    }

    public function destroy(string $id)
    {
        $data = TestAttempt::findOrFail($id);

        $data->delete();

        return response()->json([
            'message' => 'Test attempt deleted'
        ]);
    }
}
