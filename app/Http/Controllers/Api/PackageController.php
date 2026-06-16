<?php

namespace App\Http\Controllers\Api;

use App\Models\Package;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::withCount('tests')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data packages berhasil diambil',
            'data' => $packages
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'code' => 'required',
            'description' => 'nullable',
        ]);

        $package = Package::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Package berhasil dibuat',
            'data' => $package
        ]);
    }

    public function show(string $id)
    {
        $package = Package::with('tests')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail package berhasil diambil',
            'data' => $package
        ]);
    }

    public function update(Request $request, string $id)
    {
        $package = Package::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required',
            'code' => 'required',
            'description' => 'nullable',
        ]);

        $package->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Package berhasil diupdate',
            'data' => $package
        ]);
    }

    public function destroy(string $id)
    {
        $package = Package::findOrFail($id);
        $package->delete();

        return response()->json([
            'success' => true,
            'message' => 'Package berhasil dihapus'
        ]);
    }
}
