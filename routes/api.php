<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;

Route::post('/register', [AuthController::class, 'register'])->middleware('auth:sanctum');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
// Route::get('/products/', [ProductController::class,'index']);


Route::middleware('auth:sanctum')->group(function () {
    // Route::post('/products/update', [AuthController::class, 'update']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::apiResource('products', ProductController::class);
});