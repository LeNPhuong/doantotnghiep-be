<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Status;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class AdminStatusController extends BaseController
{
    public function index()
    {
        try {
            $Status = Status::all();
            return $this->sendResponse($Status, 'Lấy danh sách trạng thái thành công');
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra. Vui lòng thử lại sau.', ['error' => $th->getMessage()], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $inputSearch = $request->input('query');

            $Status = Status::search($inputSearch)->get();
            if($Status->isEmpty()){
                return $this->sendError('Không tìm thấy trạng thái', [], 404);
            }
            return $this->sendResponse($Status, 'trạng thái tìm thấy');
        } catch (\Throwable $th) {
            return $this->sendError('Đã xảy ra lỗi trong quá trình tìm kiếm trạng thái', ['error' => $th->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        try {
            $unit = Status::findOrFail($id);
            return $this->sendResponse($unit, 'Lấy thông tin trạng thái thành công');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('trạng thái không tồn tại', [], 404);
        }
    }

    public function update(Request $request, $id)
    {

        try {
            $unit = Status::findOrFail($id);
            $validatedData = $request->validate([
                'text_status' => 'required|string|max:191',
                'active' => 'boolean',
            ]);
            $unit->update($validatedData);

            return $this->sendResponse($unit, 'Cập nhật trạng thái thành công');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('trạng thái không tồn tại', [], 404);
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra khi cập nhật trạng thái.', ['error' => $th->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            $unit = Status::findOrFail($id);
            $unit->delete();

            return $this->sendResponse(null, 'Xóa trạng thái thành công');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('trạng thái không tồn tại', [], 404);
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra khi xóa trạng thái.', ['error' => $th->getMessage()], 500);
        }
    }

    public function restore($id)
    {
        try {
            $unit = Status::withTrashed()->findOrFail($id);

            if ($unit->trashed()) {
                $unit->restore();
                return $this->sendResponse($unit, 'Khôi phục trạng thái thành công');
            }

            return $this->sendError('trạng thái không cần khôi phục vì chưa bị xóa.', [], 400);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('trạng thái không tồn tại', [], 404);
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra khi khôi phục trạng thái.', ['error' => $th->getMessage()], 500);
        }
    }

    public function create(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'text_status' => 'required|string|max:191',
                'active' => 'boolean'
            ]);

            $unit = Status::create($validatedData);

            return $this->sendResponse($unit, 'Tạo trạng thái thành công');
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra khi tạo trạng thái.', ['error' => $th->getMessage()], 500);
        }
    }
}
