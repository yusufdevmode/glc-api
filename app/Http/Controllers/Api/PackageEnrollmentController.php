<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\PackageEnrollment;
use App\Http\Controllers\Controller;

class PackageEnrollmentController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('startdate') && ! $request->has('start_date')) {
            $request->merge(['start_date' => $request->query('startdate')]);
        }

        if ($request->has('enddate') && ! $request->has('end_date')) {
            $request->merge(['end_date' => $request->query('enddate')]);
        }

        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'end_time' => 'nullable|in:all,with,without',
        ]);

        $query = PackageEnrollment::with([
            'user',
            'package'
        ]);

        if ($request->filled('start_date')) {
            $query->whereDate('start_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('start_time', '<=', $request->end_date);
        }

        if ($request->end_time === 'with') {
            $query->whereNotNull('end_time');
        }

        if ($request->end_time === 'without') {
            $query->whereNull('end_time');
        }

        $data = $query->latest()->get();

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
