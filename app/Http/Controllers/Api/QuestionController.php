<?php

namespace App\Http\Controllers\Api;

use App\Models\Option;
use App\Models\Question;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class QuestionController extends Controller
{
    public function index()
    {
        $questions = Question::with([
            'stimulus',
            'stimulus.test',
            'options'
        ])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data question berhasil diambil',
            'data' => $questions
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'test_id' => 'required|exists:tests,id',
            'stimulus_id' => 'nullable|exists:stimuli,id',
            'question_text' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'type' => 'required|in:PG,ES,IP,PW',
            'duration' => 'nullable|integer',
            'point' => 'nullable|integer',
            'order' => 'nullable|integer',

            'options' => 'nullable|array',
            'options.*.label' => 'required|string|max:5',
            'options.*.option_text' => 'required|string',
            'options.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'options.*.is_correct' => 'required|boolean',
            'options.*.point' => 'nullable|integer',
        ]);

        if ($request->hasFile('image')) {

            $file = $request->file('image');

            $filename = time() . '_' . $file->getClientOriginalName();

            $path = $file->storeAs(
                'questions',
                $filename,
                'public'
            );

            $validated['image'] = $path;
        }

        $question = Question::create([
            'test_id' => $validated['test_id'],
            'stimulus_id' => $validated['stimulus_id'] ?? null,
            'question_text' => $validated['question_text'],
            'image' => $validated['image'] ?? null,
            'type' => $validated['type'],
            'duration' => $validated['duration'] ?? null,
            'point' => $validated['point'] ?? 0,
            'order' => $validated['order'] ?? 0,
        ]);

        if (!empty($validated['options'])) {

            foreach ($validated['options'] as $index => $item) {

                $question->options()->create([
                'label' => $item['label'],
                'option_text' => $item['option_text'],
                'image' => $this->storeOptionImage($request, $index),
                'is_correct' => $item['is_correct'],
                'point' => $item['point'] ?? 0,
            ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Question berhasil dibuat',
            'data' => $question->load([
                'stimulus',
                'stimulus.test',
                'options'
            ])
        ], 201);
    }

    public function show(string $id)
    {
        $question = Question::with([
            'stimulus',
            'stimulus.test',
            'options'
        ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail question berhasil diambil',
            'data' => $question
        ]);
    }

    public function update(Request $request, string $id)
    {
        $question = Question::findOrFail($id);

        $validated = $request->validate([
            'test_id' => 'required|exists:tests,id',
            'stimulus_id' => 'nullable|exists:stimuli,id',
            'question_text' => 'required|string',

            // nullable supaya gambar lama tetap aman saat tidak upload file baru
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'delete_image' => 'nullable|boolean',

            'type' => 'required|in:PG,ES,IP,PW',
            'duration' => 'nullable|integer',
            'point' => 'nullable|integer',
            'order' => 'nullable|integer',

            'options' => 'nullable|array',
            'options.*.id' => 'nullable|exists:options,id',
            'options.*.label' => 'required|string|max:5',
            'options.*.option_text' => 'required|string',
            'options.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'options.*.delete_image' => 'nullable|boolean',
            'options.*.is_correct' => 'required|boolean',
            'options.*.point' => 'nullable|integer',
        ]);

        /**
         * IMAGE HANDLER
         *
         * CASE:
         * 1. Upload image baru -> replace
         * 2. delete_image true -> delete image
         * 3. image tidak dikirim -> keep image lama
         */

        // default keep image lama
        $validated['image'] = $question->image;

        // upload image baru
        if ($request->hasFile('image')) {

            // hapus file lama
            if (
                $question->image &&
                Storage::disk('public')->exists($question->image)
            ) {
                Storage::disk('public')->delete($question->image);
            }

            $file = $request->file('image');

            $filename = time() . '_' . $file->getClientOriginalName();

            $path = $file->storeAs(
                'questions',
                $filename,
                'public'
            );

            $validated['image'] = $path;
        }

        // delete image
        elseif ($request->boolean('delete_image')) {

            if (
                $question->image &&
                Storage::disk('public')->exists($question->image)
            ) {
                Storage::disk('public')->delete($question->image);
            }

            $validated['image'] = null;
        }

        $question->update([
            'test_id' => $validated['test_id'],
            'stimulus_id' => $validated['stimulus_id'] ?? null,
            'question_text' => $validated['question_text'],
            'image' => $validated['image'],
            'type' => $validated['type'],
            'duration' => $validated['duration'] ?? null,
            'point' => $validated['point'] ?? 0,
            'order' => $validated['order'] ?? 0,
        ]);

        // update options
        if (!empty($validated['options'])) {
            $existingOptions = $question->options()->get();
            $existingOptionsById = $existingOptions->keyBy('id');
            $existingOptionsByIndex = $existingOptions->values();
            $newOptions = [];
            $keptImages = [];

            foreach ($validated['options'] as $index => $item) {
                $existingOption = null;

                if (!empty($item['id'])) {
                    $existingOption = $existingOptionsById->get($item['id']);
                }

                if (!$existingOption) {
                    $existingOption = $existingOptionsByIndex->get($index);
                }

                $image = $existingOption?->image;

                if ($request->hasFile("options.$index.image")) {
                    if (
                        $existingOption?->image &&
                        Storage::disk('public')->exists($existingOption->image)
                    ) {
                        Storage::disk('public')->delete($existingOption->image);
                    }

                    $image = $this->storeOptionImage($request, $index);
                } elseif (
                    $request->boolean("options.$index.delete_image")
                ) {
                    if (
                        $existingOption?->image &&
                        Storage::disk('public')->exists($existingOption->image)
                    ) {
                        Storage::disk('public')->delete($existingOption->image);
                    }

                    $image = null;
                }

                if ($image) {
                    $keptImages[] = $image;
                }

                $newOptions[] = [
                    'label' => $item['label'],
                    'option_text' => $item['option_text'],
                    'image' => $image,
                    'is_correct' => $item['is_correct'],
                    'point' => $item['point'] ?? 0,
                ];
            }

            foreach ($existingOptions as $option) {
                if (
                    $option->image &&
                    !in_array($option->image, $keptImages, true) &&
                    Storage::disk('public')->exists($option->image)
                ) {
                    Storage::disk('public')->delete($option->image);
                }
            }

            $question->options()->delete();

            foreach ($newOptions as $item) {
                $question->options()->create($item);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Question berhasil diupdate',
            'data' => $question->load([
                'stimulus',
                'stimulus.test',
                'options'
            ])
        ]);
    }

    public function destroy(string $id)
    {
        $question = Question::findOrFail($id);

        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Question berhasil dihapus'
        ]);
    }

    private function storeOptionImage(Request $request, $index)
    {
        if (!$request->hasFile("options.$index.image")) {
            return null;
        }

        $file = $request->file("options.$index.image");

        $filename = time() . '_' . $file->getClientOriginalName();

        return $file->storeAs(
            'options',
            $filename,
            'public'
        );
    }
}
