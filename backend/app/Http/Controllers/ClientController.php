<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * GET /api/clients
     * List all clients.
     */
    public function index()
    {
        $clients = Client::withCount('branches')->get();

        return response()->json([
            'success' => true,
            'data'    => $clients,
        ]);
    }

    /**
     * POST /api/clients
     * Create a new client.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_name'    => 'required|string|max:255',
            'tin_number'     => 'nullable|string|unique:clients,tin_number',
            'client_address' => 'required|string',
            'contact_person' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'email'          => 'nullable|email|unique:clients,email',
            'status'         => 'nullable|string|in:Active,Inactive',
        ]);

        $client = Client::create([
            ...$validated,
            'status' => $validated['status'] ?? 'Active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Client created successfully.',
            'data'    => $client,
        ], 201);
    }

    /**
     * GET /api/clients/{id}
     * Show a single client with its branches.
     */
    public function show($id)
    {
        $client = Client::with('branches')->find($id);

        if (! $client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $client,
        ]);
    }

    /**
     * PUT/PATCH /api/clients/{id}
     * Update a client.
     */
    public function update(Request $request, $id)
    {
        $client = Client::find($id);

        if (! $client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found.',
            ], 404);
        }

        $validated = $request->validate([
            'client_name'    => 'sometimes|string|max:255',
            'tin_number'     => 'nullable|string|unique:clients,tin_number,' . $id . ',client_id',
            'client_address' => 'sometimes|string',
            'contact_person' => 'sometimes|string|max:255',
            'contact_number' => 'sometimes|string|max:20',
            'email'          => 'nullable|email|unique:clients,email,' . $id . ',client_id',
            'status'         => 'sometimes|string|in:Active,Inactive',
        ]);

        $client->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Client updated successfully.',
            'data'    => $client->fresh(),
        ]);
    }

    /**
     * DELETE /api/clients/{id}
     * Delete a client (cascades to branches and schedules).
     */
    public function destroy($id)
    {
        $client = Client::find($id);

        if (! $client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found.',
            ], 404);
        }

        $client->delete();

        return response()->json([
            'success' => true,
            'message' => 'Client deleted successfully.',
        ]);
    }
}
