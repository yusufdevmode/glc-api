<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\UserAnswer;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class UserAnswerController extends Controller
{
    public function index()
    {
        $data = UserAnswer::with([
            'testAttempt',
            'question',
            'option'
        ])->latest()->get();

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'test_attempt_id' => 'required|exists:test_attempts,id',
            'question_id'     => 'required|exists:questions,id',
            'option_id'       => 'nullable|exists:options,id',
            'option_ids'      => 'nullable|array',
            'option_ids.*'    => 'exists:options,id',
            'essay_answer'    => 'nullable|string',
        ]);

        $data = $this->syncAnswers($validated);

        return response()->json([
            'message' => 'Answer saved successfully',
            'data'    => $data
        ]);
    }

    public function byTestAttempt(Request $request, int $testAttemptId)
    {
        $user = $request->user();

        $data = UserAnswer::with([
            'question',
            'option'
        ])
        ->whereHas('testAttempt', function ($q) use ($user, $testAttemptId) {
            $q->where('id', $testAttemptId)
              ->where('user_id', $user->id);
        })
        ->get();

        return response()->json([
            'success' => true,
            'message' => 'Jawaban berhasil diambil',
            'data' => $data
        ]);
    }

    public function show(string $id)
    {
        $data = UserAnswer::with([
            'testAttempt',
            'question',
            'option'
        ])->findOrFail($id);

        return response()->json($data);
    }

    public function update(Request $request, string $id)
    {
        $data = UserAnswer::findOrFail($id);

        $validated = $request->validate([
            'test_attempt_id' => 'required|exists:test_attempts,id',
            'question_id' => 'required|exists:questions,id',
            'option_id' => 'nullable|exists:options,id',
            'option_ids' => 'nullable|array',
            'option_ids.*' => 'exists:options,id',
            'essay_answer' => 'nullable|string',
        ]);

        $data = $this->syncAnswers($validated);

        return response()->json([
            'message' => 'Answer updated',
            'data' => $data
        ]);
    }

    public function destroy(string $id)
    {
        $data = UserAnswer::findOrFail($id);

        $data->delete();

        return response()->json([
            'message' => 'Answer deleted'
        ]);
    }

    private function syncAnswers(array $validated)
    {
        return DB::transaction(function () use ($validated) {
            $optionIds = collect($validated['option_ids'] ?? [])
                ->when(!empty($validated['option_id']), function ($optionIds) use ($validated) {
                    return $optionIds->push($validated['option_id']);
                })
                ->filter()
                ->unique()
                ->values();

            UserAnswer::where('test_attempt_id', $validated['test_attempt_id'])
                ->where('question_id', $validated['question_id'])
                ->delete();

            if ($optionIds->isEmpty()) {
                return UserAnswer::create([
                    'test_attempt_id' => $validated['test_attempt_id'],
                    'question_id' => $validated['question_id'],
                    'option_id' => null,
                    'essay_answer' => $validated['essay_answer'] ?? null,
                ]);
            }

            return $optionIds->map(function ($optionId) use ($validated) {
                return UserAnswer::create([
                    'test_attempt_id' => $validated['test_attempt_id'],
                    'question_id' => $validated['question_id'],
                    'option_id' => $optionId,
                    'essay_answer' => $validated['essay_answer'] ?? null,
                ]);
            });
        });
    }
}
