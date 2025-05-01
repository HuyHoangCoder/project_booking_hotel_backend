<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustomerGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerGroup::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
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

        $groups = $query->withCount('customers')->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $groups
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:customer_groups',
            'code' => 'required|string|max:50|unique:customer_groups',
            'description' => 'nullable|string',
            'discount_percentage' => 'required|numeric|min:0|max:100',
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

        $group = CustomerGroup::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Customer group created successfully',
            'data' => $group
        ], 201);
    }

    public function show($id)
    {
        $group = CustomerGroup::with(['customers' => function($query) {
            $query->select('customers.*', 'group_members.fullname', 'group_members.relationship');
        }])->find($id);

        if (!$group) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer group not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $group
        ]);
    }

    public function update(Request $request, $id)
    {
        $group = CustomerGroup::find($id);

        if (!$group) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer group not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('customer_groups')->ignore($id)],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('customer_groups')->ignore($id)],
            'description' => 'nullable|string',
            'discount_percentage' => 'sometimes|required|numeric|min:0|max:100',
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

        $group->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Customer group updated successfully',
            'data' => $group
        ]);
    }

    public function destroy($id)
    {
        $group = CustomerGroup::find($id);

        if (!$group) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer group not found'
            ], 404);
        }

        // Check if group has any members
        if ($group->customers()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete group with existing members'
            ], 422);
        }

        $group->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Customer group deleted successfully'
        ]);
    }

    public function toggleStatus($id)
    {
        $group = CustomerGroup::find($id);

        if (!$group) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer group not found'
            ], 404);
        }

        $group->update([
            'is_active' => !$group->is_active
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Customer group status toggled successfully',
            'data' => $group
        ]);
    }

    public function addMember(Request $request, $id)
    {
        $group = CustomerGroup::find($id);

        if (!$group) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer group not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'fullname' => 'required|string|max:255',
            'relationship' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $group->customers()->attach($request->customer_id, [
                'fullname' => $request->fullname,
                'relationship' => $request->relationship
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Member added to group successfully',
                'data' => $group->load('customers')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer is already in this group'
            ], 422);
        }
    }

    public function removeMember(Request $request, $id)
    {
        $group = CustomerGroup::find($id);

        if (!$group) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer group not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $group->customers()->detach($request->customer_id);

        return response()->json([
            'status' => 'success',
            'message' => 'Member removed from group successfully',
            'data' => $group->load('customers')
        ]);
    }

    public function updateMember(Request $request, $id)
    {
        $group = CustomerGroup::find($id);

        if (!$group) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer group not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'fullname' => 'required|string|max:255',
            'relationship' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $group->customers()->updateExistingPivot($request->customer_id, [
            'fullname' => $request->fullname,
            'relationship' => $request->relationship
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Member information updated successfully',
            'data' => $group->load('customers')
        ]);
    }
}