<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\Api\UserController;
use App\Http\Controllers\Auth\Api\LoginController;

// Keep login route without auth
Route::post('/users/login', [LoginController::class, 'login']);
Route::get('/users', [UserController::class, 'index']);
Route::get('/users/form-data', [UserController::class, 'getFormData']);
Route::get('/departments', [UserController::class, 'getDepartments']);
Route::get('/positions', [UserController::class, 'getPositions']);
Route::get('/roles', [UserController::class, 'getRoles']);

Route::post('/users', [UserController::class, 'store']);
Route::get('/users/{user}', [UserController::class, 'show']);
Route::post('/users/{user}', [UserController::class, 'update']);
Route::delete('/users/{user}', [UserController::class, 'destroy']);
