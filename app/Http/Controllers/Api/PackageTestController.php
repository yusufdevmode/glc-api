<?php

namespace App\Http\Controllers\Api;

use App\Models\Package;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PackageTestController extends Controller
{
    public function store(
        Request $request,
        Package $package
    ) {

        $validated = $request->validate([
            'test_id' => 'required|exists:tests,id',
            'sort_order' => 'nullable|integer'
        ]);

        $package->tests()->sync([
            $validated['test_id'] => [
                'sort_order' => $validated['sort_order'] ?? 0
            ]
        ], false);

        return response()->json([
            'success' => true,
            'message' => 'Test berhasil ditambahkan ke package'
        ]);
    }

    public function update(
        Request $request,
        Package $package,
        $testId
    ) {
        $validated = $request->validate([
            'sort_order' => 'required|integer',
        ]);

        if (! $package->tests()->where('tests.id', $testId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Test tidak ditemukan di package'
            ], 404);
        }

        $package->tests()->updateExistingPivot($testId, [
            'sort_order' => $validated['sort_order']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sort order test berhasil diupdate'
        ]);
    }

    public function destroy(
        Package $package,
        $testId
    ) {

        $package->tests()->detach($testId);

        return response()->json([
            'success' => true,
            'message' => 'Test berhasil dihapus dari package'
        ]);
    }
}
