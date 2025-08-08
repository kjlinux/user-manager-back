<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\UserController;

Route::post('login', [UserController::class, 'login']);

Route::prefix('users')->middleware('jwt.cookie')->group(function () {
    Route::get('trash', [UserController::class, 'trashed']);
    Route::get('roles/get', [UserController::class, 'getRoles']);
    Route::get('logs/get', [UserController::class, 'getLogs']);
    Route::post('restore/{user}', [UserController::class, 'restore'])->withTrashed();

    Route::get('profile/get', [UserController::class, 'profile']);
    Route::post('logout', [UserController::class, 'logout']);
    Route::post('refresh', [UserController::class, 'refresh']);

    Route::patch('toggle-status/{user}', [UserController::class, 'toggleStatus']);
    Route::post('update-role/{user}', [UserController::class, 'updateRole']);
    Route::post('update-profile/{user}', [UserController::class, 'updateProfile']);
    Route::post('update-profile-photo/{user}', [UserController::class, 'updateProfilePhoto']);
});

Route::middleware('jwt.cookie')->group(function () {
    Route::apiResource('users', UserController::class);
});
