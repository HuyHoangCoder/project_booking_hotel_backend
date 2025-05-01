<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryDelivery;
use App\Models\InventoryDeliveryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class InventoryDeliveryController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryDelivery::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        // Filter by recipient
        if ($request->has('recipient')) {
            $query->byRecipient($request->recipient);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('delivery_number', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%")
                  ->orWhere('tracking_number', 'like', "%{$search}%");
            });
        }

        // Sort results
        $sortField = $request->get('sort_by', 'delivery_date');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $deliveries = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $deliveries
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'delivery_date' => 'required|date',
            'recipient_name' => 'required|string|max:255',
            'recipient_contact' => 'nullable|string|max:255',
            'recipient_address' => 'nullable|string|max:255',
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

            $delivery = InventoryDelivery::create($request->except('items'));

            foreach ($request->items as $item) {
                $item['total_price'] = $item['quantity'] * $item['unit_price'];
                $delivery->items()->create($item);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory delivery created successfully',
                'data' => $delivery->load('items')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create inventory delivery',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $delivery = InventoryDelivery::with('items')->find($id);

        if (!$delivery) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory delivery not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $delivery
        ]);
    }

    public function update(Request $request, $id)
    {
        $delivery = InventoryDelivery::find($id);

        if (!$delivery) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory delivery not found'
            ], 404);
        }

        if ($delivery->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot update a non-pending inventory delivery'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'delivery_date' => 'sometimes|required|date',
            'recipient_name' => 'sometimes|required|string|max:255',
            'recipient_contact' => 'nullable|string|max:255',
            'recipient_address' => 'nullable|string|max:255',
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

            $delivery->update($request->except('items'));

            if ($request->has('items')) {
                $delivery->items()->delete();
                foreach ($request->items as $item) {
                    $item['total_price'] = $item['quantity'] * $item['unit_price'];
                    $delivery->items()->create($item);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory delivery updated successfully',
                'data' => $delivery->load('items')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update inventory delivery',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $delivery = InventoryDelivery::find($id);

        if (!$delivery) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory delivery not found'
            ], 404);
        }

        if ($delivery->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete a non-pending inventory delivery'
            ], 422);
        }

        $delivery->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Inventory delivery deleted successfully'
        ]);
    }

    public function markAsDelivered($id)
    {
        $delivery = InventoryDelivery::find($id);

        if (!$delivery) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory delivery not found'
            ], 404);
        }

        if ($delivery->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending inventory delivery can be marked as delivered'
            ], 422);
        }

        if ($delivery->markAsDelivered()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Inventory delivery marked as delivered successfully',
                'data' => $delivery
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to mark inventory delivery as delivered'
        ], 500);
    }

    public function cancel($id)
    {
        $delivery = InventoryDelivery::find($id);

        if (!$delivery) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory delivery not found'
            ], 404);
        }

        if ($delivery->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending inventory delivery can be cancelled'
            ], 422);
        }

        if ($delivery->cancel()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Inventory delivery cancelled successfully',
                'data' => $delivery
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to cancel inventory delivery'
        ], 500);
    }

    public function getStats()
    {
        $stats = [
            'total_deliveries' => InventoryDelivery::count(),
            'pending_deliveries' => InventoryDelivery::pending()->count(),
            'delivered_deliveries' => InventoryDelivery::delivered()->count(),
            'cancelled_deliveries' => InventoryDelivery::cancelled()->count(),
            'total_items' => InventoryDeliveryItem::count(),
            'total_value' => InventoryDeliveryItem::sum('total_price'),
            'expiring_soon_items' => InventoryDeliveryItem::expiringSoon()->count()
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}