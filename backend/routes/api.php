<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Models\Category;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['language'],
], function () {
    Route::controller(LanguageController::class)->group(function () {
        Route::post('/set-language', 'setLanguage')->name('language.set_language');
    });

    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('/register', 'register')->name('auth.register');
        Route::post('/login', 'login')->name('auth.login');
    });

    Route::group([
        'middleware' => ['auth', 'verified'],
    ], function () {
        Route::prefix('auth')->controller(AuthController::class)->group(function () {
            Route::post('/logout', 'logout')->name('auth.logout');
            Route::post('/profile', 'profile')->name('auth.profile');
            Route::post('/refresh-token', 'refreshToken')->name('auth.refresh_token');
            Route::post('/verify-email', 'verifyEmail')->name('auth.verify_email');
            Route::post('/forgot-password', 'forgotPassword')->name('auth.forgot_password');
            Route::post('/reset-password', 'resetPassword')->name('auth.reset_password');
            Route::post('/send-link-password', 'sendLinkPassword')->name('auth.send_link_password');
            Route::post('/send-link-verify-email', 'sendLinkVerifyEmail')->name('auth.send_link_verify_email');
            Route::post('/update-password', 'updatePassword')->name('auth.update_password')->middleware('throttle:5,1');
        });

        Route::prefix('users')->controller(UserController::class)->group(function () {
            Route::get('/get-user-permissions', 'getUserPermissions')->name('users.get_user_permissions');
            Route::get('/trashed', 'trashed')->name('users.trashed')->middleware('permission:user-trashed');
            Route::get('/', 'index')->name('users.index')->middleware('permission:user-index');
            Route::post('/', 'store')->name('users.store')->middleware('permission:user-store');
            Route::get('/{id}', 'show')->name('users.show')->middleware('permission:user-show');
            Route::put('/', 'update')->name('users.update')->middleware('permission:user-update');
            Route::delete('/', 'destroy')->name('users.destroy')->middleware('permission:user-destroy');
            Route::put('/restore', 'restore')->name('users.restore')->middleware('permission:user-restore');
            Route::delete('/delete-completely', 'deleteCompletely')->name('users.delete_completely')->middleware('permission:user-delete-completely');
            Route::post('/import-excel', 'importExcel')->name('users.import_excel')->middleware('permission:user-import-excel');
            Route::post('/export-excel', 'exportExcel')->name('users.export_excel')->middleware('permission:user-export-excel');
            Route::post('/export-pdf', 'exportPDF')->name('users.export_pdf')->middleware('permission:user-export-pdf');
            Route::post('/view-pdf', 'viewPDF')->name('users.view_pdf')->middleware('permission:user-export-pdf');

            // file
            Route::post('/upload-image', 'uploadImage')->name('files.upload_image');
            Route::post('/upload-multiple-image', 'uploadMultipleImage')->name('files.upload_multiple_image');
            Route::post('/delete-image', 'deleteImage')->name('files.delete_image');
            Route::post('/delete-multiple-image', 'deleteMultipleImage')->name('files.delete_multiple_image');
            Route::post('/delete-folder', 'delete_folder')->name('files.delete_folder');
            Route::post('/delete-multiple-folder', 'delete_multiple_folder')->name('files.delete_multiple_folder');
            Route::post('/delete-folder', 'deleteFolderByCategory')->name('files.delete_folder_by_category');
        });

        Route::prefix('roles')->controller(RoleController::class)->group(function () {
            Route::get('/trashed', 'trashed')->name('roles.trashed')->middleware('permission:role-trashed');
            Route::get('/', 'index')->name('roles.index')->middleware('permission:role-index');
            Route::post('/', 'store')->name('roles.store')->middleware('permission:role-store');
            Route::get('/{id}', 'show')->name('roles.show')->middleware('permission:role-show');
            Route::put('/', 'update')->name('roles.update')->middleware('permission:role-update');
            Route::delete('/', 'destroy')->name('roles.destroy')->middleware('permission:role-destroy');
            Route::put('/restore', 'restore')->name('roles.restore')->middleware('permission:role-restore');
            Route::delete('/delete-completely', 'deleteCompletely')->name('roles.delete_completely')->middleware('permission:role-delete-completely');
            Route::post('/import-excel', 'importExcel')->name('roles.import_excel')->middleware('permission:role-import-excel');
            Route::post('/export-excel', 'exportExcel')->name('roles.export_excel')->middleware('permission:role-export-excel');
            Route::post('/export-pdf', 'exportPDF')->name('roles.export_pdf')->middleware('permission:role-export-pdf');
            Route::post('/view-pdf', 'viewPDF')->name('roles.view_pdf')->middleware('permission:role-export-pdf');
            Route::post('/assign-role', 'assignRole')->name('roles.assign_role')->middleware('permission:role-assign-role');
            Route::post('/revoke-role', 'revokeRole')->name('roles.revoke_role')->middleware('permission:role-revoke-role');
        });

        Route::prefix('permissions')->controller(PermissionController::class)->group(function () {
            Route::get('/get-tree', 'getTree')->name('permissions.get_tree')->middleware('permission:permission-index');
            Route::get('/trashed', 'trashed')->name('permissions.trashed')->middleware('permission:permission-trashed');
            Route::get('/', 'index')->name('permissions.index')->middleware('permission:permission-index');
            Route::post('/', 'store')->name('permissions.store')->middleware('permission:permission-store');
            Route::get('/{id}', 'show')->name('permissions.show')->middleware('permission:permission-show');
            Route::put('/', 'update')->name('permissions.update')->middleware('permission:permission-update');
            Route::delete('/', 'destroy')->name('permissions.destroy')->middleware('permission:permission-destroy');
            Route::put('/restore', 'restore')->name('permissions.restore')->middleware('permission:permission-restore');
            Route::delete('/delete-completely', 'deleteCompletely')->name('permissions.delete_completely')->middleware('permission:permission-delete-completely');
            Route::post('/import-excel', 'importExcel')->name('permissions.import_excel')->middleware('permission:permission-import-excel');
            Route::post('/export-excel', 'exportExcel')->name('permissions.export_excel')->middleware('permission:permission-export-excel');
            Route::post('/export-pdf', 'exportPDF')->name('permissions.export_pdf')->middleware('permission:permission-export-pdf');
            Route::post('/view-pdf', 'viewPDF')->name('permissions.view_pdf')->middleware('permission:permission-export-pdf');
            Route::post('/assign-permission', 'assignPermission')->name('permissions.assign_permission')->middleware('permission:permission-assign-permission');
            Route::post('/revoke-permission', 'revokePermission')->name('permissions.revoke_permission')->middleware('permission:permission-revoke-permission');
        });
    });

    Route::prefix('categories')->controller(CategoryController::class)->group(function () {
        Route::get('/get-tree', 'getTree')->name('categories.get_tree');
        Route::get('/trashed', 'trashed')->name('categories.trashed')->middleware('permission:category-trashed');
        Route::get('/', 'index')->name('categories.index')->middleware('permission:category-index');
        Route::post('/', 'store')->name('categories.store')->middleware('permission:category-store');
        Route::get('/{id}', 'show')->name('categories.show')->middleware('permission:category-show');
        Route::put('/', 'update')->name('categories.update')->middleware('permission:category-update');
        Route::delete('/', 'destroy')->name('categories.destroy')->middleware('permission:category-destroy');
        Route::put('/restore', 'restore')->name('categories.restore')->middleware('permission:category-restore');
        Route::delete('/delete-completely', 'deleteCompletely')->name('categories.delete_completely')->middleware('permission:category-delete-completely');
        Route::post('/import-excel', 'importExcel')->name('categories.import_excel')->middleware('permission:category-import-excel');
        Route::post('/export-excel', 'exportExcel')->name('categories.export_excel')->middleware('permission:category-export-excel');
        Route::post('/export-pdf', 'exportPDF')->name('categories.export_pdf')->middleware('permission:category-export-pdf');
        Route::post('/view-pdf', 'viewPDF')->name('categories.view_pdf')->middleware('permission:category-export-pdf');
    });
});
