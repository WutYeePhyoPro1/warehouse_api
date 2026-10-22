<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\PermissionController;

Route::get('/select2', function () {
    return view('layouts.select2');
});



Route::get('/', function () {
    return redirect('admins/login');
});

Route::middleware([
    'auth',
    \App\Http\Middleware\EnsureUserIsActive::class,
    'role:Super-Admin',
])->group(function () {
    Route::resource('admin/branches', BranchController::class);
    Route::resource('admin/departments', DepartmentController::class);
    Route::resource('admin/positions', PositionController::class);
    Route::resource('admin/roles', RoleController::class);
    Route::resource('admin/permissions', PermissionController::class);
    Route::resource('admin/users', UserController::class);
    Route::get('user/search', [UserController::class, 'user_search']);
});
