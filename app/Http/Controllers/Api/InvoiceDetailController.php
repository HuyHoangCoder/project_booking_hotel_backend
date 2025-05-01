<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceDetailController extends Controller
{
    public function index(Request $request, $invoiceId)
    {
        $invoice = Invoice::find($invoiceId);

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice not found'
            ], 404);
        }

        $query = $invoice->details();

        // Filter by item type
        if ($request->has('item_type')) {
            $query->where('item_type', $request->item_type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by price range
        if ($request->has('min_price') && $request->has('max_price')) {
            $query->whereBetween('total_price', [$request->min_price, $request->max_price]);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('description', 'like', "%{$search}%");
        }

        // Sort results
        $sortField = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $details = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $details
        ]);
    }

    public function store(Request $request, $invoiceId)
    {
        $invoice = Invoice::find($invoiceId);

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice not found'
            ], 404);
        }

        if ($invoice->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only add details to draft invoices'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'item_type' => 'required|string|in:room,service,amenity,other',
            'description' => 'required|string',
            'quantity' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'discount_rate' => 'required|numeric|min:0|max:100',
            'details' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $detail = $invoice->details()->create([
            'item_type' => $request->item_type,
            'description' => $request->description,
            'quantity' => $request->quantity,
            'unit_price' => $request->unit_price,
            'tax_rate' => $request->tax_rate,
            'discount_rate' => $request->discount_rate,
            'details' => $request->details
        ]);

        $detail->calculateTotals();

        // Update invoice totals
        $invoice->calculateTotals();

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice detail added successfully',
            'data' => $detail
        ], 201);
    }

    public function show($invoiceId, $id)
    {
        $detail = InvoiceDetail::where('invoice_id', $invoiceId)->find($id);

        if (!$detail) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice detail not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $detail
        ]);
    }

    public function update(Request $request, $invoiceId, $id)
    {
        $invoice = Invoice::find($invoiceId);

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice not found'
            ], 404);
        }

        if ($invoice->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only update details of draft invoices'
            ], 422);
        }

        $detail = $invoice->details()->find($id);

        if (!$detail) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice detail not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'item_type' => 'sometimes|required|string|in:room,service,amenity,other',
            'description' => 'sometimes|required|string',
            'quantity' => 'sometimes|required|numeric|min:0',
            'unit_price' => 'sometimes|required|numeric|min:0',
            'tax_rate' => 'sometimes|required|numeric|min:0|max:100',
            'discount_rate' => 'sometimes|required|numeric|min:0|max:100',
            'details' => 'nullable|array',
            'is_active' => 'sometimes|required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $detail->update($request->all());
        $detail->calculateTotals();

        // Update invoice totals
        $invoice->calculateTotals();

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice detail updated successfully',
            'data' => $detail
        ]);
    }

    public function destroy($invoiceId, $id)
    {
        $invoice = Invoice::find($invoiceId);

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice not found'
            ], 404);
        }

        if ($invoice->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only delete details from draft invoices'
            ], 422);
        }

        $detail = $invoice->details()->find($id);

        if (!$detail) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice detail not found'
            ], 404);
        }

        $detail->delete();

        // Update invoice totals
        $invoice->calculateTotals();

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice detail deleted successfully'
        ]);
    }

    public function toggleStatus($invoiceId, $id)
    {
        $invoice = Invoice::find($invoiceId);

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice not found'
            ], 404);
        }

        if ($invoice->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only toggle details of draft invoices'
            ], 422);
        }

        $detail = $invoice->details()->find($id);

        if (!$detail) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice detail not found'
            ], 404);
        }

        $detail->update(['is_active' => !$detail->is_active]);

        // Update invoice totals
        $invoice->calculateTotals();

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice detail status toggled successfully',
            'data' => $detail
        ]);
    }

    public function getStats($invoiceId)
    {
        $invoice = Invoice::find($invoiceId);

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice not found'
            ], 404);
        }

        $stats = [
            'total_items' => $invoice->details()->count(),
            'total_amount' => $invoice->details()->sum('total_price'),
            'total_tax' => $invoice->details()->sum('tax_amount'),
            'total_discount' => $invoice->details()->sum('discount_amount'),
            'item_type_counts' => [
                'room' => $invoice->details()->where('item_type', 'room')->count(),
                'service' => $invoice->details()->where('item_type', 'service')->count(),
                'amenity' => $invoice->details()->where('item_type', 'amenity')->count(),
                'other' => $invoice->details()->where('item_type', 'other')->count()
            ],
            'active_items' => $invoice->details()->where('is_active', true)->count(),
            'inactive_items' => $invoice->details()->where('is_active', false)->count()
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}