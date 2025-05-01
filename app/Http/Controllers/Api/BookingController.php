<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['customer', 'room']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->where(function($q) use ($request) {
                $q->whereBetween('check_in_date', [$request->date_from, $request->date_to])
                  ->orWhereBetween('check_out_date', [$request->date_from, $request->date_to]);
            });
        }

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by room
        if ($request->has('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        // Search by booking number
        if ($request->has('search')) {
            $query->where('booking_number', 'like', "%{$request->search}%");
        }

        // Sort results
        $sortField = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $bookings = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $bookings
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_guests' => 'required|integer|min:1',
            'special_requests' => 'nullable|string',
            'additional_services' => 'nullable|array',
            'additional_services.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check room availability
        $isRoomAvailable = !Booking::where('room_id', $request->room_id)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->where(function($query) use ($request) {
                $query->whereBetween('check_in_date', [$request->check_in_date, $request->check_out_date])
                      ->orWhereBetween('check_out_date', [$request->check_in_date, $request->check_out_date]);
            })
            ->exists();

        if (!$isRoomAvailable) {
            return response()->json([
                'status' => 'error',
                'message' => 'Room is not available for the selected dates'
            ], 422);
        }

        // Calculate amounts
        $room = Room::find($request->room_id);
        $customer = Customer::with('customerType')->find($request->customer_id);
        
        $duration = Carbon::parse($request->check_in_date)->diffInDays($request->check_out_date);
        $totalAmount = $room->price_per_night * $duration;
        
        // Apply customer type discount
        $discountAmount = 0;
        if ($customer->customerType) {
            $discountAmount = $totalAmount * ($customer->customerType->discount_percentage / 100);
        }
        
        $finalAmount = $totalAmount - $discountAmount;

        // Generate booking number
        $bookingNumber = 'BK' . str_pad(Booking::count() + 1, 6, '0', STR_PAD_LEFT);

        $booking = Booking::create([
            'booking_number' => $bookingNumber,
            'customer_id' => $request->customer_id,
            'room_id' => $request->room_id,
            'check_in_date' => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'number_of_guests' => $request->number_of_guests,
            'total_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'status' => 'pending',
            'special_requests' => $request->special_requests,
            'additional_services' => $request->additional_services
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Booking created successfully',
            'data' => $booking->load(['customer', 'room'])
        ], 201);
    }

    public function show($id)
    {
        $booking = Booking::with(['customer', 'room'])->find($id);

        if (!$booking) {
            return response()->json([
                'status' => 'error',
                'message' => 'Booking not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $booking
        ]);
    }

    public function update(Request $request, $id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'status' => 'error',
                'message' => 'Booking not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'check_in_date' => 'sometimes|required|date|after_or_equal:today',
            'check_out_date' => 'sometimes|required|date|after:check_in_date',
            'number_of_guests' => 'sometimes|required|integer|min:1',
            'special_requests' => 'nullable|string',
            'additional_services' => 'nullable|array',
            'additional_services.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check room availability if dates are being updated
        if ($request->has('check_in_date') || $request->has('check_out_date')) {
            $checkInDate = $request->check_in_date ?? $booking->check_in_date;
            $checkOutDate = $request->check_out_date ?? $booking->check_out_date;

            $isRoomAvailable = !Booking::where('room_id', $booking->room_id)
                ->where('id', '!=', $booking->id)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->where(function($query) use ($checkInDate, $checkOutDate) {
                    $query->whereBetween('check_in_date', [$checkInDate, $checkOutDate])
                          ->orWhereBetween('check_out_date', [$checkInDate, $checkOutDate]);
                })
                ->exists();

            if (!$isRoomAvailable) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Room is not available for the selected dates'
                ], 422);
            }
        }

        $booking->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Booking updated successfully',
            'data' => $booking->load(['customer', 'room'])
        ]);
    }

    public function confirm($id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'status' => 'error',
                'message' => 'Booking not found'
            ], 404);
        }

        if ($booking->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending bookings can be confirmed'
            ], 422);
        }

        $booking->update([
            'status' => 'confirmed',
            'confirmed_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Booking confirmed successfully',
            'data' => $booking->load(['customer', 'room'])
        ]);
    }

    public function checkIn($id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'status' => 'error',
                'message' => 'Booking not found'
            ], 404);
        }

        if ($booking->status !== 'confirmed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only confirmed bookings can be checked in'
            ], 422);
        }

        $booking->update([
            'status' => 'checked_in',
            'checked_in_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Guest checked in successfully',
            'data' => $booking->load(['customer', 'room'])
        ]);
    }

    public function checkOut($id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'status' => 'error',
                'message' => 'Booking not found'
            ], 404);
        }

        if ($booking->status !== 'checked_in') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only checked-in bookings can be checked out'
            ], 422);
        }

        $booking->update([
            'status' => 'checked_out',
            'checked_out_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Guest checked out successfully',
            'data' => $booking->load(['customer', 'room'])
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'status' => 'error',
                'message' => 'Booking not found'
            ], 404);
        }

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending or confirmed bookings can be cancelled'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'cancellation_reason' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->cancellation_reason
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Booking cancelled successfully',
            'data' => $booking->load(['customer', 'room'])
        ]);
    }
}