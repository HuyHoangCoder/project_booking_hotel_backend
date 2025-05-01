<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\LostItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::with(['customerType', 'groups', 'bookings', 'lostItems']);

        // Advanced search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('citizen_identity', 'like', "%{$search}%");
            });
        }

        // Filter by customer type
        if ($request->has('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        // Filter by group
        if ($request->has('group_id')) {
            $query->whereHas('groups', function($q) use ($request) {
                $q->where('customer_groups.id', $request->group_id);
            });
        }

        // Filter by booking status
        if ($request->has('booking_status')) {
            $query->whereHas('bookings', function($q) use ($request) {
                $q->where('status', $request->booking_status);
            });
        }

        // Filter by lost items status
        if ($request->has('lost_item_status')) {
            $query->whereHas('lostItems', function($q) use ($request) {
                $q->where('status', $request->lost_item_status);
            });
        }

        // Sort results
        $sortField = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $customers = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $customers
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|max:255',
            'citizen_identity' => 'required|string|unique:customers',
            'phone' => 'required|string|unique:customers',
            'email' => 'required|email|unique:customers',
            'dob' => 'required|date|before:today',
            'customer_type' => 'required|exists:customer_types,id',
            'groups' => 'array',
            'groups.*.group_id' => 'required|exists:customer_groups,id',
            'groups.*.fullname' => 'required|string',
            'groups.*.relationship' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer = Customer::create($request->except('groups'));

        if ($request->has('groups')) {
            foreach ($request->groups as $group) {
                $customer->groups()->attach($group['group_id'], [
                    'fullname' => $group['fullname'],
                    'relationship' => $group['relationship']
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Customer created successfully',
            'data' => $customer->load(['customerType', 'groups'])
        ], 201);
    }

    public function show($id)
    {
        $customer = Customer::with(['customerType', 'groups', 'bookings', 'lostItems'])->find($id);
        
        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $customer
        ]);
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'fullname' => 'sometimes|required|string|max:255',
            'citizen_identity' => ['sometimes', 'required', Rule::unique('customers')->ignore($id)],
            'phone' => ['sometimes', 'required', Rule::unique('customers')->ignore($id)],
            'email' => ['sometimes', 'required', 'email', Rule::unique('customers')->ignore($id)],
            'dob' => 'sometimes|required|date|before:today',
            'customer_type' => 'sometimes|required|exists:customer_types,id',
            'groups' => 'array',
            'groups.*.group_id' => 'required|exists:customer_groups,id',
            'groups.*.fullname' => 'required|string',
            'groups.*.relationship' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer->update($request->except('groups'));

        if ($request->has('groups')) {
            $customer->groups()->detach();
            foreach ($request->groups as $group) {
                $customer->groups()->attach($group['group_id'], [
                    'fullname' => $group['fullname'],
                    'relationship' => $group['relationship']
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Customer updated successfully',
            'data' => $customer->load(['customerType', 'groups'])
        ]);
    }

    public function destroy($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found'
            ], 404);
        }

        // Check if customer has any active bookings
        if ($customer->bookings()->where('status', 'active')->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete customer with active bookings'
            ], 422);
        }

        $customer->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Customer deleted successfully'
        ]);
    }

    public function addToGroup(Request $request, $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'group_id' => 'required|exists:customer_groups,id',
            'fullname' => 'required|string',
            'relationship' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customer->groups()->attach($request->group_id, [
                'fullname' => $request->fullname,
                'relationship' => $request->relationship
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer added to group successfully',
                'data' => $customer->load('groups')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer is already in this group'
            ], 422);
        }
    }

    public function removeFromGroup(Request $request, $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'group_id' => 'required|exists:customer_groups,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer->groups()->detach($request->group_id);

        return response()->json([
            'status' => 'success',
            'message' => 'Customer removed from group successfully',
            'data' => $customer->load('groups')
        ]);
    }

    public function reportLostItem(Request $request, $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location_found' => 'required|string|max:255',
            'found_at' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $lostItem = $customer->lostItems()->create([
            'item_name' => $request->item_name,
            'description' => $request->description,
            'location_found' => $request->location_found,
            'found_at' => $request->found_at,
            'status' => 'pending',
            'notes' => $request->notes
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Lost item reported successfully',
            'data' => $lostItem
        ], 201);
    }

    public function updateLostItemStatus(Request $request, $id, $itemId)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found'
            ], 404);
        }

        $lostItem = $customer->lostItems()->find($itemId);

        if (!$lostItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lost item not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,claimed,disposed',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $lostItem->update([
            'status' => $request->status,
            'notes' => $request->notes
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Lost item status updated successfully',
            'data' => $lostItem
        ]);
    }
} 