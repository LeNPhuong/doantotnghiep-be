<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Unit;
use Illuminate\Contracts\Support\ValidatedData;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AdminUnitsController extends BaseController
{
    public function index()
    {
        try {
            $units = Unit::all();
            return $this->sendResponse($units, 'Lấy danh sách đơn vị thành công');
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra. Vui lòng thử lại sau.', ['error' => $th->getMessage()], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $inputSearch = $request->input('query');

            $Units = Unit::search($inputSearch)->get();

            return $this->sendResponse($Units, 'Đơn vị tìm thấy');
        } catch (\Throwable $th) {
            return $this->sendError('Đã xảy ra lỗi trong quá trình tìm kiếm Đơn vị', ['error' => $th->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        try {
            $unit = Unit::findOrFail($id);
            return $this->sendResponse($unit, 'Lấy thông tin đơn vị thành công');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Đơn vị không tồn tại', [], 404);
        }
    }

    public function update(Request $request, $id)
    {

        try {
            $unit = Unit::findOrFail($id);
            $validatedData = $request->validate([
                'name' => 'required|string|max:191',
                'active' => 'boolean',
            ]);
            $unit->update($validatedData);

            return $this->sendResponse($unit, 'Cập nhật đơn vị thành công');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Đơn vị không tồn tại', [], 404);
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra khi cập nhật đơn vị.', ['error' => $th->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            $unit = Unit::findOrFail($id);
            $unit->delete();

            return $this->sendResponse(null, 'Xóa đơn vị thành công');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Đơn vị không tồn tại', [], 404);
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra khi xóa đơn vị.', ['error' => $th->getMessage()], 500);
        }
    }

    public function restore($id)
    {
        try {
            $unit = Unit::withTrashed()->findOrFail($id);

            if ($unit->trashed()) {
                $unit->restore();
                return $this->sendResponse($unit, 'Khôi phục đơn vị thành công');
            }

            return $this->sendError('Đơn vị không cần khôi phục vì chưa bị xóa.', [], 400);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Đơn vị không tồn tại', [], 404);
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra khi khôi phục đơn vị.', ['error' => $th->getMessage()], 500);
        }
    }

    public function create(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:191',
                'description' => 'nullable|string|max:500'
            ]);

            $unit = Unit::create($validatedData);

            return $this->sendResponse($unit, 'Tạo đơn vị thành công');
        } catch (\Exception $th) {
            return $this->sendError('Có lỗi xảy ra khi tạo đơn vị.', ['error' => $th->getMessage()], 500);
        }
    }
}
