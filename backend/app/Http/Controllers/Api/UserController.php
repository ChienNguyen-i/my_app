<?php

namespace App\Http\Controllers\api;

use App\Exports\UsersExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\UserRequest;
use App\Imports\UsersImport;
use App\Mail\VerifyEmail;
use App\Mail\Welcome;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class UserController extends BaseController
{
    protected $columns_select = ['id', 'name', 'email', 'image', 'type', 'status'];
    protected $columns_search = ['id', 'name', 'email'];

    /**
     * Display a listing of the resource.
     */
    public function index(UserRequest $request)
    {
        $limit = $request->limit ?? $this->limit_pagination;
        $page = $request->page ?? 1;
        $offset = ($page - 1) * $limit;
        $query = User::select($this->columns_select);
        $this->applyFilters($query, $request->filter);
        if ($request->filled('search')) {
            $this->applySearch($query, $request->search, $this->columns_search);
        }
        $this->applyOrderBy($query, $request->orderBy);
        $total = $query->count();
        list($from, $to) = $this->calculatePagination($total, $limit, $page);
        $data = $query->offset($offset)->limit($limit)->get();
        return $this->responsesService->pagination(200, __('message.success'), $data, $from, $to, $page, $limit, $total);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if ($user) {
            if ($user->trashed()) {
                $user->restore();
                return $this->responsesService->success(200, __('message.restore_successful'), ['user' => $user]);
            }
            return $this->responsesService->error(400, __('message.email_unique'));
        }
        $password = Str::random(10);
        $data = $request->all();
        $data['password'] = Hash::make($password);
        // $data['image'] = $this->manageFilesService->uploadFile($request, 'image', 'users/images');
        $user = User::create($data);
        if (!$user) {
            return $this->responsesService->error(400, __('message.add_failed'));
        }
        $token = Str::random(64);
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        DB::table('password_reset_tokens')->insert(['email' => $request->email, 'token' => $token, 'created_at' => Carbon::now()]);
        try {
            $mailData = ['user' => $user, 'password' => $password, 'token' => $token];
            Mail::to($request->email)->queue(new Welcome($mailData));
            Mail::to($request->email)->queue(new VerifyEmail($mailData));
        } catch (Exception $e) {
            return $this->responsesService->error(500, __('message.email_sending_failed'), $e->getMessage());
        }
        return $this->responsesService->success(
            200,
            __('message.add_successful_with_verification_link'),
            ['user' => $user, 'token' => $token]
        );
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $data = User::findOrFail($id);
            return $this->responsesService->success(200, __('message.success'), $data);
        } catch (ModelNotFoundException $e) {
            return $this->responsesService->error(400, __('message.not_found'), $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request)
    {
        try {
            $user = User::findOrFail($request->id);
            $data = $request->all();
            if ($request->email !== $user->email) {
                if (User::where('email', $request->email)->exists()) {
                    return $this->responsesService->error(400, __('message.validation_failed'), __('message.email_unique'));
                }
                $data['email_verified_at'] = null;
            }
            // $data['image'] = $this->manageFilesService->uploadFile($request, 'image', 'users/images');
            $user->update($data);
            return $this->responsesService->success(200, __('message.update_successful'), $user);
        } catch (ModelNotFoundException $e) {
            return $this->responsesService->error(400, __('message.not_found'), $e->getMessage());
        } catch (Exception $e) {
            return $this->responsesService->error(400, __('message.update_failed'), $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserRequest $request)
    {
        if (empty($request->ids)) {
            return $this->responsesService->error(400, __('message.no_ids_provided'));
        }
        $data = ['deleted_at' => Carbon::now(), 'deleted_by' => Auth::user()->id];
        $result = User::whereIn('id', $request->ids)->update($data);
        if ($result) {
            return $this->responsesService->success(200, __('message.delete_successful'));
        }
        return $this->responsesService->error(400, __('message.delete_failed'));
    }

    public function restore(UserRequest $request)
    {
        if (empty($request->ids)) {
            return $this->responsesService->error(400, __('message.no_ids_provided'));
        }
        $data = ['deleted_at' => null, 'deleted_by' => null];
        $result = User::onlyTrashed()->whereIn('id', $request->ids)->update($data);
        if ($result) {
            return $this->responsesService->success(200, __('message.restore_successful'));
        }
        return $this->responsesService->error(400, __('message.restore_failed'));
    }

    public function deleteCompletely(UserRequest $request)
    {
        if (empty($request->ids)) {
            return $this->responsesService->error(400, __('message.no_ids_provided'));
        }
        $users = User::onlyTrashed()->whereIn('id', $request->ids)->get();
        if ($users->isEmpty()) {
            return $this->responsesService->error(400, __('message.not_found'));
        }
        $list_image = $users->pluck('image')->toArray();
        // $this->manageFilesService->deleteMultipleFile($list_image);
        $result = User::onlyTrashed()->whereIn('id', $request->ids)->forceDelete();
        if ($result) {
            return $this->responsesService->success(200, __('message.delete_successful'));
        }
        return $this->responsesService->error(400, __('message.delete_failed'));
    }

    public function trashed(UserRequest $request)
    {
        $limit = $request->limit ?? $this->limit_pagination;
        $page = $request->page ?? 1;
        $offset = ($page - 1) * $limit;
        $query = User::onlyTrashed();
        $this->applyFilters($query, $request->filter);
        if ($request->filled('search')) {
            $this->applySearch($query, $request->search, $this->columns_search);
        }
        $this->applyOrderBy($query, $request->orderBy);
        $total = $query->count();
        list($from, $to) = $this->calculatePagination($total, $limit, $page);
        $data = $query->offset($offset)->limit($limit)->get();
        return $this->responsesService->pagination(200, __('message.success'), $data, $from, $to, $page, $limit, $total);
    }

    // public function importExcel(UserRequest $request)
    // {
    //     DB::beginTransaction();

    //     try {
    //         // ini_set('max_execution_time', 1800);
    //         $this->manageFilesService->uploadFile($request, 'file', 'users/imports');
    //         Excel::import(new UsersImport, $request->file('file'));

    //         DB::commit();

    //         return $this->responsesService->success(200, __('message.import_successful'));
    //     } catch (Throwable $th) {
    //         DB::rollBack();

    //         return $this->responsesService->error(400, __('message.import_failed'), $th->getMessage());
    //     }
    // }

    // public function exportExcel(UserRequest $request)
    // {
    //     try {
    //         $query = User::select($this->columns_select);

    //         $this->applyFilters($query, $request->filter);

    //         if ($request->filled('search')) {
    //             $this->applySearch($query, $request->search, $this->columns_search);
    //         }

    //         $this->applyOrderBy($query, $request->orderBy);

    //         $data = $query->get();
    //         return Excel::download(new UsersExport($data, $this->columns_select), 'users_' . date('YmdHis') . '.xlsx');
    //     } catch (Throwable $th) {
    //         return $this->responsesService->error(400, __('message.export_failed'), $th->getMessage());
    //     }
    // }

    // public function exportPDF(UserRequest $request)
    // {
    //     try {
    //         $query = User::select($this->columns_select);

    //         $this->applyFilters($query, $request->filter);

    //         if ($request->filled('search')) {
    //             $this->applySearch($query, $request->search, $this->columns_search);
    //         }

    //         $this->applyOrderBy($query, $request->orderBy);

    //         $data = $query->get();
    //         $pdfData = [
    //             'title' => 'Welcome to my app',
    //             'date' => date('d/m/Y'),
    //             'users' => $data
    //         ];
    //         $pdf = PDF::loadView('pdf.users',  $pdfData)->setPaper('a4', 'portrait');
    //         return $pdf->download('users_' . date('YmdHis') . '.pdf');
    //     } catch (Throwable $th) {
    //         return $this->responsesService->error(400, __('message.export_failed'), $th->getMessage());
    //     }
    // }

    // public function viewPDF(UserRequest $request)
    // {
    //     try {
    //         $query = User::select($this->columns_select);

    //         $this->applyFilters($query, $request->filter);

    //         if ($request->filled('search')) {
    //             $this->applySearch($query, $request->search, $this->columns_search);
    //         }

    //         $this->applyOrderBy($query, $request->orderBy);

    //         $data = $query->get();
    //         $pdfData = [
    //             'title' => 'Welcome to my app',
    //             'date' => date('d/m/Y'),
    //             'users' => $data
    //         ];
    //         $pdf = PDF::loadView('pdf.users', $pdfData)->setPaper('a4', 'portrait');
    //         return $pdf->stream();
    //     } catch (Throwable $th) {
    //         return $this->responsesService->error(400, __('message.export_failed'), $th->getMessage());
    //     }
    // }

    public function getUserPermissions()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->responsesService->error(404, __('message.user_not_found'));
            }
            $permissions = $user->hasPermissions()->pluck('name', 'key_code');
            return $this->responsesService->success(200, __('message.success'), $permissions);
        } catch (Exception $e) {
            return $this->responsesService->error(500, __('message.failed_retrieve_permission'), $e->getMessage());
        }
    }
}
