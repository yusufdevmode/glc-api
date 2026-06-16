<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\PackageEnrollment;
use App\Http\Controllers\Controller;

class PackageEnrollmentController extends Controller
{
    public function index()
    {
        $data = PackageEnrollment::with([
            'user',
            'package'
        ])->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Data enrollment berhasil diambil',
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'package_id' => 'required|exists:packages,id',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date',
            'status' => 'nullable',
        ]);

        $data = PackageEnrollment::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment berhasil dibuat',
            'data' => $data
        ]);
    }

    public function show(string $id)
    {
        $data = PackageEnrollment::with([
            'user',
            'package'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail enrollment berhasil diambil',
            'data' => $data
        ]);
    }

    public function myPackages(Request $request)
    {
        $request->validate([
            'category' => 'nullable|in:A,P'
        ]);

        $user = $request->user();

        $query = PackageEnrollment::with([
            'package.tests' => function ($q) use ($request) {

                if ($request->filled('category')) {
                    $q->where('category', $request->category);
                }

                $q->withCount('questions');
            }
        ])
        ->where('user_id', $user->id);

        if ($request->filled('category')) {

            $query->whereHas('package.tests', function ($q) use ($request) {
                $q->where('category', $request->category);
            });
        }

        $data = $query
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data package saya berhasil diambil',
            'data' => $data
        ]);
    }

    public function update(Request $request, string $id)
    {
        $data = PackageEnrollment::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'package_id' => 'required|exists:packages,id',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date',
            'status' => 'nullable',
        ]);

        $data->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment berhasil diupdate',
            'data' => $data
        ]);
    }

    public function destroy(string $id)
    {
        $data = PackageEnrollment::findOrFail($id);

        $data->delete();

        return response()->json([
            'success' => true,
            'message' => 'Enrollment berhasil dihapus'
        ]);
    }
}
