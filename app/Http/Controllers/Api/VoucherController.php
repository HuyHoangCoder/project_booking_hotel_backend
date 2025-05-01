<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $query = Voucher::query();

        // Filter by status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'active':
                    $query->active();
                    break;
                case 'expired':
                    $query->expired();
                    break;
                case 'upcoming':
                    $query->upcoming();
                    break;
            }
        }

        // Filter by type
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        // Filter by code
        if ($request->has('code')) {
            $query->byCode($request->code);
        }

        // Filter by availability
        if ($request->has('available') && $request->available) {
            $query->available();
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort results
        $sortField = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $vouchers = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $vouchers
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed_amount',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
            'applicable_room_types' => 'nullable|array',
            'applicable_room_types.*' => 'exists:room_types,id',
            'applicable_customer_types' => 'nullable|array',
            'applicable_customer_types.*' => 'exists:customer_types,id',
            'applicable_customer_groups' => 'nullable|array',
            'applicable_customer_groups.*' => 'exists:customer_groups,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate voucher code if not provided
        $code = $request->code ?? strtoupper(Str::random(8));

        $voucher = Voucher::create(array_merge(
            $request->all(),
            ['code' => $code]
        ));

        return response()->json([
            'status' => 'success',
            'message' => 'Voucher created successfully',
            'data' => $voucher
        ], 201);
    }

    public function show($id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json([
                'status' => 'error',
                'message' => 'Voucher not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $voucher
        ]);
    }

    public function update(Request $request, $id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json([
                'status' => 'error',
                'message' => 'Voucher not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:percentage,fixed_amount',
            'value' => 'sometimes|required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'is_active' => 'boolean',
            'applicable_room_types' => 'nullable|array',
            'applicable_room_types.*' => 'exists:room_types,id',
            'applicable_customer_types' => 'nullable|array',
            'applicable_customer_types.*' => 'exists:customer_types,id',
            'applicable_customer_groups' => 'nullable|array',
            'applicable_customer_groups.*' => 'exists:customer_groups,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $voucher->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Voucher updated successfully',
            'data' => $voucher
        ]);
    }

    public function destroy($id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json([
                'status' => 'error',
                'message' => 'Voucher not found'
            ], 404);
        }

        $voucher->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Voucher deleted successfully'
        ]);
    }

    public function toggleStatus($id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json([
                'status' => 'error',
                'message' => 'Voucher not found'
            ], 404);
        }

        $voucher->update(['is_active' => !$voucher->is_active]);

        return response()->json([
            'status' => 'success',
            'message' => 'Voucher status toggled successfully',
            'data' => $voucher
        ]);
    }

    public function validateVoucher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'room_type_id' => 'nullable|exists:room_types,id',
            'customer_type_id' => 'nullable|exists:customer_types,id',
            'customer_group_id' => 'nullable|exists:customer_groups,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $voucher = Voucher::byCode($request->code)->first();

        if (!$voucher) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid voucher code'
            ], 404);
        }

        if (!$voucher->isAvailable()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Voucher is not available'
            ], 422);
        }

        if ($request->room_type_id && !$voucher->isApplicableToRoomType($request->room_type_id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Voucher is not applicable to this room type'
            ], 422);
        }

        if ($request->customer_type_id && !$voucher->isApplicableToCustomerType($request->customer_type_id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Voucher is not applicable to this customer type'
            ], 422);
        }

        if ($request->customer_group_id && !$voucher->isApplicableToCustomerGroup($request->customer_group_id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Voucher is not applicable to this customer group'
            ], 422);
        }

        $discount = $voucher->calculateDiscount($request->amount);

        return response()->json([
            'status' => 'success',
            'data' => [
                'voucher' => $voucher,
                'discount_amount' => $discount,
                'final_amount' => $request->amount - $discount
            ]
        ]);
    }

    public function getStats()
    {
        $stats = [
            'total_vouchers' => Voucher::count(),
            'active_vouchers' => Voucher::active()->count(),
            'expired_vouchers' => Voucher::expired()->count(),
            'upcoming_vouchers' => Voucher::upcoming()->count(),
            'total_usage' => Voucher::sum('usage_count'),
            'type_counts' => [
                'percentage' => Voucher::byType('percentage')->count(),
                'fixed_amount' => Voucher::byType('fixed_amount')->count()
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}