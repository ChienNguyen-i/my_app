<?php

namespace App\Http\Controllers\Api;

use App\Exports\CategoryExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\CategoryRequest;
use App\Imports\CategoryImport;
use App\Models\Category;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class CategoryController extends BaseController
{
    protected $columns_select = ['id', 'name', 'url', 'parent_id', 'order', 'icon', 'type', 'status',];
    protected $columns_search = ['id', 'name', 'url', 'order', 'icon'];

    /**
     * Display a listing of the resource.
     */
    public function index(CategoryRequest $request)
    {
        $limit = $request->limit ?? $this->limit_pagination;
        $page = $request->page ?? 1;
        $offset = ($page - 1) * $limit;
        $query = Category::select($this->columns_select);
        $this->applyFilters($query, $request->filter);
        if ($request->filled('search')) {
            $this->applySearch($query, $request->search, $this->columns_search);
        }
        $this->applyOrderBy($query, $request->orderBy);
        $total = $query->count();
        list($from, $to) = $this->calculatePagination($total, $limit, $page);
        $data = $query->offset($offset)->limit($limit)->get();
        return $this->responsesService->pagination(200, __('messages.success'), $data, $from, $to, $page, $limit, $total);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryRequest $request)
    {
        $data = $request->all();
        $category = Category::create($data);
        if (!$category) {
            return $this->responsesService->error(400, __('messages.add_failed'));
        }
        return $this->responsesService->success(200, __('messages.add_successful'), $category);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $data = Category::findOrFail($id);
            return $this->responsesService->success(200, __('messages.success'), $data);
        } catch (ModelNotFoundException $e) {
            return $this->responsesService->error(400, __('messages.not_found'), $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryRequest $request)
    {
        try {
            $category = Category::findOrFail($request->id);
            $data = $request->all();
            $category->update($data);
            return $this->responsesService->success(200, __('messages.update_successful'), $category);
        } catch (ModelNotFoundException $e) {
            return $this->responsesService->error(400, __('messages.not_found'), $e->getMessage());
        } catch (Exception $e) {
            return $this->responsesService->error(400, __('messages.update_failed'), $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CategoryRequest $request)
    {
        if (empty($request->ids)) {
            return $this->responsesService->error(400, __('messages.no_ids_provided'));
        }
        $data = ['deleted_at' => Carbon::now(), 'deleted_by' => Auth::user()->id];
        $result = Category::whereIn('id', $request->ids)->update($data);
        if ($result) {
            return $this->responsesService->success(200, __('messages.delete_successful'));
        }
        return $this->responsesService->error(400, __('messages.delete_failed'));
    }

    public function restore(CategoryRequest $request)
    {
        if (empty($request->ids)) {
            return $this->responsesService->error(400, __('messages.no_ids_provided'));
        }
        $data = ['deleted_at' => null, 'deleted_by' => null];
        $result = Category::onlyTrashed()->whereIn('id', $request->ids)->update($data);
        if ($result) {
            return $this->responsesService->success(200, __('messages.restore_successful'));
        }
        return $this->responsesService->error(400, __('messages.restore_failed'));
    }

    public function deleteCompletely(CategoryRequest $request)
    {
        if (empty($request->ids)) {
            return $this->responsesService->error(400, __('messages.no_ids_provided'));
        }
        $categories = Category::onlyTrashed()->whereIn('id', $request->ids)->get();
        if ($categories->isEmpty()) {
            return $this->responsesService->error(400, __('messages.not_found'));
        }
        $result = Category::onlyTrashed()->whereIn('id', $request->ids)->forceDelete(); 
        if ($result) {
            return $this->responsesService->success(200, __('messages.delete_successful'));
        }
        return $this->responsesService->error(400, __('messages.delete_failed'));
    }

    public function trashed(CategoryRequest $request)
    {
        $limit = $request->limit ?? $this->limit_pagination;
        $page = $request->page ?? 1;
        $offset = ($page - 1) * $limit;
        $query = Category::onlyTrashed();
        $this->applyFilters($query, $request->filter);
        if ($request->filled('search')) {
            $this->applySearch($query, $request->search, $this->columns_search);
        }
        $this->applyOrderBy($query, $request->orderBy);
        $total = $query->count();
        list($from, $to) = $this->calculatePagination($total, $limit, $page);
        $data = $query->offset($offset)->limit($limit)->get();
        return $this->responsesService->pagination(200, __('messages.success'), $data, $from, $to, $page, $limit, $total);
    }

    // public function importExcel(CategoryRequest $request)
    // {
    //     DB::beginTransaction();

    //     try {
    //         // set_time_limit(0);
    //         ini_set('max_execution_time', 1800);
    //         // $this->manageFilesService->uploadFile($request, 'file', 'menus/imports');
    //         Excel::import(new MenusImport, $request->file('file'));

    //         DB::commit();

    //         return $this->responsesService->success(200, __('messages.import_successful'));
    //     } catch (Throwable $th) {
    //         Log::error('Import failed: ' . $th->getMessage());
    //         DB::rollBack();

    //         return $this->responsesService->error(400, __('messages.import_failed'), $th->getMessage());
    //     }
    // }

    // public function exportExcel(CategoryRequest $request)
    // {
    //     try {
    //         $menus = $this->getDataByInputNotPagination($request);

    //         return Excel::download(new MenusExport($menus->get()), 'menus_' . date('YmdHis') . '.xlsx');
    //     } catch (Throwable $th) {
    //         Log::error('Export failed: ' . $th->getMessage());

    //         return $this->responsesService->error(400, __('messages.export_failed'));
    //     }
    // }

    // public function exportPDF(CategoryRequest $request)
    // {
    //     try {
    //         $menus = $this->getDataByInputNotPagination($request)->get();
    //         $pdfData = [
    //             'title' => 'Welcome to my app',
    //             'date' => date('d/m/Y'),
    //             'menus' => $menus
    //         ];
    //         $pdf = PDF::loadView('pdf.menus',  $pdfData)->setPaper('a4', 'portrait');

    //         return $pdf->download('menus_' . date('YmdHis') . '.pdf');
    //     } catch (Throwable $th) {
    //         Log::error('Export failed: ' . $th->getMessage());

    //         return $this->responsesService->error(400, __('messages.export_failed'));
    //     }
    // }

    // public function viewPDF(CategoryRequest $request)
    // {
    //     try {
    //         $menus = $this->getDataByInputNotPagination($request)->get();
    //         $pdfData = [
    //             'title' => 'Welcome to my app',
    //             'date' => date('d/m/Y'),
    //             'menus' => $menus
    //         ];
    //         $pdf = PDF::loadView('pdf.menus', $pdfData)->setPaper('a4', 'portrait');

    //         return $pdf->stream();
    //     } catch (Throwable $th) {
    //         Log::error('View PDF failed: ' . $th->getMessage());

    //         return $this->responsesService->error(400, __('messages.export_failed'));
    //     }
    // }

    public function getTreeCategory()
    {
        $menus = Category::with('children')->whereNull('parent_id')->orderBy('order')->get();
        return $this->responsesService->success(200, __('messages.success'), $menus);
    }
}
