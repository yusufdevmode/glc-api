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
            'sort_order' => 'nullable'
        ]);

        $package->tests()->sync([
    $validated['test_id'] => [
        'sort_order' => $validated['sort_order']
    ]
], false);

        return response()->json([
            'success' => true,
            'message' => 'Test berhasil ditambahkan ke package'
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
