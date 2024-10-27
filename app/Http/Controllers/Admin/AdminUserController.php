<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends BaseController
{
    public function index() {
        try {
            $users = User::all();
            if($users->isEmpty()) {
                return $this->sendResponse($users, 'Chưa có người dùng');
            }
            return $this->sendResponse($users, 'Lấy người dùng thành công');
        } catch (\Throwable $th) {
            return $this->sendError('Lỗi định dạng.', ['error' => $th->getMessage()], 404);
        }
    }
}
