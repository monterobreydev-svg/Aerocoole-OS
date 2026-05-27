<?php

namespace App\Http\Controllers;

use App\Models\EmployeeAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeAccountController extends Controller
{
    // Store new employee account
    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:employee_accounts,username',
            'password' => 'required|string|min:6',
            'role' => 'required|string',
            'status' => 'nullable|string'
        ]);

        $account = EmployeeAccount::create([
            'username' => $validated['username'],
            'password_hash' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'status' => $validated['status'] ?? 'Active'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employee account created successfully.',
            'data' => $account
        ], 201);
    }


    // Display all employee accounts
    public function index(Request $request)
    {
        $accounts = EmployeeAccount::with('employeeInfo')->get();

        return response()->json([
            'success' => true,
            'data' => $accounts
        ]);
    }


    // Display single employee account
    public function show($id)
    {
        $account = EmployeeAccount::with('employeeInfo')->find($id);

        if(!$account){
            return response()->json([
                'success' => false,
                'message' => 'Employee account not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $account
        ]);
    }


    // Update employee account
    public function update(Request $request, $id)
    {
        $account = EmployeeAccount::find($id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Employee account not found.'
            ], 404);
        }

        $validated = $request->validate([
            'username' => 'sometimes|string|unique:employee_accounts,username,' . $id . ',employeeAccount_id',
            'password' => 'nullable|string|min:6',
            'role' => 'sometimes|string',
            'status' => 'sometimes|string'
        ]);

        if (isset($validated['username'])) {
            $account->username = $validated['username'];
        }

        if (isset($validated['password'])) {
            $account->password_hash = Hash::make($validated['password']);
        }

        if (isset($validated['role'])) {
            $account->role = $validated['role'];
        }

        if (isset($validated['status'])) {
            $account->status = $validated['status'];
        }

        $account->save();

        return response()->json([
            'success' => true,
            'message' => 'Employee account updated successfully.',
            'data' => $account
        ]);
    }


    // Delete employee account
    public function destroy($id)
    {

         $account = EmployeeAccount::find($id);

        return response()->json([
            'success' => false,
            'message' => 'Employee Account not found.'
        ]);

        $account->delete();

        return response()->json([
            'success' => 'true',
            'message' => 'Employee account deleted successfully.'
        ]);
    }
}
