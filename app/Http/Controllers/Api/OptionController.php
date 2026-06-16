<?php

namespace App\Http\Controllers\Api;

use App\Models\Option;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class OptionController extends Controller
{
    public function index()
    {
        $options = Option::latest()->get();

        return response()->json($options);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question_id' => 'required|exists:questions,id',
            'label' => 'required',
            'option_text' => 'nullable',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_correct' => 'boolean',
            'point' => 'nullable|integer',
        ]);

        if ($request->hasFile('image')) {

            $file = $request->file('image');

            $filename = time() . '_' . $file->getClientOriginalName();

            $path = $file->storeAs(
                'options',
                $filename,
                'public'
            );

            $validated['image'] = $path;
        }

        $option = Option::create([
            'question_id' => $validated['question_id'],
            'label' => $validated['label'],
            'option_text' => $validated['option_text'] ?? null,
            'image' => $validated['image'] ?? null,
            'is_correct' => $validated['is_correct'] ?? false,
            'point' => $validated['point'] ?? 0,
        ]);

        return response()->json([
            'message' => 'Option created',
            'data' => $option
        ]);
    }

    public function show(string $id)
    {
        $option = Option::findOrFail($id);

        return response()->json($option);
    }

    public function update(Request $request, string $id)
    {
        $option = Option::findOrFail($id);

        $validated = $request->validate([
            'question_id' => 'required|exists:questions,id',
            'label' => 'required',
            'option_text' => 'nullable',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_correct' => 'boolean',
            'point' => 'nullable|integer',
        ]);

        $validated['image'] = $option->image;

        if ($request->hasFile('image')) {

            if (
                $option->image &&
                Storage::disk('public')->exists($option->image)
            ) {
                Storage::disk('public')->delete($option->image);
            }

            $file = $request->file('image');

            $filename = time() . '_' . $file->getClientOriginalName();

            $path = $file->storeAs(
                'options',
                $filename,
                'public'
            );

            $validated['image'] = $path;
        } elseif ($request->has('image') && empty($request->image)) {

            if (
                $option->image &&
                Storage::disk('public')->exists($option->image)
            ) {
                Storage::disk('public')->delete($option->image);
            }

            $validated['image'] = null;
        }

        $option->update([
            'question_id' => $validated['question_id'],
            'label' => $validated['label'],
            'option_text' => $validated['option_text'] ?? null,
            'image' => $validated['image'],
            'is_correct' => $validated['is_correct'] ?? false,
            'point' => $validated['point'] ?? 0,
        ]);

        return response()->json([
            'message' => 'Option updated',
            'data' => $option
        ]);
    }

    public function destroy(string $id)
    {
        $option = Option::findOrFail($id);

        if (
            $option->image &&
            Storage::disk('public')->exists($option->image)
        ) {
            Storage::disk('public')->delete($option->image);
        }

        $option->delete();

        return response()->json([
            'message' => 'Option deleted'
        ]);
    }
}
