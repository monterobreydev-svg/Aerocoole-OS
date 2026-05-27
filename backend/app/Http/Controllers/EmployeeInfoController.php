<?php

namespace App\Http\Controllers;

use App\Models\EmployeeInfo;
use Illuminate\Http\Request;

class EmployeeInfoController extends Controller
{
    /**
     * GET /api/employee-infos
     * List all employee profiles with their account info.
     */
    public function index()
    {
        $employees = EmployeeInfo::with('account')->get();

        return response()->json([
            'success' => true,
            'data'    => $employees,
        ]);
    }

    /**
     * POST /api/employee-infos
     * Create a profile for an employee account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employeeAccount_id' => 'required|integer|exists:employee_accounts,employeeAccount_id|unique:employee_infos,employeeAccount_id',
            'first_name'         => 'required|string|max:100',
            'middle_name'        => 'nullable|string|max:100',
            'last_name'          => 'required|string|max:100',
            'email'              => 'required|email|unique:employee_infos,email',
            'position'           => 'required|string|max:100',
            'hire_date'          => 'nullable|date',
        ]);

        $employee = EmployeeInfo::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Employee profile created successfully.',
            'data'    => $employee->load('account'),
        ], 201);
    }

    /**
     * GET /api/employee-infos/{id}
     * Show a single employee profile.
     */
    public function show($id)
    {
        $employee = EmployeeInfo::with(['account', 'schedules', 'workLogs'])->find($id);

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $employee,
        ]);
    }

    /**
     * PUT/PATCH /api/employee-infos/{id}
     * Update an employee profile.
     */
    public function update(Request $request, $id)
    {
        $employee = EmployeeInfo::find($id);

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found.',
            ], 404);
        }

        $validated = $request->validate([
            'first_name'  => 'sometimes|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name'   => 'sometimes|string|max:100',
            'email'       => 'sometimes|email|unique:employee_infos,email,' . $id . ',employee_id',
            'position'    => 'sometimes|string|max:100',
            'hire_date'   => 'nullable|date',
        ]);

        $employee->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Employee profile updated successfully.',
            'data'    => $employee->fresh('account'),
        ]);
    }

    /**
     * DELETE /api/employee-infos/{id}
     * Delete an employee profile.
     */
    public function destroy($id)
    {
        $employee = EmployeeInfo::find($id);

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found.',
            ], 404);
        }

        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee profile deleted successfully.',
        ]);
    }
}
