<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ServiceTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceType::query();

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->active);
        }

        // Filter by code
        if ($request->has('code')) {
            $query->byCode($request->code);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Sort results
        $sortField = $request->get('sort_by', 'display_order');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $types = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $types
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:service_types',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        
        // Generate code if not provided
        if (!isset($data['code'])) {
            $data['code'] = Str::slug($data['name']);
        }

        $type = ServiceType::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Service type created successfully',
            'data' => $type
        ], 201);
    }

    public function show($id)
    {
        $type = ServiceType::find($id);

        if (!$type) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service type not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $type
        ]);
    }

    public function update(Request $request, $id)
    {
        $type = ServiceType::find($id);

        if (!$type) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service type not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:service_types,code,' . $id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $type->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Service type updated successfully',
            'data' => $type
        ]);
    }

    public function destroy($id)
    {
        $type = ServiceType::find($id);

        if (!$type) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service type not found'
            ], 404);
        }

        // Check if there are any associated services
        if ($type->serviceDetails()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete service type with associated services'
            ], 422);
        }

        $type->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Service type deleted successfully'
        ]);
    }

    public function toggleStatus($id)
    {
        $type = ServiceType::find($id);

        if (!$type) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service type not found'
            ], 404);
        }

        $type->toggleStatus();

        return response()->json([
            'status' => 'success',
            'message' => 'Service type status toggled successfully',
            'data' => $type
        ]);
    }

    public function updateDisplayOrder(Request $request, $id)
    {
        $type = ServiceType::find($id);

        if (!$type) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service type not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'display_order' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $type->updateDisplayOrder($request->display_order);

        return response()->json([
            'status' => 'success',
            'message' => 'Service type display order updated successfully',
            'data' => $type
        ]);
    }

    public function getServices($id)
    {
        $type = ServiceType::find($id);

        if (!$type) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service type not found'
            ], 404);
        }

        $services = $type->serviceDetails()->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $services
        ]);
    }

    public function getActiveServices($id)
    {
        $type = ServiceType::find($id);

        if (!$type) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service type not found'
            ], 404);
        }

        $services = $type->getActiveServiceDetails();

        return response()->json([
            'status' => 'success',
            'data' => $services
        ]);
    }

    public function getStats()
    {
        $stats = [
            'total_types' => ServiceType::count(),
            'active_types' => ServiceType::active()->count(),
            'inactive_types' => ServiceType::inactive()->count(),
            'types_with_services' => ServiceType::has('serviceDetails')->count(),
            'types_with_active_services' => ServiceType::whereHas('serviceDetails', function($query) {
                $query->where('is_available', true);
            })->count()
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}