<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Employee;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::with(['role', 'account']);

        // Filter by role
        if ($request->has('role_id')) {
            $employees->where('role_id', $request->role_id);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $employees->where('is_active', $request->boolean('is_active'));
        }

        // Filter by gender
        if ($request->has('gender')) {
            $employees->where('gender', $request->gender);
        }

        // Filter by age range
        if ($request->has('age_from') && $request->has('age_to')) {
            $fromDate = Carbon::now()->subYears($request->age_to);
            $toDate = Carbon::now()->subYears($request->age_from);
            $employees->whereBetween('dob', [$fromDate, $toDate]);
        }

        // Filter by created date range
        if ($request->has('date_from') && $request->has('date_to')) {
            $employees->whereBetween('created_date', [
                Carbon::parse($request->date_from)->startOfDay(),
                Carbon::parse($request->date_to)->endOfDay()
            ]);
        }

        // Advanced search
        if ($request->has('search')) {
            $search = $request->search;
            $employees->where(function ($query) use ($search) {
                $query->where('fullname', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('citizen_identity', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort_by', 'created_date');
        $sortDirection = $request->get('sort_direction', 'desc');
        $allowedSortFields = ['fullname', 'email', 'created_date', 'is_active'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $employees->orderBy($sortField, $sortDirection);
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        
        return response()->json([
            'status' => 'success',
            'data' => $employees->paginate($perPage)
        ]);
    }

    // private function handleAvatarUpload($file)
    // {
    //     if (!$file->isValid()) {
    //         throw new \Exception("Uploaded file is invalid.");
    //     }
    
    //     $image = Image::make($file);
    //     $filename = time() . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
    
    //     if ($image->width() > 800 || $image->height() > 800) {
    //         $image->resize(800, 800, function ($constraint) {
    //             $constraint->aspectRatio();
    //             $constraint->upsize();
    //         });
    //     }
    
    //     $path = 'avatars/' . $filename;
    //     Storage::disk('public')->put($path, (string) $image->encode());
    
    //     return Storage::url($path); // Trả về URL truy cập được
    // }

    private function handleAvatarUpload($file)
    {
        if (!$file->isValid()) {
            throw new \Exception("Uploaded file is invalid.");
        }

        // Tạo tên file duy nhất
        $filename = time() . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension();

        // Đường dẫn lưu trong disk 'public'
        $path = 'avatars/' . $filename;

        // Lưu file gốc không resize
        Storage::disk('public')->putFileAs('avatars', $file, $filename);

        // Trả về URL công khai
        return Storage::url($path);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'citizen_identity' => 'required|unique:employees|max:20',
            'fullname' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'dob' => 'required|date|before:today',
            'email' => 'required|email|unique:employees',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048|dimensions:min_width=100,min_height=100',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except('avatar');
        
        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->handleAvatarUpload($request->file('avatar'));
        }

        $employee = Employee::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Employee created successfully',
            'data' => $employee->load('role')
        ], 201);
    }

    public function show($id)
    {
        $employee = Employee::with(['role', 'account', 'bookings', 'invoices'])->find($id);
        
        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $employee
        ]);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'citizen_identity' => ['sometimes', 'required', Rule::unique('employees')->ignore($id)],
            'fullname' => 'sometimes|required|string|max:255',
            'gender' => 'sometimes|required|in:male,female,other',
            'dob' => 'sometimes|required|date|before:today',
            'email' => ['sometimes', 'required', 'email', Rule::unique('employees')->ignore($id)],
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048|dimensions:min_width=100,min_height=100',
            'role_id' => 'sometimes|required|exists:roles,id',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except('avatar');

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($employee->avatar) {
                Storage::disk('public')->delete($employee->avatar);
            }
            
            $data['avatar'] = $this->handleAvatarUpload($request->file('avatar'));
        }

        $employee->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Employee updated successfully',
            'data' => $employee->load('role')
        ]);
    }

    public function destroy($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee not found'
            ], 404);
        }

        // Check if employee has related records
        if ($employee->bookings()->exists() || $employee->invoices()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete employee with existing records'
            ], 422);
        }

        // Delete avatar if exists
        if ($employee->avatar) {
            Storage::disk('public')->delete($employee->avatar);
        }

        $employee->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Employee deleted successfully'
        ]);
    }

    public function toggleStatus($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee not found'
            ], 404);
        }

        $employee->is_active = !$employee->is_active;
        $employee->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Employee status updated successfully',
            'data' => $employee
        ]);
    }

    public function removeAvatar($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee not found'
            ], 404);
        }

        if ($employee->avatar) {
            Storage::disk('public')->delete($employee->avatar);
            $employee->update(['avatar' => null]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Avatar removed successfully'
        ]);
    }
} 