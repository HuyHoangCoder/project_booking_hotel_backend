<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['customer', 'booking', 'items', 'payments']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            switch ($request->payment_status) {
                case 'paid':
                    $query->where('status', 'paid');
                    break;
                case 'partial':
                    $query->where('status', 'pending')
                        ->where('paid_amount', '>', 0);
                    break;
                case 'unpaid':
                    $query->where('status', 'pending')
                        ->where('paid_amount', 0);
                    break;
            }
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('issue_date', [$request->start_date, $request->end_date]);
        }

        // Filter by amount range
        if ($request->has('min_amount') && $request->has('max_amount')) {
            $query->whereBetween('total_amount', [$request->min_amount, $request->max_amount]);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sort results
        $sortField = $request->get('sort_by', 'issue_date');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $invoices = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $invoices
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'tax_amount' => 'required|numeric|min:0',
            'discount_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.type' => 'required|string|in:room,service,other',
            'items.*.details' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate invoice number
        $invoiceNumber = 'INV-' . strtoupper(Str::random(8));

        // Create invoice
        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'booking_id' => $request->booking_id,
            'customer_id' => $request->customer_id,
            'issue_date' => $request->issue_date,
            'due_date' => $request->due_date,
            'tax_amount' => $request->tax_amount,
            'discount_amount' => $request->discount_amount,
            'notes' => $request->notes,
            'status' => 'draft'
        ]);

        // Add items
        foreach ($request->items as $item) {
            $invoiceItem = $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'type' => $item['type'],
                'details' => $item['details'] ?? null
            ]);
            $invoiceItem->calculateTotal();
        }

        // Calculate totals
        $invoice->calculateTotals();

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice created successfully',
            'data' => $invoice->load(['customer', 'booking', 'items', 'payments'])
        ], 201);
    }

    public function show($id)
    {
        $invoice = Invoice::with(['customer', 'booking', 'items', 'payments'])->find($id);

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $invoice
        ]);
    }

    public function update(Request $request, $id)
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice not found'
            ], 404);
        }

        if ($invoice->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft invoices can be updated'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'issue_date' => 'sometimes|required|date',
            'due_date' => 'sometimes|required|date|after_or_equal:issue_date',
            'tax_amount' => 'sometimes|required|numeric|min:0',
            'discount_amount' => 'sometimes|required|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'sometimes|required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.type' => 'required|string|in:room,service,other',
            'items.*.details' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update invoice
        $invoice->update($request->only([
            'issue_date',
            'due_date',
            'tax_amount',
            'discount_amount',
            'notes'
        ]));

        // Update items if provided
        if ($request->has('items')) {
            // Delete existing items
            $invoice->items()->delete();

            // Add new items
            foreach ($request->items as $item) {
                $invoiceItem = $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'type' => $item['type'],
                    'details' => $item['details'] ?? null
                ]);
                $invoiceItem->calculateTotal();
            }
        }

        // Recalculate totals
        $invoice->calculateTotals();

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice updated successfully',
            'data' => $invoice->load(['customer', 'booking', 'items', 'payments'])
        ]);
    }

    public function addPayment(Request $request, $id)
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice not found'
            ], 404);
        }

        if ($invoice->status === 'paid') {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice is already paid'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0|max:' . $invoice->remaining_amount,
            'payment_method' => 'required|string',
            'transaction_id' => 'nullable|string',
            'payment_details' => 'nullable|array',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $payment = $invoice->addPayment(
            $request->amount,
            $request->payment_method,
            $request->transaction_id,
            $request->payment_details,
            $request->notes
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Payment added successfully',
            'data' => $payment
        ]);
    }

    public function cancel($id)
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice not found'
            ], 404);
        }

        if ($invoice->status === 'paid') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot cancel a paid invoice'
            ], 422);
        }

        $invoice->update(['status' => 'cancelled']);

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice cancelled successfully',
            'data' => $invoice
        ]);
    }

    public function refund(Request $request, $id)
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice not found'
            ], 404);
        }

        if ($invoice->status !== 'paid') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only paid invoices can be refunded'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0|max:' . $invoice->paid_amount,
            'reason' => 'required|string',
            'payment_method' => 'required|string',
            'transaction_id' => 'nullable|string',
            'payment_details' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create refund payment
        $refund = $invoice->payments()->create([
            'amount' => -$request->amount,
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
            'payment_details' => $request->payment_details,
            'payment_date' => now(),
            'notes' => 'Refund: ' . $request->reason
        ]);

        $invoice->paid_amount -= $request->amount;
        $invoice->remaining_amount = $invoice->total_amount - $invoice->paid_amount;
        $invoice->status = 'refunded';
        $invoice->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Refund processed successfully',
            'data' => $refund
        ]);
    }

    public function getStats()
    {
        $stats = [
            'total_invoices' => Invoice::count(),
            'total_amount' => Invoice::sum('total_amount'),
            'total_paid' => Invoice::sum('paid_amount'),
            'total_remaining' => Invoice::sum('remaining_amount'),
            'total_tax' => Invoice::sum('tax_amount'),
            'total_discount' => Invoice::sum('discount_amount'),
            'status_counts' => [
                'draft' => Invoice::draft()->count(),
                'pending' => Invoice::pending()->count(),
                'paid' => Invoice::paid()->count(),
                'cancelled' => Invoice::cancelled()->count(),
                'refunded' => Invoice::refunded()->count()
            ],
            'overdue_count' => Invoice::overdue()->count(),
            'overdue_amount' => Invoice::overdue()->sum('remaining_amount')
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}