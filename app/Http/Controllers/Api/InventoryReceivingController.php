<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryReceiving;
use App\Models\InventoryReceivingItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class InventoryReceivingController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryReceiving::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        // Filter by supplier
        if ($request->has('supplier')) {
            $query->bySupplier($request->supplier);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('receiving_number', 'like', "%{$search}%")
                  ->orWhere('supplier_name', 'like', "%{$search}%")
                  ->orWhere('tracking_number', 'like', "%{$search}%");
            });
        }

        // Sort results
        $sortField = $request->get('sort_by', 'receiving_date');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $receivings = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $receivings
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiving_date' => 'required|date',
            'supplier_name' => 'required|string|max:255',
            'supplier_contact' => 'nullable|string|max:255',
            'supplier_address' => 'nullable|string|max:255',
            'delivery_method' => 'nullable|string|max:50',
            'tracking_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.item_code' => 'nullable|string|max:100',
            'items.*.unit' => 'required|string|max:50',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.storage_location' => 'nullable|string|max:255',
            'items.*.notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $receiving = InventoryReceiving::create($request->except('items'));

            foreach ($request->items as $item) {
                $item['total_price'] = $item['quantity'] * $item['unit_price'];
                $receiving->items()->create($item);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory receiving created successfully',
                'data' => $receiving->load('items')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create inventory receiving',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $receiving = InventoryReceiving::with('items')->find($id);

        if (!$receiving) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory receiving not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $receiving
        ]);
    }

    public function update(Request $request, $id)
    {
        $receiving = InventoryReceiving::find($id);

        if (!$receiving) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory receiving not found'
            ], 404);
        }

        if ($receiving->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot update a non-pending inventory receiving'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'receiving_date' => 'sometimes|required|date',
            'supplier_name' => 'sometimes|required|string|max:255',
            'supplier_contact' => 'nullable|string|max:255',
            'supplier_address' => 'nullable|string|max:255',
            'delivery_method' => 'nullable|string|max:50',
            'tracking_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'items' => 'sometimes|required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.item_code' => 'nullable|string|max:100',
            'items.*.unit' => 'required|string|max:50',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.storage_location' => 'nullable|string|max:255',
            'items.*.notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $receiving->update($request->except('items'));

            if ($request->has('items')) {
                $receiving->items()->delete();
                foreach ($request->items as $item) {
                    $item['total_price'] = $item['quantity'] * $item['unit_price'];
                    $receiving->items()->create($item);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory receiving updated successfully',
                'data' => $receiving->load('items')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update inventory receiving',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $receiving = InventoryReceiving::find($id);

        if (!$receiving) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory receiving not found'
            ], 404);
        }

        if ($receiving->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete a non-pending inventory receiving'
            ], 422);
        }

        $receiving->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Inventory receiving deleted successfully'
        ]);
    }

    public function markAsReceived($id)
    {
        $receiving = InventoryReceiving::find($id);

        if (!$receiving) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory receiving not found'
            ], 404);
        }

        if ($receiving->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending inventory receiving can be marked as received'
            ], 422);
        }

        if ($receiving->markAsReceived()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Inventory receiving marked as received successfully',
                'data' => $receiving
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to mark inventory receiving as received'
        ], 500);
    }

    public function cancel($id)
    {
        $receiving = InventoryReceiving::find($id);

        if (!$receiving) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory receiving not found'
            ], 404);
        }

        if ($receiving->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending inventory receiving can be cancelled'
            ], 422);
        }

        if ($receiving->cancel()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Inventory receiving cancelled successfully',
                'data' => $receiving
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to cancel inventory receiving'
        ], 500);
    }

    public function getStats()
    {
        $stats = [
            'total_receivings' => InventoryReceiving::count(),
            'pending_receivings' => InventoryReceiving::pending()->count(),
            'received_receivings' => InventoryReceiving::received()->count(),
            'cancelled_receivings' => InventoryReceiving::cancelled()->count(),
            'total_items' => InventoryReceivingItem::count(),
            'total_value' => InventoryReceivingItem::sum('total_price'),
            'expiring_soon_items' => InventoryReceivingItem::expiringSoon()->count()
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}