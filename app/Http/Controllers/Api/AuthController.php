<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data) {
            $user = User::where('emp_id', $data['emp_id'])
                ->lockForUpdate()
                ->first();

            if (!$user || !Hash::check($data['password'], $user->password)) {
                return response()->json([
                    'message' => 'Invalid credentials.',
                ], 401);
            }

            if ((int) $user->status !== 1) {
                return response()->json([
                    'message' => 'Your account is inactive.',
                ], 403);
            }

            $token = $user->createToken('warehouse')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
            ], 200);
        });
    }


    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not authenticated',
                    'token' => $request->bearerToken(),
                ], 401);
            }

            $token = $user->currentAccessToken();

            if ($token) {
                $token->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function updateBranch(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        $user = $request->user();
        $user->branch_id = $request->branch_id;
        $user->save();

        return response()->json([
            'message' => 'Branch updated successfully',
            'branch_id' => $user->branch_id,
        ]);
    }
}
