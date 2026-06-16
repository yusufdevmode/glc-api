<?php

namespace App\Http\Controllers;

use App\Models\stimulus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StimulusController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'test_id' => 'nullable|exists:tests,id',
        ]);

        $stimuli = stimulus::with([
            'test',
            'questions'
        ])
        ->when($validated['test_id'] ?? null, function ($query, $testId) {
            $query->where('test_id', $testId);
        })
        ->latest()
        ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data stimulus berhasil diambil',
            'data' => $stimuli
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'test_id' => 'required|exists:tests,id',
            'stimulus_text' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {

            $file = $request->file('image');

            $filename = time() . '_' . $file->getClientOriginalName();

            $path = $file->storeAs(
                'stimuli',
                $filename,
                'public'
            );

            $validated['image'] = $path;
        }

        $stimulus = stimulus::create([
            'test_id' => $validated['test_id'],
            'stimulus_text' => $validated['stimulus_text'],
            'image' => $validated['image'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stimulus berhasil dibuat',
            'data' => $stimulus
        ], 201);
    }

    public function show(stimulus $stimulus)
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail stimulus berhasil diambil',
            'data' => $stimulus->load([
                'test',
                'questions'
            ])
        ]);
    }

    public function update(Request $request, stimulus $stimulus)
    {
        $validated = $request->validate([
            'test_id' => 'required|exists:tests,id',
            'stimulus_text' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $validated['image'] = $stimulus->image;

        if ($request->hasFile('image')) {

            if (
                $stimulus->image &&
                Storage::disk('public')->exists($stimulus->image)
            ) {
                Storage::disk('public')->delete($stimulus->image);
            }

            $file = $request->file('image');

            $filename = time() . '_' . $file->getClientOriginalName();

            $path = $file->storeAs(
                'stimuli',
                $filename,
                'public'
            );

            $validated['image'] = $path;
        } elseif ($request->has('image') && empty($request->image)) {

            if (
                $stimulus->image &&
                Storage::disk('public')->exists($stimulus->image)
            ) {
                Storage::disk('public')->delete($stimulus->image);
            }

            $validated['image'] = null;
        }

        $stimulus->update([
            'test_id' => $validated['test_id'],
            'stimulus_text' => $validated['stimulus_text'],
            'image' => $validated['image'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stimulus berhasil diperbarui',
            'data' => $stimulus
        ]);
    }

    public function destroy(stimulus $stimulus)
    {
        if (
            $stimulus->image &&
            Storage::disk('public')->exists($stimulus->image)
        ) {
            Storage::disk('public')->delete($stimulus->image);
        }

        $stimulus->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stimulus berhasil dihapus'
        ]);
    }
}
