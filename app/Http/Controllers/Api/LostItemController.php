<?php

namespace App\Http\Controllers\Api;

use App\Models\LostItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class LostItemController extends Controller
{
    public function index(Request $request)
    {
        $query = LostItem::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        // Filter by location
        if ($request->has('location')) {
            $query->byLocation($request->location);
        }

        // Filter by storage location
        if ($request->has('storage_location')) {
            $query->byStorageLocation($request->storage_location);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('item_number', 'like', "%{$search}%")
                  ->orWhere('item_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort results
        $sortField = $request->get('sort_by', 'date_found');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $items = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $items
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'location_found' => 'nullable|string|max:255',
            'date_found' => 'required|date',
            'storage_location' => 'nullable|string|max:255',
            'finder_name' => 'nullable|string|max:255',
            'finder_contact' => 'nullable|string|max:255',
            'images' => 'nullable|array',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $item = LostItem::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Lost item created successfully',
            'data' => $item
        ], 201);
    }

    public function show($id)
    {
        $item = LostItem::find($id);

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lost item not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $item
        ]);
    }

    public function update(Request $request, $id)
    {
        $item = LostItem::find($id);

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lost item not found'
            ], 404);
        }

        if ($item->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot update a non-pending lost item'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'item_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'location_found' => 'nullable|string|max:255',
            'date_found' => 'sometimes|required|date',
            'storage_location' => 'nullable|string|max:255',
            'finder_name' => 'nullable|string|max:255',
            'finder_contact' => 'nullable|string|max:255',
            'images' => 'nullable|array',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $item->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Lost item updated successfully',
            'data' => $item
        ]);
    }

    public function destroy($id)
    {
        $item = LostItem::find($id);

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lost item not found'
            ], 404);
        }

        if ($item->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete a non-pending lost item'
            ], 422);
        }

        $item->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Lost item deleted successfully'
        ]);
    }

    public function markAsClaimed(Request $request, $id)
    {
        $item = LostItem::find($id);

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lost item not found'
            ], 404);
        }

        if ($item->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending lost items can be claimed'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'claimer_name' => 'required|string|max:255',
            'claimer_contact' => 'required|string|max:255',
            'claim_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($item->markAsClaimed(
            $request->claimer_name,
            $request->claimer_contact,
            $request->claim_notes
        )) {
            return response()->json([
                'status' => 'success',
                'message' => 'Lost item marked as claimed successfully',
                'data' => $item
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to mark lost item as claimed'
        ], 500);
    }

    public function markAsDisposed($id)
    {
        $item = LostItem::find($id);

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lost item not found'
            ], 404);
        }

        if ($item->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending lost items can be disposed'
            ], 422);
        }

        if ($item->markAsDisposed()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Lost item marked as disposed successfully',
                'data' => $item
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to mark lost item as disposed'
        ], 500);
    }

    public function getStats()
    {
        $stats = [
            'total_items' => LostItem::count(),
            'pending_items' => LostItem::pending()->count(),
            'claimed_items' => LostItem::claimed()->count(),
            'disposed_items' => LostItem::disposed()->count(),
            'items_by_category' => LostItem::select('category', DB::raw('count(*) as count'))
                ->groupBy('category')
                ->get()
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}