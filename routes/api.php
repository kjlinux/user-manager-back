<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

route::prefix('auth')->group(function () {
    require __DIR__ . '/routers/auth.php';
});
