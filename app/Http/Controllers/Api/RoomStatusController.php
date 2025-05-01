<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoomStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoomStatusController extends Controller
{
    public function index(Request $request)
    {
        $query = RoomStatus::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Filter by availability
        if ($request->has('is_available')) {
            $query->where('is_available', $request->is_available);
        }

        // Filter by occupancy
        if ($request->has('is_occupied')) {
            $query->where('is_occupied', $request->is_occupied);
        }

        // Filter by maintenance
        if ($request->has('is_maintenance')) {
            $query->where('is_maintenance', $request->is_maintenance);
        }

        // Filter by cleaning
        if ($request->has('is_cleaning')) {
            $query->where('is_cleaning', $request->is_cleaning);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort results
        $sortField = $request->get('sort_by', 'priority');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $statuses = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $statuses
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'code' => 'required|string|unique:room_statuses',
            'description' => 'nullable|string',
            'color' => 'nullable|string',
            'is_active' => 'boolean',
            'is_available' => 'boolean',
            'is_occupied' => 'boolean',
            'is_maintenance' => 'boolean',
            'is_cleaning' => 'boolean',
            'priority' => 'integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $status = RoomStatus::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Room status created successfully',
            'data' => $status
        ], 201);
    }

    public function show($id)
    {
        $status = RoomStatus::find($id);

        if (!$status) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room status not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $status
        ]);
    }

    public function update(Request $request, $id)
    {
        $status = RoomStatus::find($id);

        if (!$status) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room status not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string',
            'code' => 'sometimes|required|string|unique:room_statuses,code,' . $id,
            'description' => 'nullable|string',
            'color' => 'nullable|string',
            'is_active' => 'boolean',
            'is_available' => 'boolean',
            'is_occupied' => 'boolean',
            'is_maintenance' => 'boolean',
            'is_cleaning' => 'boolean',
            'priority' => 'integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $status->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Room status updated successfully',
            'data' => $status
        ]);
    }

    public function toggleStatus($id)
    {
        $status = RoomStatus::find($id);

        if (!$status) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room status not found'
            ], 404);
        }

        $status->update(['is_active' => !$status->is_active]);

        return response()->json([
            'status' => 'success',
            'message' => 'Room status toggled successfully',
            'data' => $status
        ]);
    }

    public function destroy($id)
    {
        $status = RoomStatus::find($id);

        if (!$status) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room status not found'
            ], 404);
        }

        // Check if status has rooms
        if ($status->rooms()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete room status with existing rooms'
            ], 422);
        }

        $status->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Room status deleted successfully'
        ]);
    }

    public function getStatusStats()
    {
        $stats = [
            'total' => RoomStatus::count(),
            'active' => RoomStatus::active()->count(),
            'available' => RoomStatus::available()->count(),
            'occupied' => RoomStatus::occupied()->count(),
            'maintenance' => RoomStatus::maintenance()->count(),
            'cleaning' => RoomStatus::cleaning()->count()
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}