<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Floor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class FloorController extends Controller
{
    public function index(Request $request)
    {
        $query = Floor::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Filter by level
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort results
        $sortField = $request->get('sort_by', 'level');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $floors = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $floors
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'level' => 'required|integer|unique:floors',
            'description' => 'nullable|string',
            'floor_plan' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $floor = Floor::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Floor created successfully',
            'data' => $floor
        ], 201);
    }

    public function show($id)
    {
        $floor = Floor::with('rooms')->find($id);

        if (!$floor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Floor not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $floor
        ]);
    }

    public function update(Request $request, $id)
    {
        $floor = Floor::find($id);

        if (!$floor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Floor not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string',
            'level' => 'sometimes|required|integer|unique:floors,level,' . $id,
            'description' => 'nullable|string',
            'floor_plan' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $floor->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Floor updated successfully',
            'data' => $floor
        ]);
    }

    public function toggleStatus($id)
    {
        $floor = Floor::find($id);

        if (!$floor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Floor not found'
            ], 404);
        }

        $floor->update(['is_active' => !$floor->is_active]);

        return response()->json([
            'status' => 'success',
            'message' => 'Floor status updated successfully',
            'data' => $floor
        ]);
    }

    public function destroy($id)
    {
        $floor = Floor::find($id);

        if (!$floor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Floor not found'
            ], 404);
        }

        // Check if floor has rooms
        if ($floor->rooms()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete floor with existing rooms'
            ], 422);
        }

        $floor->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Floor deleted successfully'
        ]);
    }

    public function getFloorStats($id)
    {
        $floor = Floor::with('rooms')->find($id);

        if (!$floor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Floor not found'
            ], 404);
        }

        $stats = [
            'total_rooms' => $floor->rooms->count(),
            'available_rooms' => $floor->rooms->where('status', 'available')->count(),
            'occupied_rooms' => $floor->rooms->where('status', 'occupied')->count(),
            'maintenance_rooms' => $floor->rooms->where('status', 'maintenance')->count(),
            'cleaning_rooms' => $floor->rooms->where('status', 'cleaning')->count(),
            'room_types' => $floor->rooms->groupBy('room_type_id')->count()
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}