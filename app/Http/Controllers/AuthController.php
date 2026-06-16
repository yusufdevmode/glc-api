<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'username'              => 'required|string|max:255|unique:users,username',  // diganti
            'password'              => 'required|min:6|confirmed',
            'gender'                => 'nullable|in:male,female',
            'birth_place_date'      => 'nullable|string|max:100',
            'phone'                 => 'nullable|string|max:20',
            'last_education'        => 'nullable|string|max:100',
            'role'                  => 'required|in:admin,superadmin,user'
        ]);

        $validated['password'] = bcrypt($validated['password']);

        // Default values
        $validated['is_active'] = true;

        $user = User::create($validated);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil',
            'data' => [
                'user' => [
                    'id'                => $user->id,
                    'name'              => $user->name,
                    'username'          => $user->username,        // diganti dari email
                    'phone'             => $user->phone,
                    'role'              => $user->role,
                    'gender'            => $user->gender,
                    'birth_place_date'  => $user->birth_place_date,
                    'last_education'    => $user->last_education,
                    'is_active'         => $user->is_active,
                ],
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required',
        ]);

        $user = User::with([
            'packageEnrollments.package.tests'
        ])->where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Incorrect username or password. Please try again!',
                'errors' => [
                    'message' => ['Incorrect username or password. Please try again!']
                ]
            ], 422);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'role' => $user->role,
                    'gender' => $user->gender,
                    'birth_place_date' => $user->birth_place_date,
                    'last_education' => $user->last_education,
                    'phone' => $user->phone,
                    'is_active' => $user->is_active,

                    'package_enrollments' => $user->packageEnrollments
                ],
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }
}
