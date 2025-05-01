<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
class AccountController extends Controller
{
    //lấy danh sách accounts 
    public function index(){
        $accounts = Account::with(['employee', 'authorities'])->paginate(10);
        return response()->json([
            'status' => 'success',
            'data' => $accounts
        ]);
    }
    //thêm mới một tài khoản 
    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:accounts|max:255',
            'password' => 'required|min:6',
            'employee_id' => 'required|exists:employee, id',
            'is_active' => 'boolean'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 'error',
                'error' => $validator->errors()
            ], 422);
        }
        $account = Account::create([
            'username' => $request->input('username'),
            'password' => $request->input('password'), //sẽ được tự ddiingj hash do casts
            'employee_id' => $request->input('employee_id'),
            'is_active' => $request->input('is_active', true), //mặc định là true nếu không có 
            'created_date' => now()
        ]);
        return response()->json([
            'status' => 'success',
            'data' => $account
        ], 201);
    }
    //lấy thông tin chi tiết một tài khoản 
    public function show($id){
        $account = Account::with(['employee', 'authorities'])->find($id);
    
        if (!$account) {
            return response()->json([
                'status' => 'error',
                'message' => 'Account not found'
            ], 404);
        }
    
        return response()->json([
            'status' => 'success',
            'data' => $account
        ]);
    }
    //cập nhật thông tin tài khoản 
    public function update(Request $request, $id){
        $account = Account::find($id);
        if(!$account){
            response()->json([
                'status' => 'error',
                'message' => 'Account not found'
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'username' => ['sometimes', 'required', Rule::unique('accounts')->ignore($id)],
            'password' => 'sometimes|required|exists:employee, id',
            'is_active' => 'boolean'
        ]);
        if($validator->fails()){
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = $request->only(['username', 'employee_id', 'is_active']);
        if($request->has('passowrd')){
            $updateData['password'] = Hash::make($request->password);
        }
        $account->update($updateData);
        return response()->json([
            'status' => 'success',
            "message" => "Account updated successfully",
            'data' => $account
        ]);
    }
    //xóa tài khoản
    public function destroy($id){
        $account = Account::find($id);
        if(!$account){
            return response()->json([
                'status' => 'success',
                'message' => 'Account deleted successfully'
            ]);
        }
    }

    //trạng thái hoạt động hoặc ngừng hoạt động 
    public function toggleStatus($id){
        $account = Account::find($id);
    
        if (!$account) {
            return response()->json([
                'status' => 'error',
                'message' => 'Account not found'
            ], 404);
        }
    
        // Lưu trạng thái cũ để ghi log
        $oldStatus = $account->is_active;
    
        // Đảo trạng thái
        $account->is_active = !$oldStatus;
        $account->save();
    
        // Ghi log
        Log::info("Account status toggled", [
            'account_id' => $account->id,
            'username' => $account->username,
            'old_status' => $oldStatus,
            'new_status' => $account->is_active,
            'timestamp' => now()->toDateTimeString()
        ]);
    
        return response()->json([
            'status' => 'success',
            'message' => 'Account status updated successfully',
            'data' => $account
        ]);
    }
}
