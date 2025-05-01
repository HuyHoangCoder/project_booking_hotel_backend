<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\Authority;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AuthorityController extends Controller
{
    //lấy danh sách các quyền hạn, có thể lọc theo username, phân trang 
    public function index(Request $request){
        $authorities = Authority::with('account');
        if($request->has('username')){
            $authorities->where('username', $request->username);
        }
        return response()->json([
            'status' => 'success',
            'data' => $authorities->paginate(10)
        ]);
    }
    //thêm mới quyền 
    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'username' => 'required|exists:accounts, username',
            'permission' => 'required|string',
            'details' => 'nullable|string'
        ]);
        if($validator->failed()){
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        try {
            $authority = Authority::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Authority assigned successfully',
                'data' => $authority
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'This permission is already assigned to this user'
            ], 422);
        }
    }
    //lấy thông tin chi tiết 1 quyền 
    public function show($id)
    {
        $authority = Authority::with('account')->find($id);
        
        if (!$authority) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authority not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $authority
        ]);
    }
    //cập nhật thông tin quyền 
    public function update(Request $request, $id)
    {
        $authority = Authority::find($id);

        if (!$authority) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authority not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'details' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $authority->update($request->only(['details']));

        return response()->json([
            'status' => 'success',
            'message' => 'Authority updated successfully',
            'data' => $authority
        ]);
    }
    //xóa thông tin quyền 
    public function destroy($id)
    {
        $authority = Authority::find($id);

        if (!$authority) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authority not found'
            ], 404);
        }

        $authority->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Authority removed successfully'
        ]);
    }
    //lấy danh sách quyền 
    public function getUserPermissions($username)
    {
        $account = Account::where('username', $username)->first();
        
        if (!$account) {
            return response()->json([
                'status' => 'error',
                'message' => 'Account not found'
            ], 404);
        }

        $permissions = Authority::where('username', $username)->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'account' => $account,
                'permissions' => $permissions
            ]
        ]);
    }
} 
