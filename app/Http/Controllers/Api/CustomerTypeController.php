<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustomerTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerType::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by minimum points
        if ($request->has('minimum_points')) {
            $query->where('minimum_points', '<=', $request->minimum_points);
        }

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Sort results
        $sortField = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $customerTypes = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $customerTypes
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:customer_types',
            'code' => 'required|string|max:50|unique:customer_types',
            'description' => 'nullable|string',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'minimum_points' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'benefits' => 'nullable|array',
            'benefits.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $customerType = CustomerType::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Customer type created successfully',
            'data' => $customerType
        ], 201);
    }

    public function show($id)
    {
        $customerType = CustomerType::with('customers')->find($id);

        if (!$customerType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer type not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $customerType
        ]);
    }

    public function update(Request $request, $id)
    {
        $customerType = CustomerType::find($id);

        if (!$customerType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer type not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('customer_types')->ignore($id)],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('customer_types')->ignore($id)],
            'description' => 'nullable|string',
            'discount_percentage' => 'sometimes|required|numeric|min:0|max:100',
            'minimum_points' => 'sometimes|required|integer|min:0',
            'is_active' => 'boolean',
            'benefits' => 'nullable|array',
            'benefits.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $customerType->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Customer type updated successfully',
            'data' => $customerType
        ]);
    }

    public function destroy($id)
    {
        $customerType = CustomerType::find($id);

        if (!$customerType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer type not found'
            ], 404);
        }

        // Check if customer type has any customers
        if ($customerType->customers()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete customer type with existing customers'
            ], 422);
        }

        $customerType->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Customer type deleted successfully'
        ]);
    }

    public function toggleStatus($id)
    {
        $customerType = CustomerType::find($id);

        if (!$customerType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer type not found'
            ], 404);
        }

        $customerType->update([
            'is_active' => !$customerType->is_active
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Customer type status toggled successfully',
            'data' => $customerType
        ]);
    }

    public function getEligibleTypes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'points' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $eligibleTypes = CustomerType::active()
            ->withMinimumPoints($request->points)
            ->orderBy('minimum_points', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $eligibleTypes
        ]);
    }
} 