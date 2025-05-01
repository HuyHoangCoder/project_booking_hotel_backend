<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class RoomTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = RoomType::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Filter by capacity
        if ($request->has('capacity')) {
            $query->where('max_capacity', '>=', $request->capacity);
        }

        // Filter by price range
        if ($request->has('min_price') && $request->has('max_price')) {
            $query->whereBetween('base_price', [$request->min_price, $request->max_price]);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort results
        $sortField = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $roomTypes = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $roomTypes
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'max_capacity' => 'required|integer|min:1',
            'default_amenities' => 'nullable|array',
            'default_amenities.*' => 'string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except('images');

        // Handle image uploads
        if ($request->hasFile('images')) {
            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('room-types', 'public');
                $imagePaths[] = $path;
            }
            $data['images'] = $imagePaths;
        }

        $roomType = RoomType::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Room type created successfully',
            'data' => $roomType
        ], 201);
    }

    public function show($id)
    {
        $roomType = RoomType::with(['rooms' => function ($query) {
            $query->with('status');
        }])->find($id);

        if (!$roomType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room type not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $roomType
        ]);
    }

    public function update(Request $request, $id)
    {
        $roomType = RoomType::find($id);

        if (!$roomType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room type not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'base_price' => 'sometimes|required|numeric|min:0',
            'max_capacity' => 'sometimes|required|integer|min:1',
            'default_amenities' => 'nullable|array',
            'default_amenities.*' => 'string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except('images');

        // Handle image uploads
        if ($request->hasFile('images')) {
            // Delete old images
            if ($roomType->images) {
                foreach ($roomType->images as $oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }
            }

            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('room-types', 'public');
                $imagePaths[] = $path;
            }
            $data['images'] = $imagePaths;
        }

        $roomType->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Room type updated successfully',
            'data' => $roomType
        ]);
    }

    public function toggleStatus($id)
    {
        $roomType = RoomType::find($id);

        if (!$roomType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room type not found'
            ], 404);
        }

        $roomType->update(['is_active' => !$roomType->is_active]);

        return response()->json([
            'status' => 'success',
            'message' => 'Room type status updated successfully',
            'data' => $roomType
        ]);
    }

    public function destroy($id)
    {
        $roomType = RoomType::find($id);

        if (!$roomType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room type not found'
            ], 404);
        }

        // Check if room type has rooms
        if ($roomType->rooms()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete room type with existing rooms'
            ], 422);
        }

        // Delete images
        if ($roomType->images) {
            foreach ($roomType->images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        $roomType->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Room type deleted successfully'
        ]);
    }

    public function getStats($id)
    {
        $roomType = RoomType::find($id);

        if (!$roomType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room type not found'
            ], 404);
        }

        $stats = [
            'total_rooms' => $roomType->total_rooms_count,
            'available_rooms' => $roomType->available_rooms_count,
            'occupied_rooms' => $roomType->occupied_rooms_count,
            'maintenance_rooms' => $roomType->maintenance_rooms_count,
            'cleaning_rooms' => $roomType->cleaning_rooms_count,
            'average_price' => $roomType->average_price,
            'popularity' => $roomType->popularity,
            'revenue' => $roomType->revenue
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    public function getAvailableTypes(Request $request)
    {
        $query = RoomType::active();

        // Filter by capacity
        if ($request->has('capacity')) {
            $query->where('max_capacity', '>=', $request->capacity);
        }

        // Filter by price range
        if ($request->has('min_price') && $request->has('max_price')) {
            $query->whereBetween('base_price', [$request->min_price, $request->max_price]);
        }

        $roomTypes = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $roomTypes
        ]);
    }
}
