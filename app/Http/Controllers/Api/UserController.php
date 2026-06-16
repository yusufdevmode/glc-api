<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with([
            'packageEnrollments.package'
        ])
        ->latest()
        ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data user berhasil diambil',
            'data'    => $users
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'username'         => 'required|string|max:255|unique:users,username',
            'password'         => 'nullable|min:6',
            'role'             => 'required|in:superadmin,admin,user',
            'gender'           => 'nullable|in:male,female',
            'nik'              => 'nullable|string|max:50',
            'birth_place_date' => 'nullable|string|max:255',
            'phone'            => 'nullable|string|max:50',
            'last_education'   => 'nullable|string|max:255',
            'is_active'        => 'nullable|boolean',

            'packages' => 'nullable|array',
            'packages.*.package_id' => 'required|exists:packages,id',
            'packages.*.start_time' => 'nullable|date',
            'packages.*.end_time'   => 'nullable|date',
        ]);

        // Set default password jika tidak diisi
        if (empty($validated['password'])) {
            $validated['password'] = Hash::make('password123'); // default password
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user = User::create($validated);

        // Create Package Enrollments
        if ($request->filled('packages')) {
            foreach ($request->packages as $package) {
                $user->packageEnrollments()->create([
                    'package_id' => $package['package_id'],
                    'start_time' => $package['start_time'] ?? null,
                    'end_time'   => $package['end_time'] ?? null,
                    'status'     => 'active',
                ]);
            }
        }

        $user->load(['packageEnrollments.package']);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dibuat',
            'data'    => $user
        ], 201);
    }

    public function show(string $id)
    {
        $user = User::with([
            'packageEnrollments.package'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Data user berhasil diambil',
            'data'    => $user
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'role' => 'required|in:superadmin,admin,user',
            'gender' => 'nullable|in:male,female',
            'nik' => 'nullable|string|max:50',
            'birth_place_date' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'last_education' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'packages' => 'nullable|array',
            'packages.*.package_id' => 'required|exists:packages,id',
            'packages.*.start_time' => 'nullable|date',
            'packages.*.end_time' => 'nullable|date',
        ]);

        if ($request->filled('password')) {

            $validated['password'] = Hash::make(
                $request->password
            );
        }

        $user->update($validated);

        $user->packageEnrollments()->delete();

        if ($request->filled('packages')) {

            foreach ($request->packages as $package) {

                $user->packageEnrollments()->create([
                    'package_id' => $package['package_id'],
                    'start_time' => $package['start_time'] ?? null,
                    'end_time' => $package['end_time'] ?? null,
                    'status' => 'active',
                ]);
            }
        }

        $user->load([
            'packageEnrollments.package'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil diperbarui',
            'data' => $user
        ]);
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus',
            'data'    => null
        ]);
    }
}
