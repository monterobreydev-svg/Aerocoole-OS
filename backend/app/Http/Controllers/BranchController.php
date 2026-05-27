<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    /**
     * GET /api/branches
     * List all branches with their client.
     */
    public function index()
    {
        $branches = Branch::with('client')->get();

        return response()->json([
            'success' => true,
            'data'    => $branches,
        ]);
    }

    /**
     * GET /api/clients/{client}/branches
     * List all branches belonging to a specific client.
     */
    public function byClient($clientId)
    {
        $branches = Branch::with('client')
            ->where('client_id', $clientId)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $branches,
        ]);
    }

    /**
     * POST /api/branches
     * Create a new branch.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'      => 'required|integer|exists:clients,client_id',
            'branch_name'    => 'required|string|max:255',
            'address'        => 'required|string',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'contact_person' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'status'         => 'nullable|string|in:Active,Inactive',
        ]);

        $branch = Branch::create([
            ...$validated,
            'status' => $validated['status'] ?? 'Active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branch created successfully.',
            'data'    => $branch->load('client'),
        ], 201);
    }

    /**
     * GET /api/branches/{id}
     * Show a single branch with its client and schedules.
     */
    public function show($id)
    {
        $branch = Branch::with(['client', 'schedules'])->find($id);

        if (! $branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $branch,
        ]);
    }

    /**
     * PUT/PATCH /api/branches/{id}
     * Update a branch.
     */
    public function update(Request $request, $id)
    {
        $branch = Branch::find($id);

        if (! $branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found.',
            ], 404);
        }

        $validated = $request->validate([
            'client_id'      => 'sometimes|integer|exists:clients,client_id',
            'branch_name'    => 'sometimes|string|max:255',
            'address'        => 'sometimes|string',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'contact_person' => 'sometimes|string|max:255',
            'contact_number' => 'sometimes|string|max:20',
            'status'         => 'sometimes|string|in:Active,Inactive',
        ]);

        $branch->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Branch updated successfully.',
            'data'    => $branch->fresh('client'),
        ]);
    }

    /**
     * DELETE /api/branches/{id}
     * Delete a branch (cascades to schedules).
     */
    public function destroy($id)
    {
        $branch = Branch::find($id);

        if (! $branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found.',
            ], 404);
        }

        $branch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Branch deleted successfully.',
        ]);
    }
}
