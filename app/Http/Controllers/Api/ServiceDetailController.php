<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ServiceDetailController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceDetail::query();

        // Filter by availability
        if ($request->has('available') && $request->available) {
            $query->available();
        }

        // Filter by booking requirement
        if ($request->has('requires_booking')) {
            $query->where('requires_booking', $request->requires_booking);
        }

        // Filter by price range
        if ($request->has('min_price') && $request->has('max_price')) {
            $query->byPriceRange($request->min_price, $request->max_price);
        }

        // Filter by duration
        if ($request->has('duration')) {
            $query->byDuration($request->duration);
        }

        // Filter by unit
        if ($request->has('unit')) {
            $query->byUnit($request->unit);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort results
        $sortField = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $services = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $services
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string|in:item,hour,day',
            'duration' => 'nullable|numeric|min:0',
            'is_available' => 'boolean',
            'requires_booking' => 'boolean',
            'min_booking_hours' => 'nullable|integer|min:1',
            'max_booking_hours' => 'nullable|integer|min:1',
            'operating_hours' => 'nullable|array',
            'requirements' => 'nullable|array',
            'included_items' => 'nullable|array',
            'additional_charges' => 'nullable|array',
            'images' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $service = ServiceDetail::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Service detail created successfully',
            'data' => $service
        ], 201);
    }

    public function show($id)
    {
        $service = ServiceDetail::find($id);

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service detail not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $service
        ]);
    }

    public function update(Request $request, $id)
    {
        $service = ServiceDetail::find($id);

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service detail not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'unit' => 'sometimes|required|string|in:item,hour,day',
            'duration' => 'nullable|numeric|min:0',
            'is_available' => 'boolean',
            'requires_booking' => 'boolean',
            'min_booking_hours' => 'nullable|integer|min:1',
            'max_booking_hours' => 'nullable|integer|min:1',
            'operating_hours' => 'nullable|array',
            'requirements' => 'nullable|array',
            'included_items' => 'nullable|array',
            'additional_charges' => 'nullable|array',
            'images' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $service->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Service detail updated successfully',
            'data' => $service
        ]);
    }

    public function destroy($id)
    {
        $service = ServiceDetail::find($id);

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service detail not found'
            ], 404);
        }

        $service->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Service detail deleted successfully'
        ]);
    }

    public function toggleStatus($id)
    {
        $service = ServiceDetail::find($id);

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service detail not found'
            ], 404);
        }

        $service->update(['is_available' => !$service->is_available]);

        return response()->json([
            'status' => 'success',
            'message' => 'Service detail status toggled successfully',
            'data' => $service
        ]);
    }

    public function calculatePrice(Request $request, $id)
    {
        $service = ServiceDetail::find($id);

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service detail not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
            'hours' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($service->requires_booking && !$request->hours) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hours are required for this service'
            ], 422);
        }

        if ($service->requires_booking && !$service->validateBooking($request->hours)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid booking hours'
            ], 422);
        }

        $totalPrice = $service->calculateTotalPrice($request->quantity, $request->hours);

        return response()->json([
            'status' => 'success',
            'data' => [
                'service' => $service,
                'quantity' => $request->quantity,
                'hours' => $request->hours,
                'total_price' => $totalPrice
            ]
        ]);
    }

    public function getTimeSlots(Request $request, $id)
    {
        $service = ServiceDetail::find($id);

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service detail not found'
            ], 404);
        }

        if (!$service->requires_booking) {
            return response()->json([
                'status' => 'error',
                'message' => 'This service does not require booking'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $date = Carbon::parse($request->date);
        $timeSlots = $service->getAvailableTimeSlots($date);

        return response()->json([
            'status' => 'success',
            'data' => [
                'service' => $service,
                'date' => $date->format('Y-m-d'),
                'time_slots' => $timeSlots
            ]
        ]);
    }

    public function getStats()
    {
        $stats = [
            'total_services' => ServiceDetail::count(),
            'available_services' => ServiceDetail::available()->count(),
            'services_requiring_booking' => ServiceDetail::requiresBooking()->count(),
            'unit_counts' => [
                'item' => ServiceDetail::byUnit('item')->count(),
                'hour' => ServiceDetail::byUnit('hour')->count(),
                'day' => ServiceDetail::byUnit('day')->count()
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}