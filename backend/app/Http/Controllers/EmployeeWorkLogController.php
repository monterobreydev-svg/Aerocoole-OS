<?php

namespace App\Http\Controllers;

use App\Models\EmployeeWorkLog;
use App\Models\ServiceSchedule;
use Illuminate\Http\Request;

class EmployeeWorkLogController extends Controller
{
    /**
     * GET /api/work-logs
     * List all work logs with schedule and employee info.
     */
    public function index()
    {
        $logs = EmployeeWorkLog::with(['schedule.branch.client', 'employee'])->get();

        return response()->json([
            'success' => true,
            'data'    => $logs,
        ]);
    }

    /**
     * GET /api/work-logs/by-employee/{employeeId}
     * List all work logs for a specific employee.
     */
    public function byEmployee($employeeId)
    {
        $logs = EmployeeWorkLog::with(['schedule.branch.client'])
            ->where('employee_id', $employeeId)
            ->orderBy('actual_work_start', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $logs,
        ]);
    }

    /**
     * GET /api/work-logs/by-schedule/{scheduleId}
     * List all work logs for a specific service schedule.
     */
    public function bySchedule($scheduleId)
    {
        $logs = EmployeeWorkLog::with(['employee'])
            ->where('schedule_id', $scheduleId)
            ->orderBy('actual_work_start', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $logs,
        ]);
    }

    /**
     * POST /api/work-logs
     * Create a new work log (clock-in / submit log).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_id'       => 'required|integer|exists:service_schedules,schedule_id',
            'employee_id'       => 'required|integer|exists:employee_infos,employee_id',
            'actual_work_start' => 'required|date',
            'actual_work_end'   => 'nullable|date|after:actual_work_start',
            'remarks'           => 'nullable|string',
            'gps_latitude'      => 'nullable|numeric|between:-90,90',
            'gps_longitude'     => 'nullable|numeric|between:-180,180',
        ]);

        // Auto-calculate total_work_hours if both timestamps are present
        $totalHours = null;
        if (! empty($validated['actual_work_start']) && ! empty($validated['actual_work_end'])) {
            $start      = \Carbon\Carbon::parse($validated['actual_work_start']);
            $end        = \Carbon\Carbon::parse($validated['actual_work_end']);
            $totalHours = round($start->diffInMinutes($end) / 60, 2);
        }

        $log = EmployeeWorkLog::create([
            ...$validated,
            'total_work_hours' => $totalHours,
            'approval_status'  => 'Pending',
        ]);

        // Mark the linked schedule as "In Progress" if it is still Scheduled
        $schedule = ServiceSchedule::find($validated['schedule_id']);
        if ($schedule && $schedule->status === 'Scheduled') {
            $schedule->update(['status' => 'In Progress']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Work log submitted successfully.',
            'data'    => $log->load(['schedule.branch.client', 'employee']),
        ], 201);
    }

    /**
     * GET /api/work-logs/{id}
     * Show a single work log with full related data.
     */
    public function show($id)
    {
        $log = EmployeeWorkLog::with(['schedule.branch.client', 'employee'])->find($id);

        if (! $log) {
            return response()->json([
                'success' => false,
                'message' => 'Work log not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $log,
        ]);
    }

    /**
     * PUT/PATCH /api/work-logs/{id}
     * Update a work log (only allowed while still Pending).
     */
    public function update(Request $request, $id)
    {
        $log = EmployeeWorkLog::find($id);

        if (! $log) {
            return response()->json([
                'success' => false,
                'message' => 'Work log not found.',
            ], 404);
        }

        if ($log->approval_status !== 'Pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending work logs can be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'actual_work_start' => 'sometimes|date',
            'actual_work_end'   => 'nullable|date|after:actual_work_start',
            'remarks'           => 'nullable|string',
            'gps_latitude'      => 'nullable|numeric|between:-90,90',
            'gps_longitude'     => 'nullable|numeric|between:-180,180',
        ]);

        // Recalculate hours if both endpoints are known after update
        $start = \Carbon\Carbon::parse($validated['actual_work_start'] ?? $log->actual_work_start);
        $end   = isset($validated['actual_work_end'])
            ? ($validated['actual_work_end'] ? \Carbon\Carbon::parse($validated['actual_work_end']) : null)
            : ($log->actual_work_end ? \Carbon\Carbon::parse($log->actual_work_end) : null);

        $validated['total_work_hours'] = ($start && $end)
            ? round($start->diffInMinutes($end) / 60, 2)
            : $log->total_work_hours;

        $log->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Work log updated successfully.',
            'data'    => $log->fresh(['schedule.branch.client', 'employee']),
        ]);
    }

    /**
     * PATCH /api/work-logs/{id}/approve
     * Approve a pending work log (supervisor / admin only).
     */
    public function approve(Request $request, $id)
    {
        $log = EmployeeWorkLog::with('schedule')->find($id);

        if (! $log) {
            return response()->json([
                'success' => false,
                'message' => 'Work log not found.',
            ], 404);
        }

        if ($log->approval_status !== 'Pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending work logs can be approved.',
            ], 422);
        }

        $log->update([
            'approval_status'  => 'Approved',
            'reviewed_by'      => $request->user()->employeeAccount_id,
            'reviewed_at'      => now(),
            'rejection_reason' => null,
        ]);

        // Auto-complete the schedule when all its logs are approved
        if ($log->schedule) {
            $allApproved = EmployeeWorkLog::where('schedule_id', $log->schedule_id)
                ->where('approval_status', '!=', 'Approved')
                ->doesntExist();

            if ($allApproved) {
                $log->schedule->update(['status' => 'Completed']);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Work log approved.',
            'data'    => $log->fresh(),
        ]);
    }

    /**
     * PATCH /api/work-logs/{id}/reject
     * Reject a pending work log with a reason.
     */
    public function reject(Request $request, $id)
    {
        $log = EmployeeWorkLog::find($id);

        if (! $log) {
            return response()->json([
                'success' => false,
                'message' => 'Work log not found.',
            ], 404);
        }

        if ($log->approval_status !== 'Pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending work logs can be rejected.',
            ], 422);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:5',
        ]);

        $log->update([
            'approval_status'  => 'Rejected',
            'reviewed_by'      => $request->user()->employeeAccount_id,
            'reviewed_at'      => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Work log rejected.',
            'data'    => $log->fresh(),
        ]);
    }

    /**
     * DELETE /api/work-logs/{id}
     * Delete a work log (only while Pending).
     */
    public function destroy($id)
    {
        $log = EmployeeWorkLog::find($id);

        if (! $log) {
            return response()->json([
                'success' => false,
                'message' => 'Work log not found.',
            ], 404);
        }

        if ($log->approval_status !== 'Pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending work logs can be deleted.',
            ], 422);
        }

        $log->delete();

        return response()->json([
            'success' => true,
            'message' => 'Work log deleted successfully.',
        ]);
    }
}
