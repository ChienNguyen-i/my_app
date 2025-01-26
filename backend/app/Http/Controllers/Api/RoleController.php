<?php

namespace App\Http\Controllers\Api;

use App\Exports\RolesExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\RoleRequest;
use App\Imports\RolesImport;
use App\Models\Role;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class RoleController extends BaseController
{
    protected $columns_select = ['id', 'name'];
    protected $columns_search = ['id', 'name'];

    /**
     * Display a listing of the resource.
     */
    public function index(RoleRequest $request)
    {
        $limit = $request->limit ?? $this->limit_pagination;
        $page = $request->page ?? 1;
        $offset = ($page - 1) * $limit;
        $query = Role::select($this->columns_select);
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
    public function store(RoleRequest $request)
    {
        $data = $request->all();
        $role = Role::create($data);
        if (!$role) {
            return $this->responsesService->error(400, __('messages.add_failed'));
        }
        return $this->responsesService->success(200, __('messages.add_successful'), $role);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $data = Role::findOrFail($id);
            return $this->responsesService->success(200, __('messages.success'), $data);
        } catch (ModelNotFoundException $e) {
            return $this->responsesService->error(400, __('messages.not_found'), $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request)
    {
        try {
            $role = Role::findOrFail($request->id);
            $data = $request->all();
            $role->update($data);
            return $this->responsesService->success(200, __('messages.update_successful'), $role);
        } catch (ModelNotFoundException $e) {
            return $this->responsesService->error(400, __('messages.not_found'), $e->getMessage());
        } catch (Exception $e) {
            return $this->responsesService->error(400, __('messages.update_failed'), $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RoleRequest $request)
    {
        if (empty($request->ids)) {
            return $this->responsesService->error(400, __('messages.no_ids_provided'));
        }
        $data = ['deleted_at' => Carbon::now(), 'deleted_by' => Auth::user()->id];
        $result = Role::whereIn('id', $request->ids)->update($data);
        if ($result) {
            return $this->responsesService->success(200, __('messages.delete_successful'));
        }
        return $this->responsesService->error(400, __('messages.delete_failed'));
    }

    public function restore(RoleRequest $request)
    {
        if (empty($request->ids)) {
            return $this->responsesService->error(400, __('messages.no_ids_provided'));
        }
        $data = ['deleted_at' => null, 'deleted_by' => null];
        $result = Role::onlyTrashed()->whereIn('id', $request->ids)->update($data);
        if ($result) {
            return $this->responsesService->success(200, __('messages.restore_successful'));
        }
        return $this->responsesService->error(400, __('messages.restore_failed'));
    }

    public function deleteCompletely(RoleRequest $request)
    {
        if (empty($request->ids)) {
            return $this->responsesService->error(400, __('messages.no_ids_provided'));
        }
        $roles = Role::onlyTrashed()->whereIn('id', $request->ids)->get();
        if ($roles->isEmpty()) {
            return $this->responsesService->error(400, __('messages.not_found'));
        }
        $result = Role::onlyTrashed()->whereIn('id', $request->ids)->forceDelete(); 
        if ($result) {
            return $this->responsesService->success(200, __('messages.delete_successful'));
        }
        return $this->responsesService->error(400, __('messages.delete_failed'));
    }

    public function trashed(RoleRequest $request)
    {
        $limit = $request->limit ?? $this->limit_pagination;
        $page = $request->page ?? 1;
        $offset = ($page - 1) * $limit;
        $query = Role::onlyTrashed();
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

    // public function importExcel(RoleRequest $request)
    // {
    //     DB::beginTransaction();

    //     try {
    //         ini_set('max_execution_time', 1800);
    //         $this->manageFilesService->uploadFile($request, 'file', 'roles/imports');
    //         Excel::import(new RolesImport, $request->file('file'));

    //         DB::commit();

    //         return $this->responsesService->success(200, __('messages.import_successful'));
    //     } catch (Throwable $th) {
    //         Log::error('Import failed: ' . $th->getMessage());
    //         DB::rollBack();

    //         return $this->responsesService->error(400, __('messages.import_failed'));
    //     }
    // }

    // public function exportExcel(RoleRequest $request)
    // {
    //     try {
    //         $roles = $this->getDataByInputNotPagination($request);

    //         return Excel::download(new RolesExport($roles->get()), 'roles_' . date('YmdHis') . '.xlsx');
    //     } catch (Throwable $th) {
    //         Log::error('Export failed: ' . $th->getMessage());

    //         return $this->responsesService->error(400, __('messages.export_failed'));
    //     }
    // }

    // public function exportPDF(RoleRequest $request)
    // {
    //     try {
    //         $roles = $this->getDataByInputNotPagination($request)->get();
    //         $pdfData = [
    //             'title' => 'Welcome to my app',
    //             'date' => date('d/m/Y'),
    //             'roles' => $roles
    //         ];
    //         $pdf = PDF::loadView('pdf.roles',  $pdfData)->setPaper('a4', 'portrait');

    //         return $pdf->download('roles_' . date('YmdHis') . '.pdf');
    //     } catch (Throwable $th) {
    //         Log::error('Export failed: ' . $th->getMessage());

    //         return $this->responsesService->error(400, __('messages.export_failed'));
    //     }
    // }

    // public function viewPDF(RoleRequest $request)
    // {
    //     try {
    //         $roles = $this->getDataByInputNotPagination($request)->get();
    //         $pdfData = [
    //             'title' => 'Welcome to my app',
    //             'date' => date('d/m/Y'),
    //             'roles' => $roles
    //         ];
    //         $pdf = PDF::loadView('pdf.roles', $pdfData)->setPaper('a4', 'portrait');

    //         return $pdf->stream();
    //     } catch (Throwable $th) {
    //         Log::error('View PDF failed: ' . $th->getMessage());

    //         return $this->responsesService->error(400, __('messages.export_failed'));
    //     }
    // }

    public function assignRole(RoleRequest $request)
    {
        DB::beginTransaction();
        try {
            $users = User::whereIn('id', $request->user_ids)->get();
            $roles = Role::whereIn('id', $request->role_ids)->get();
            if ($users->isEmpty()) {
                return $this->responsesService->error(400, __('messages.users_not_found'));
            }
            if ($roles->isEmpty()) {
                return $this->responsesService->error(400, __('messages.roles_not_found'));
            }
            foreach ($users as $user) {
                $user->roles()->syncWithoutDetaching($request->role_ids);
            }
            DB::commit();
            return $this->responsesService->success(200, __('messages.role_assign_success'));
        } catch (Exception $e) {
            DB::rollBack();
            return $this->responsesService->error(400, __('messages.role_assign_failed'), $e->getMessage());
        }
    }

    public function revokeRole(RoleRequest $request)
    {
        DB::beginTransaction();
        try {
            $users = User::whereIn('id', $request->user_ids)->get();
            $roles = Role::whereIn('id', $request->role_ids)->get();
            if ($users->isEmpty()) {
                return $this->responsesService->error(400, __('messages.users_not_found'));
            }
            if ($roles->isEmpty()) {
                return $this->responsesService->error(400, __('messages.roles_not_found'));
            }
            foreach ($users as $user) {
                $user->roles()->detach($request->role_ids);
            }
            DB::commit();
            return $this->responsesService->success(200, __('messages.role_revoke_successful'));
        } catch (Exception $e) {
            DB::rollBack();
            return $this->responsesService->error(400, __('messages.role_revoke_failed'), $e->getMessage());
        }
    }
}
