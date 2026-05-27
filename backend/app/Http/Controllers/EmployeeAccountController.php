<?php

namespace App\Http\Controllers;

use App\Models\EmployeeAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeAccountController extends Controller
{
    /**
     * GET /api/employee-accounts
     * List all employee accounts with their profile.
     */
    public function index()
    {
        $accounts = EmployeeAccount::with('employeeInfo')->get();

        return response()->json([
            'success' => true,
            'data'    => $accounts,
        ]);
    }

    /**
     * POST /api/employee-accounts
     * Create a new employee account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:employee_accounts,username',
            'password' => 'required|string|min:8',
            'role'     => 'required|string|in:Admin,Supervisor,Technician',
            'status'   => 'nullable|string|in:Active,Inactive',
        ]);

        $account = EmployeeAccount::create([
            'username'      => $validated['username'],
            'password_hash' => Hash::make($validated['password']),
            'role'          => $validated['role'],
            'status'        => $validated['status'] ?? 'Active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employee account created successfully.',
            'data'    => $account,
        ], 201);
    }

    /**
     * GET /api/employee-accounts/{id}
     * Show a single employee account.
     */
    public function show($id)
    {
        $account = EmployeeAccount::with('employeeInfo')->find($id);

        if (! $account) {
            return response()->json([
                'success' => false,
                'message' => 'Employee account not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $account,
        ]);
    }

    /**
     * PUT/PATCH /api/employee-accounts/{id}
     * Update an employee account.
     */
    public function update(Request $request, $id)
    {
        $account = EmployeeAccount::find($id);

        if (! $account) {
            return response()->json([
                'success' => false,
                'message' => 'Employee account not found.',
            ], 404);
        }

        $validated = $request->validate([
            'username' => 'sometimes|string|unique:employee_accounts,username,' . $id . ',employeeAccount_id',
            'password' => 'nullable|string|min:8',
            'role'     => 'sometimes|string|in:Admin,Supervisor,Technician',
            'status'   => 'sometimes|string|in:Active,Inactive',
        ]);

        if (isset($validated['username'])) {
            $account->username = $validated['username'];
        }

        if (! empty($validated['password'])) {
            $account->password_hash = Hash::make($validated['password']);
            // Revoke all tokens when password is reset by admin
            $account->tokens()->delete();
        }

        if (isset($validated['role'])) {
            $account->role = $validated['role'];
        }

        if (isset($validated['status'])) {
            $account->status = $validated['status'];
            // Revoke tokens when account is deactivated
            if ($validated['status'] === 'Inactive') {
                $account->tokens()->delete();
            }
        }

        $account->save();

        return response()->json([
            'success' => true,
            'message' => 'Employee account updated successfully.',
            'data'    => $account->fresh('employeeInfo'),
        ]);
    }

    /**
     * DELETE /api/employee-accounts/{id}
     * Soft-delete (deactivate) or permanently delete an employee account.
     */
    public function destroy($id)
    {
        $account = EmployeeAccount::find($id);

        if (! $account) {
            return response()->json([
                'success' => false,
                'message' => 'Employee account not found.',
            ], 404);
        }

        // Revoke all tokens before deleting
        $account->tokens()->delete();
        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee account deleted successfully.',
        ]);
    }
}
