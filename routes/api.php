<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\FloorController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\RoomTypeController;
use App\Http\Controllers\Api\AuthorityController;
use App\Http\Controllers\Api\CustomerTypeController;
use App\Http\Controllers\Api\CustomerGroupController;
use App\Http\Controllers\Api\BookingHistoryController;
use App\Http\Controllers\Api\RoomStatusController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\InvoiceDetailController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\VoucherController;
use App\Http\Controllers\Api\ServiceDetailController;
use App\Http\Controllers\Api\ServiceTypeController;
use App\Http\Controllers\Api\InventoryReceivingController;
use App\Http\Controllers\Api\InventoryDeliveryController;
use App\Http\Controllers\Api\LostItemController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Account Management Routes
Route::prefix('accounts')->group(function () {
    Route::get('/', [AccountController::class, 'index']);
    Route::post('/', [AccountController::class, 'store']);
    Route::get('/{id}', [AccountController::class, 'show']);
    Route::put('/{id}', [AccountController::class, 'update']);
    Route::delete('/{id}', [AccountController::class, 'destroy']);
    Route::patch('/{id}/toggle-status', [AccountController::class, 'toggleStatus']);
});


// Authority Management Routes
Route::prefix('authorities')->group(function () {
    Route::get('/', [AuthorityController::class, 'index']);
    Route::post('/', [AuthorityController::class, 'store']);
    Route::get('/{id}', [AuthorityController::class, 'show']);
    Route::put('/{id}', [AuthorityController::class, 'update']);
    Route::delete('/{id}', [AuthorityController::class, 'destroy']);
    Route::get('/user/{username}', [AuthorityController::class, 'getUserPermissions']);
});


// Employee Management Routes
Route::prefix('employees')->group(function () {
    Route::get('/', [EmployeeController::class, 'index']);
    Route::post('/', [EmployeeController::class, 'store']);
    Route::get('/{id}', [EmployeeController::class, 'show']);
    Route::post('/{id}', [EmployeeController::class, 'update']);
    Route::delete('/{id}', [EmployeeController::class, 'destroy']);
    Route::patch('/{id}/toggle-status', [EmployeeController::class, 'toggleStatus']);
});


// Customer Management Routes
Route::prefix('customers')->group(function () {
    Route::get('/', [CustomerController::class, 'index']);
    Route::post('/', [CustomerController::class, 'store']);
    Route::get('/{id}', [CustomerController::class, 'show']);
    Route::put('/{id}', [CustomerController::class, 'update']);
    Route::delete('/{id}', [CustomerController::class, 'destroy']);
    // Group management
    Route::post('/{id}/groups', [CustomerController::class, 'addToGroup']);
    Route::delete('/{id}/groups', [CustomerController::class, 'removeFromGroup']);
    // Lost items management
    Route::post('/{id}/lost-items', [CustomerController::class, 'reportLostItem']);
    Route::put('/{id}/lost-items/{itemId}', [CustomerController::class, 'updateLostItemStatus']);
});


// Customer Type routes
Route::prefix('customer-types')->group(function () {
    Route::get('/', [CustomerTypeController::class, 'index']);
    Route::post('/', [CustomerTypeController::class, 'store']);
    Route::get('/{id}', [CustomerTypeController::class, 'show']);
    Route::put('/{id}', [CustomerTypeController::class, 'update']);
    Route::delete('/{id}', [CustomerTypeController::class, 'destroy']);
    Route::patch('/{id}/toggle-status', [CustomerTypeController::class, 'toggleStatus']);
    Route::get('/eligible-types', [CustomerTypeController::class, 'getEligibleTypes']);
}); 



// Customer Group routes
Route::prefix('customer-groups')->group(function () {
    Route::get('/', [CustomerGroupController::class, 'index']);
    Route::post('/', [CustomerGroupController::class, 'store']);
    Route::get('/{id}', [CustomerGroupController::class, 'show']);
    Route::put('/{id}', [CustomerGroupController::class, 'update']);
    Route::delete('/{id}', [CustomerGroupController::class, 'destroy']);
    Route::patch('/{id}/toggle-status', [CustomerGroupController::class, 'toggleStatus']);
    
    // Group member management
    Route::post('/{id}/members', [CustomerGroupController::class, 'addMember']);
    Route::delete('/{id}/members', [CustomerGroupController::class, 'removeMember']);
    Route::put('/{id}/members', [CustomerGroupController::class, 'updateMember']);
}); 



// Booking routes
Route::prefix('bookings')->group(function () {
    Route::get('/', [BookingController::class, 'index']);
    Route::post('/', [BookingController::class, 'store']);
    Route::get('/{id}', [BookingController::class, 'show']);
    Route::put('/{id}', [BookingController::class, 'update']);
    
    // Booking status management
    Route::post('/{id}/confirm', [BookingController::class, 'confirm']);
    Route::post('/{id}/check-in', [BookingController::class, 'checkIn']);
    Route::post('/{id}/check-out', [BookingController::class, 'checkOut']);
    Route::post('/{id}/cancel', [BookingController::class, 'cancel']);
}); 


// Booking History Routes
Route::get('/booking-histories', [BookingHistoryController::class, 'index']);
Route::get('/booking-histories/{id}', [BookingHistoryController::class, 'show']);
Route::get('/bookings/{bookingId}/history', [BookingHistoryController::class, 'getBookingHistory']);
Route::get('/employees/{employeeId}/booking-history', [BookingHistoryController::class, 'getEmployeeHistory']); 



// Room Management Routes
Route::prefix('rooms')->group(function () {
    Route::get('/', [RoomController::class, 'index']);
    Route::post('/', [RoomController::class, 'store']);
    Route::get('/{id}', [RoomController::class, 'show']);
    Route::put('/{id}', [RoomController::class, 'update']);
    Route::delete('/{id}', [RoomController::class, 'destroy']);
    Route::patch('/{id}/status', [RoomController::class, 'updateStatus']);
});

// Room Type Routes
Route::get('/room-types', [RoomTypeController::class, 'index']);
Route::post('/room-types', [RoomTypeController::class, 'store']);
Route::get('/room-types/{id}', [RoomTypeController::class, 'show']);
Route::put('/room-types/{id}', [RoomTypeController::class, 'update']);
Route::delete('/room-types/{id}', [RoomTypeController::class, 'destroy']);
Route::patch('/room-types/{id}/toggle-status', [RoomTypeController::class, 'toggleStatus']);
Route::get('/room-types/{id}/stats', [RoomTypeController::class, 'getStats']);
Route::get('/room-types/available', [RoomTypeController::class, 'getAvailableTypes']);


// Floor Routes
Route::prefix('floors')->group(function () {
    Route::get('/', [FloorController::class, 'index']);
    Route::post('/', [FloorController::class, 'store']);
    Route::get('/{id}', [FloorController::class, 'show']);
    Route::put('/{id}', [FloorController::class, 'update']);
    Route::delete('/{id}', [FloorController::class, 'destroy']);
    Route::patch('/{id}/toggle-status', [FloorController::class, 'toggleStatus']);
    Route::get('/{id}/stats', [FloorController::class, 'getFloorStats']);
});


// Room Status Routes
Route::get('/room-statuses', [RoomStatusController::class, 'index']);
Route::post('/room-statuses', [RoomStatusController::class, 'store']);
Route::get('/room-statuses/{id}', [RoomStatusController::class, 'show']);
Route::put('/room-statuses/{id}', [RoomStatusController::class, 'update']);
Route::delete('/room-statuses/{id}', [RoomStatusController::class, 'destroy']);
Route::patch('/room-statuses/{id}/toggle-status', [RoomStatusController::class, 'toggleStatus']);
Route::get('/room-statuses/stats', [RoomStatusController::class, 'getStatusStats']); 



// Invoice Management Routes
Route::prefix('invoices')->group(function () {
    Route::get('/', [InvoiceController::class, 'index']);
    Route::post('/', [InvoiceController::class, 'store']);
    Route::get('/stats', [InvoiceController::class, 'getStats']);
    Route::get('/{id}', [InvoiceController::class, 'show']);
    Route::put('/{id}', [InvoiceController::class, 'update']);
    Route::post('/{id}/payments', [InvoiceController::class, 'addPayment']);
    Route::post('/{id}/cancel', [InvoiceController::class, 'cancel']);
    Route::post('/{id}/refund', [InvoiceController::class, 'refund']);
}); 


// Invoice Details Routes
Route::prefix('invoices/{invoiceId}/details')->group(function () {
    Route::get('/', [InvoiceDetailController::class, 'index']);
    Route::post('/', [InvoiceDetailController::class, 'store']);
    Route::get('/stats', [InvoiceDetailController::class, 'getStats']);
    Route::get('/{id}', [InvoiceDetailController::class, 'show']);
    Route::put('/{id}', [InvoiceDetailController::class, 'update']);
    Route::delete('/{id}', [InvoiceDetailController::class, 'destroy']);
    Route::patch('/{id}/toggle-status', [InvoiceDetailController::class, 'toggleStatus']);
}); 


// Payment Routes
Route::prefix('payments')->group(function () {
    Route::get('/', [PaymentController::class, 'index']);
    Route::post('/', [PaymentController::class, 'store']);
    Route::get('/stats', [PaymentController::class, 'getStats']);
    Route::get('/{id}', [PaymentController::class, 'show']);
    Route::put('/{id}', [PaymentController::class, 'update']);
    Route::patch('/{id}/complete', [PaymentController::class, 'complete']);
    Route::patch('/{id}/fail', [PaymentController::class, 'fail']);
    Route::post('/{id}/refund', [PaymentController::class, 'refund']);
}); 



// Voucher Routes
Route::prefix('vouchers')->group(function () {
    Route::get('/', [VoucherController::class, 'index']);
    Route::post('/', [VoucherController::class, 'store']);
    Route::get('/stats', [VoucherController::class, 'getStats']);
    Route::get('/{id}', [VoucherController::class, 'show']);
    Route::put('/{id}', [VoucherController::class, 'update']);
    Route::delete('/{id}', [VoucherController::class, 'destroy']);
    Route::patch('/{id}/toggle-status', [VoucherController::class, 'toggleStatus']);
    Route::post('/validate', [VoucherController::class, 'validateVoucher']);
}); 



// Service Detail Routes
Route::prefix('service-details')->group(function () {
    Route::get('/', [ServiceDetailController::class, 'index']);
    Route::post('/', [ServiceDetailController::class, 'store']);
    Route::get('/stats', [ServiceDetailController::class, 'getStats']);
    Route::get('/{id}', [ServiceDetailController::class, 'show']);
    Route::put('/{id}', [ServiceDetailController::class, 'update']);
    Route::delete('/{id}', [ServiceDetailController::class, 'destroy']);
    Route::patch('/{id}/toggle-status', [ServiceDetailController::class, 'toggleStatus']);
    Route::post('/{id}/calculate-price', [ServiceDetailController::class, 'calculatePrice']);
    Route::get('/{id}/time-slots', [ServiceDetailController::class, 'getTimeSlots']);
}); 


// Service Type Routes
Route::prefix('service-types')->group(function () {
    Route::get('/', [ServiceTypeController::class, 'index']);
    Route::post('/', [ServiceTypeController::class, 'store']);
    Route::get('/stats', [ServiceTypeController::class, 'getStats']);
    Route::get('/{id}', [ServiceTypeController::class, 'show']);
    Route::put('/{id}', [ServiceTypeController::class, 'update']);
    Route::delete('/{id}', [ServiceTypeController::class, 'destroy']);
    Route::patch('/{id}/toggle-status', [ServiceTypeController::class, 'toggleStatus']);
    Route::patch('/{id}/display-order', [ServiceTypeController::class, 'updateDisplayOrder']);
    Route::get('/{id}/services', [ServiceTypeController::class, 'getServices']);
    Route::get('/{id}/active-services', [ServiceTypeController::class, 'getActiveServices']);
}); 


// Inventory Receiving Routes
Route::prefix('inventory-receivings')->group(function () {
    Route::get('/', [InventoryReceivingController::class, 'index']);
    Route::post('/', [InventoryReceivingController::class, 'store']);
    Route::get('/stats', [InventoryReceivingController::class, 'getStats']);
    Route::get('/{id}', [InventoryReceivingController::class, 'show']);
    Route::put('/{id}', [InventoryReceivingController::class, 'update']);
    Route::delete('/{id}', [InventoryReceivingController::class, 'destroy']);
    Route::patch('/{id}/mark-received', [InventoryReceivingController::class, 'markAsReceived']);
    Route::patch('/{id}/cancel', [InventoryReceivingController::class, 'cancel']);
});


// Inventory Delivery Routes
Route::prefix('inventory-deliveries')->group(function () {
    Route::get('/', [InventoryDeliveryController::class, 'index']);
    Route::post('/', [InventoryDeliveryController::class, 'store']);
    Route::get('/stats', [InventoryDeliveryController::class, 'getStats']);
    Route::get('/{id}', [InventoryDeliveryController::class, 'show']);
    Route::put('/{id}', [InventoryDeliveryController::class, 'update']);
    Route::delete('/{id}', [InventoryDeliveryController::class, 'destroy']);
    Route::patch('/{id}/mark-delivered', [InventoryDeliveryController::class, 'markAsDelivered']);
    Route::patch('/{id}/cancel', [InventoryDeliveryController::class, 'cancel']);
}); 




// Lost Items Management
Route::prefix('lost-items')->group(function () {
    Route::get('/', [LostItemController::class, 'index']);
    Route::post('/', [LostItemController::class, 'store']);
    Route::get('/stats', [LostItemController::class, 'getStats']);
    Route::get('/{id}', [LostItemController::class, 'show']);
    Route::put('/{id}', [LostItemController::class, 'update']);
    Route::delete('/{id}', [LostItemController::class, 'destroy']);
    Route::patch('/{id}/mark-claimed', [LostItemController::class, 'markAsClaimed']);
    Route::patch('/{id}/mark-disposed', [LostItemController::class, 'markAsDisposed']);
}); 