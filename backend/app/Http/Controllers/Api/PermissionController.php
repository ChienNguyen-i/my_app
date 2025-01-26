<?php

namespace App\Http\Controllers\Api;

use App\Exports\PermissionsExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\PermissionRequest;
use App\Imports\PermissionsImport;
use App\Models\Permission;
use App\Models\Role;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Throwable;

class PermissionController extends BaseController
{
    protected $columns_select = ['id', 'name', 'key_code', 'parent_id', 'order'];
    protected $columns_search = ['id', 'name', 'key_code', 'order'];

    /**
     * Display a listing of the resource.
     */
    public function index(PermissionRequest $request)
    {
        $limit = $request->limit ?? $this->limit_pagination;
        $page = $request->page ?? 1;
        $offset = ($page - 1) * $limit;
        $query = Permission::select($this->columns_select);
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
    public function store(PermissionRequest $request)
    {
        $data = $request->all();
        $permission = Permission::create($data);
        if (!$permission) {
            return $this->responsesService->error(400, __('messages.add_failed'));
        }
        return $this->responsesService->success(200, __('messages.add_successful'), $permission);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $data = Permission::findOrFail($id);
            return $this->responsesService->success(200, __('messages.success'), $data);
        } catch (ModelNotFoundException $e) {
            return $this->responsesService->error(400, __('messages.not_found'), $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PermissionRequest $request)
    {
        try {
            $permission = Permission::findOrFail($request->id);
            $data = $request->all();
            if ($request->key_code !== $permission->key_code) {
                if (Permission::where('key_code', $request->key_code)->exists()) {
                    return $this->responsesService->error(400, __('messages.validation_failed'), __('messages.key_code_unique'));
                }
                $data['key_code'] = $request->key_code;
            }
            $permission->update($data);
            return $this->responsesService->success(200, __('messages.update_successful'), $permission);
        } catch (ModelNotFoundException $e) {
            return $this->responsesService->error(400, __('messages.not_found'), $e->getMessage());
        } catch (Exception $e) {
            return $this->responsesService->error(400, __('messages.update_failed'), $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PermissionRequest $request)
    {
        if (empty($request->ids)) {
            return $this->responsesService->error(400, __('messages.no_ids_provided'));
        }
        $data = ['deleted_at' => Carbon::now(), 'deleted_by' => Auth::user()->id];
        $result = Permission::whereIn('id', $request->ids)->update($data);
        if ($result) {
            return $this->responsesService->success(200, __('messages.delete_successful'));
        }
        return $this->responsesService->error(400, __('messages.delete_failed'));
    }

    public function restore(PermissionRequest $request)
    {
        if (empty($request->ids)) {
            return $this->responsesService->error(400, __('messages.no_ids_provided'));
        }
        $data = ['deleted_at' => null, 'deleted_by' => null];
        $result = Permission::onlyTrashed()->whereIn('id', $request->ids)->update($data);
        if ($result) {
            return $this->responsesService->success(200, __('messages.restore_successful'));
        }
        return $this->responsesService->error(400, __('messages.restore_failed'));
    }

    public function deleteCompletely(PermissionRequest $request)
    {
        if (empty($request->ids)) {
            return $this->responsesService->error(400, __('messages.no_ids_provided'));
        }
        $permissions = Permission::onlyTrashed()->whereIn('id', $request->ids)->get();
        if ($permissions->isEmpty()) {
            return $this->responsesService->error(400, __('messages.not_found'));
        }
        $result = Permission::onlyTrashed()->whereIn('id', $request->ids)->forceDelete();
        if ($result) {
            return $this->responsesService->success(200, __('messages.delete_successful'));
        }
        return $this->responsesService->error(400, __('messages.delete_failed'));
    }

    public function trashed(PermissionRequest $request)
    {
        $limit = $request->limit ?? $this->limit_pagination;
        $page = $request->page ?? 1;
        $offset = ($page - 1) * $limit;
        $query = Permission::onlyTrashed();
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

    // public function importExcel(PermissionRequest $request)
    // {
    //     DB::beginTransaction();

    //     try {
    //         ini_set('max_execution_time', 1800);
    //         $this->manageFilesService->uploadFile($request, 'file', 'permissions/imports');
    //         Excel::import(new PermissionsImport, $request->file('file'));

    //         DB::commit();

    //         return $this->responsesService->success(200, __('messages.import_successful'));
    //     } catch (Throwable $th) {
    //         Log::error('Import failed: ' . $th->getMessage());
    //         DB::rollBack();

    //         return $this->responsesService->error(400, __('messages.import_failed'));
    //     }
    // }

    // public function exportExcel(PermissionRequest $request)
    // {
    //     try {
    //         $permissions = $this->getDataByInputNotPagination($request);

    //         return Excel::download(new PermissionsExport($permissions->get()), 'permissions_' . date('YmdHis') . '.xlsx');
    //     } catch (Throwable $th) {
    //         Log::error('Export failed: ' . $th->getMessage());

    //         return $this->responsesService->error(400, __('messages.export_failed'));
    //     }
    // }

    // public function exportPDF(PermissionRequest $request)
    // {
    //     try {
    //         $permissions = $this->getDataByInputNotPagination($request)->get();
    //         $pdfData = [
    //             'title' => 'Welcome to my app',
    //             'date' => date('d/m/Y'),
    //             'permissions' => $permissions
    //         ];
    //         $pdf = PDF::loadView('pdf.permissions',  $pdfData)->setPaper('a4', 'portrait');

    //         return $pdf->download('permissions_' . date('YmdHis') . '.pdf');
    //     } catch (Throwable $th) {
    //         Log::error('Export failed: ' . $th->getMessage());

    //         return $this->responsesService->error(400, __('messages.export_failed'));
    //     }
    // }

    // public function viewPDF(PermissionRequest $request)
    // {
    //     try {
    //         $permissions = $this->getDataByInputNotPagination($request)->get();
    //         $pdfData = [
    //             'title' => 'Welcome to my app',
    //             'date' => date('d/m/Y'),
    //             'permissions' => $permissions
    //         ];
    //         $pdf = PDF::loadView('pdf.permissions', $pdfData)->setPaper('a4', 'portrait');

    //         return $pdf->stream();
    //     } catch (Throwable $th) {
    //         Log::error('View PDF failed: ' . $th->getMessage());

    //         return $this->responsesService->error(400, __('messages.export_failed'));
    //     }
    // }

    public function getTree()
    {
        $permissions = Permission::with('children')->whereNull('parent_id')->orderBy('order')->get();
        return $this->responsesService->success(200, __('messages.success'), $permissions);
    }

    public function assignPermission(PermissionRequest $request)
    {
        DB::beginTransaction();
        try {
            $roles = Role::find($request->role_ids);
            if ($roles->isEmpty()) {
                return $this->responsesService->error(400, __('messages.role_not_found'));
            }
            foreach ($roles as $role) {
                $role->permissions()->syncWithoutDetaching($request->permission_ids);
            }
            DB::commit();
            return $this->responsesService->success(200, __('messages.permission_assign_successful'));
        } catch (Exception $e) {
            DB::rollBack();
            return $this->responsesService->error(400, __('messages.permission_assign_failed'), $e->getMessage());
        }
    }

    public function revokePermission(PermissionRequest $request)
    {
        DB::beginTransaction();
        try {
            $roles = Role::whereIn('id', $request->role_ids)->get();
            foreach ($roles as $role) {
                $role->permissions()->detach($request->permission_ids);
            }
            DB::commit();
            return $this->responsesService->success(200, __('messages.permission_revoke_successful'));
        } catch (Exception $e) {
            return $this->responsesService->error(400, __('messages.permission_revoke_failed'), $e->getMessage());
        }
    }
}
