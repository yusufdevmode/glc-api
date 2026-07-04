<?php

namespace App\Http\Controllers\Api;

use App\Models\Test;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TestController extends Controller
{
    public function index()
    {
        $tests = Test::with([
            'packages',
            'stimulus'
        ])
        ->withCount('questions')
        ->orderBy('name')
        ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data test berhasil diambil',
            'data' => $tests
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
            'data' => $test
        ], 201);
    }

    public function show(string $id)
    {
        $test = Test::with([
            'packages',
            'stimulus',
            'questions.stimulus',
            'questions.options'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail test berhasil diambil',
            'data' => $test
        ]);
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
                'stimulus'
            ])
        ]);
    }


    public function destroy(string $id)
    {
        $test = Test::findOrFail($id);

        $test->delete();

        return response()->json([
            'success' => true,
            'message' => 'Test berhasil dihapus',
            'data' => null
        ]);
    }
}
