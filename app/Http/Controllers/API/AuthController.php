<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\UserAccount; // Make sure this path is correct for your Model
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Login does not need type hinting because we find the user manually
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // Fix: Use a closure for orWhere to avoid logic errors
            $user = UserAccount::where(function($query) use ($request) {
                $query->where('username', $request->username)
                      ->orWhere('email', $request->username);
            })->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
            }

            if ($user->is_suspended) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Account is suspended',
                    'suspension_note' => $user->suspension_note,
                    'suspended_until' => $user->suspended_until
                ], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged in',
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Login failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            /** @var \App\Models\UserAccount $user */
            $user = Auth::user();

            if ($user) {
                $user->tokens()->delete(); // Fixed: IDE now knows 'tokens()' exists
                return response()->json(['success' => true, 'message' => 'Successfully logged out'], 200);
            }

            return response()->json(['success' => false, 'message' => 'No authenticated user'], 401);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Logout failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function profile(): JsonResponse
    {
        try {
            /** @var \App\Models\UserAccount $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
            }

            return response()->json(['success' => true, 'user' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch profile', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        try {
            /** @var \App\Models\UserAccount $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
            }

            $updateData = [];
            if ($request->filled('name')) $updateData['name'] = $request->name;
            if ($request->filled('username')) $updateData['username'] = $request->username;
            if ($request->filled('email')) $updateData['email'] = $request->email;
            if ($request->filled('phone_number')) $updateData['phone_number'] = $request->phone_number;

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
                $user->tokens()->delete(); // Fixed
            }

            $user->update($updateData); // Fixed: IDE now knows 'update()' exists

            $token = null;
            if ($request->filled('password')) {
                $token = $user->createToken('auth_token')->plainTextToken;
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $user,
                'token' => $token,
                'token_type' => $token ? 'Bearer' : null
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update profile', 'error' => $e->getMessage()], 500);
        }
    }

    public function changePassword(UpdateProfileRequest $request): JsonResponse
    {
        try {
            /** @var \App\Models\UserAccount $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The provided password does not match our records.',
                    'errors' => ['current_password' => ['Incorrect current password.']]
                ], 422);
            }

            $user->update(['password' => Hash::make($request->password)]); // Fixed
            $user->tokens()->delete(); // Fixed
            $token = $user->createToken('auth_token')->plainTextToken; // Fixed

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully. Please login again.',
                'token' => $token,
                'token_type' => 'Bearer'
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to change password', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteAccount(): JsonResponse
    {
        try {
            /** @var \App\Models\UserAccount $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
            }

            $user->tokens()->delete();
            $user->delete(); // Fixed

            return response()->json(['success' => true, 'message' => 'Account deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete account', 'error' => $e->getMessage()], 500);
        }
    }
}