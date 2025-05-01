<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookingHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = BookingHistory::with(['booking', 'employee']);

        // Filter by booking
        if ($request->has('booking_id')) {
            $query->where('booking_id', $request->booking_id);
        }

        // Filter by employee
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by action
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        }

        // Search in notes
        if ($request->has('search')) {
            $query->where('notes', 'like', "%{$request->search}%");
        }

        // Sort results
        $sortField = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $histories = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $histories
        ]);
    }

    public function show($id)
    {
        $history = BookingHistory::with(['booking', 'employee'])->find($id);

        if (!$history) {
            return response()->json([
                'status' => 'error',
                'message' => 'History record not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $history
        ]);
    }

    public function getBookingHistory($bookingId)
    {
        $histories = BookingHistory::with(['employee'])
            ->where('booking_id', $bookingId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $histories
        ]);
    }

    public function getEmployeeHistory($employeeId)
    {
        $histories = BookingHistory::with(['booking'])
            ->where('employee_id', $employeeId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $histories
        ]);
    }
}