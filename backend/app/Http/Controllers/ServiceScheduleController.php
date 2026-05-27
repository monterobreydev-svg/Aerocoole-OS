<?php

namespace App\Http\Controllers;

use App\Models\ServiceSchedule;
use Illuminate\Http\Request;

class ServiceScheduleController extends Controller
{
    /**
     * GET /api/service-schedules
     * List all schedules with branch and employee info.
     */
    public function index()
    {
        $schedules = ServiceSchedule::with(['branch.client', 'employee'])->get();

        return response()->json([
            'success' => true,
            'data'    => $schedules,
        ]);
    }

    /**
     * GET /api/service-schedules/by-employee/{employeeId}
     * List schedules assigned to a specific employee.
     */
    public function byEmployee($employeeId)
    {
        $schedules = ServiceSchedule::with(['branch.client'])
            ->where('employee_id', $employeeId)
            ->orderBy('schedule_start', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $schedules,
        ]);
    }

    /**
     * GET /api/service-schedules/by-branch/{branchId}
     * List schedules for a specific branch.
     */
    public function byBranch($branchId)
    {
        $schedules = ServiceSchedule::with(['employee'])
            ->where('branch_id', $branchId)
            ->orderBy('schedule_start', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $schedules,
        ]);
    }

    /**
     * POST /api/service-schedules
     * Create a new service schedule.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id'         => 'required|integer|exists:branches,branch_id',
            'employee_id'       => 'required|integer|exists:employee_infos,employee_id',
            'service_type'      => 'required|string|max:255',
            'description'       => 'nullable|string',
            'schedule_start'    => 'required|date',
            'estimated_end'     => 'required|date|after:schedule_start',
            'status'            => 'nullable|string|in:Scheduled,In Progress,Completed,Cancelled,Rescheduled',
            'reschedule_reason' => 'nullable|string',
        ]);

        $schedule = ServiceSchedule::create([
            ...$validated,
            'status'     => $validated['status'] ?? 'Scheduled',
            'created_by' => $request->user()->employeeAccount_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Service schedule created successfully.',
            'data'    => $schedule->load(['branch.client', 'employee']),
        ], 201);
    }

    /**
     * GET /api/service-schedules/{id}
     * Show a single schedule with full related data.
     */
    public function show($id)
    {
        $schedule = ServiceSchedule::with(['branch.client', 'employee', 'workLogs.employee'])->find($id);

        if (! $schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Service schedule not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $schedule,
        ]);
    }

    /**
     * PUT/PATCH /api/service-schedules/{id}
     * Update a service schedule.
     */
    public function update(Request $request, $id)
    {
        $schedule = ServiceSchedule::find($id);

        if (! $schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Service schedule not found.',
            ], 404);
        }

        $validated = $request->validate([
            'branch_id'         => 'sometimes|integer|exists:branches,branch_id',
            'employee_id'       => 'sometimes|integer|exists:employee_infos,employee_id',
            'service_type'      => 'sometimes|string|max:255',
            'description'       => 'nullable|string',
            'schedule_start'    => 'sometimes|date',
            'estimated_end'     => 'sometimes|date|after:schedule_start',
            'status'            => 'sometimes|string|in:Scheduled,In Progress,Completed,Cancelled,Rescheduled',
            'reschedule_reason' => 'nullable|string',
        ]);

        $schedule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Service schedule updated successfully.',
            'data'    => $schedule->fresh(['branch.client', 'employee']),
        ]);
    }

    /**
     * PATCH /api/service-schedules/{id}/status
     * Quickly update only the status (e.g. mark as Completed/Cancelled).
     */
    public function updateStatus(Request $request, $id)
    {
        $schedule = ServiceSchedule::find($id);

        if (! $schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Service schedule not found.',
            ], 404);
        }

        $validated = $request->validate([
            'status'            => 'required|string|in:Scheduled,In Progress,Completed,Cancelled,Rescheduled',
            'reschedule_reason' => 'required_if:status,Rescheduled|nullable|string',
        ]);

        $schedule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Schedule status updated.',
            'data'    => $schedule->fresh(),
        ]);
    }

    /**
     * DELETE /api/service-schedules/{id}
     * Delete a service schedule.
     */
    public function destroy($id)
    {
        $schedule = ServiceSchedule::find($id);

        if (! $schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Service schedule not found.',
            ], 404);
        }

        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service schedule deleted successfully.',
        ]);
    }
}
