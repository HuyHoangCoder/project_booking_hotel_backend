<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::with(['roomType', 'floor']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by room type
        if ($request->has('room_type_id')) {
            $query->where('room_type_id', $request->room_type_id);
        }

        // Filter by floor
        if ($request->has('floor_id')) {
            $query->where('floor_id', $request->floor_id);
        }

        // Filter by capacity
        if ($request->has('capacity')) {
            $query->where('capacity', '>=', $request->capacity);
        }

        // Filter by price range
        if ($request->has('min_price') && $request->has('max_price')) {
            $query->whereBetween('price_per_night', [$request->min_price, $request->max_price]);
        }

        // Filter by amenities
        if ($request->has('amenities')) {
            $query->whereJsonContains('amenities', $request->amenities);
        }

        // Filter by features
        if ($request->has('is_smoking_allowed')) {
            $query->where('is_smoking_allowed', $request->is_smoking_allowed);
        }
        if ($request->has('has_balcony')) {
            $query->where('has_balcony', $request->has_balcony);
        }
        if ($request->has('has_view')) {
            $query->where('has_view', $request->has_view);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('room_number', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort results
        $sortField = $request->get('sort_by', 'room_number');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $rooms = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $rooms
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_number' => 'required|string|unique:rooms',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'room_type_id' => 'required|exists:room_types,id',
            'floor_id' => 'required|exists:floors,id',
            'capacity' => 'required|integer|min:1',
            'price_per_night' => 'required|numeric|min:0',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_smoking_allowed' => 'boolean',
            'has_balcony' => 'boolean',
            'has_view' => 'boolean',
            'size' => 'nullable|integer|min:0',
            'bed_count' => 'required|integer|min:1',
            'bed_type' => 'required|string|in:single,double,queen,king'
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
                $path = $image->store('rooms', 'public');
                $imagePaths[] = $path;
            }
            $data['images'] = $imagePaths;
        }

        $room = Room::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Room created successfully',
            'data' => $room->load(['roomType', 'floor'])
        ], 201);
    }

    public function show($id)
    {
        $room = Room::with(['roomType', 'floor'])->find($id);

        if (!$room) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $room
        ]);
    }

    public function update(Request $request, $id)
    {
        $room = Room::find($id);

        if (!$room) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'room_number' => 'sometimes|required|string|unique:rooms,room_number,' . $id,
            'name' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'room_type_id' => 'sometimes|required|exists:room_types,id',
            'floor_id' => 'sometimes|required|exists:floors,id',
            'capacity' => 'sometimes|required|integer|min:1',
            'price_per_night' => 'sometimes|required|numeric|min:0',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_smoking_allowed' => 'boolean',
            'has_balcony' => 'boolean',
            'has_view' => 'boolean',
            'size' => 'nullable|integer|min:0',
            'bed_count' => 'sometimes|required|integer|min:1',
            'bed_type' => 'sometimes|required|string|in:single,double,queen,king'
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
            if ($room->images) {
                foreach ($room->images as $oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }
            }

            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('rooms', 'public');
                $imagePaths[] = $path;
            }
            $data['images'] = $imagePaths;
        }

        $room->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Room updated successfully',
            'data' => $room->load(['roomType', 'floor'])
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $room = Room::find($id);

        if (!$room) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:available,occupied,maintenance,cleaning'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $room->update(['status' => $request->status]);

        return response()->json([
            'status' => 'success',
            'message' => 'Room status updated successfully',
            'data' => $room->load(['roomType', 'floor'])
        ]);
    }

    public function destroy($id)
    {
        $room = Room::find($id);

        if (!$room) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room not found'
            ], 404);
        }

        // Delete images
        if ($room->images) {
            foreach ($room->images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        $room->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Room deleted successfully'
        ]);
    }
}