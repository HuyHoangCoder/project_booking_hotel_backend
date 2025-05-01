<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['invoice', 'customer']);

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        }

        // Filter by amount range
        if ($request->has('min_amount') && $request->has('max_amount')) {
            $query->whereBetween('amount', [$request->min_amount, $request->max_amount]);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                  ->orWhere('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sort results
        $sortField = $request->get('sort_by', 'payment_date');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $payments = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $payments
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:cash,credit_card,bank_transfer,e_wallet,other',
            'transaction_id' => 'nullable|string',
            'payment_details' => 'nullable|array',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate payment number
        $paymentNumber = 'PAY-' . strtoupper(Str::random(8));

        $payment = Payment::create([
            'payment_number' => $paymentNumber,
            'invoice_id' => $request->invoice_id,
            'customer_id' => $request->customer_id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
            'payment_details' => $request->payment_details,
            'payment_date' => $request->payment_date,
            'notes' => $request->notes,
            'payment_status' => 'pending'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Payment created successfully',
            'data' => $payment->load(['invoice', 'customer'])
        ], 201);
    }

    public function show($id)
    {
        $payment = Payment::with(['invoice', 'customer'])->find($id);

        if (!$payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $payment
        ]);
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment not found'
            ], 404);
        }

        if ($payment->payment_status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only update pending payments'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'sometimes|required|numeric|min:0',
            'payment_method' => 'sometimes|required|string|in:cash,credit_card,bank_transfer,e_wallet,other',
            'transaction_id' => 'nullable|string',
            'payment_details' => 'nullable|array',
            'payment_date' => 'sometimes|required|date',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $payment->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Payment updated successfully',
            'data' => $payment->load(['invoice', 'customer'])
        ]);
    }

    public function complete($id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment not found'
            ], 404);
        }

        if ($payment->payment_status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending payments can be completed'
            ], 422);
        }

        $payment->markAsCompleted();

        return response()->json([
            'status' => 'success',
            'message' => 'Payment completed successfully',
            'data' => $payment->load(['invoice', 'customer'])
        ]);
    }

    public function fail($id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment not found'
            ], 404);
        }

        if ($payment->payment_status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending payments can be marked as failed'
            ], 422);
        }

        $payment->markAsFailed();

        return response()->json([
            'status' => 'success',
            'message' => 'Payment marked as failed',
            'data' => $payment->load(['invoice', 'customer'])
        ]);
    }

    public function refund(Request $request, $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment not found'
            ], 404);
        }

        if (!$payment->isRefundable()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment is not refundable'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'refund_reason' => 'required|string',
            'refund_details' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create refund payment
        $refundPayment = Payment::create([
            'payment_number' => 'REF-' . strtoupper(Str::random(8)),
            'invoice_id' => $payment->invoice_id,
            'customer_id' => $payment->customer_id,
            'amount' => -$payment->amount,
            'payment_method' => $payment->payment_method,
            'transaction_id' => $request->transaction_id ?? null,
            'payment_details' => array_merge(
                $request->refund_details ?? [],
                ['original_payment_id' => $payment->id, 'refund_reason' => $request->refund_reason]
            ),
            'payment_date' => now(),
            'payment_status' => 'completed'
        ]);

        // Mark original payment as refunded
        $payment->markAsRefunded();

        return response()->json([
            'status' => 'success',
            'message' => 'Payment refunded successfully',
            'data' => [
                'original_payment' => $payment->load(['invoice', 'customer']),
                'refund_payment' => $refundPayment->load(['invoice', 'customer'])
            ]
        ]);
    }

    public function getStats()
    {
        $stats = [
            'total_payments' => Payment::count(),
            'total_amount' => Payment::sum('amount'),
            'status_counts' => [
                'pending' => Payment::pending()->count(),
                'completed' => Payment::completed()->count(),
                'failed' => Payment::failed()->count(),
                'refunded' => Payment::refunded()->count()
            ],
            'method_counts' => [
                'cash' => Payment::byMethod('cash')->count(),
                'credit_card' => Payment::byMethod('credit_card')->count(),
                'bank_transfer' => Payment::byMethod('bank_transfer')->count(),
                'e_wallet' => Payment::byMethod('e_wallet')->count(),
                'other' => Payment::byMethod('other')->count()
            ],
            'today_payments' => Payment::whereDate('payment_date', today())->count(),
            'today_amount' => Payment::whereDate('payment_date', today())->sum('amount')
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}