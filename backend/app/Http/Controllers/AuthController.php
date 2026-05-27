<?php

namespace App\Http\Controllers;

use App\Models\EmployeeAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     * Authenticate an employee and issue a Sanctum token.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $account = EmployeeAccount::where('username', $validated['username'])->first();

        if (! $account || ! Hash::check($validated['password'], $account->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if ($account->status !== 'Active') {
            return response()->json([
                'success' => false,
                'message' => 'Account is inactive. Please contact your administrator.',
            ], 403);
        }

        // Update last login timestamp
        $account->update(['last_login' => now()]);

        // Revoke all existing tokens before issuing a new one (single-session)
        $account->tokens()->delete();

        $token = $account->createToken('api-token', ['*'], now()->addHours(8))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'token'      => $token,
                'token_type' => 'Bearer',
                'expires_in' => 8 * 60 * 60, // seconds
                'employee'   => $account->load('employeeInfo'),
            ],
        ]);
    }

    /**
     * POST /api/auth/logout
     * Revoke the current token.
     */
    public function logout(Request $request)
    {
        // Delete only the current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * GET /api/auth/me
     * Return the currently authenticated employee.
     */
    public function me(Request $request)
    {
        $account = $request->user()->load('employeeInfo');

        return response()->json([
            'success' => true,
            'data'    => $account,
        ]);
    }

    /**
     * POST /api/auth/logout-all
     * Revoke ALL tokens for the current user (logout from all devices).
     */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices.',
        ]);
    }

    /**
     * POST /api/auth/change-password
     * Change the authenticated employee's password.
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        $account = $request->user();

        if (! Hash::check($validated['current_password'], $account->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $account->update(['password_hash' => Hash::make($validated['new_password'])]);

        // Revoke all tokens so the user must re-login
        $account->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully. Please log in again.',
        ]);
    }
}
