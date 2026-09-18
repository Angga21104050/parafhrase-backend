<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PenjokiController;
use App\Http\Controllers\AdminController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    // Authentication
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Customer
    Route::middleware('role:user')
        ->prefix('customer')
        ->group(function () {

            Route::get('/dashboard', [CustomerController::class, 'dashboard']);

            Route::get('/documents', [CustomerController::class, 'index']);

            Route::post('/documents', [CustomerController::class, 'store']);

            Route::get('/documents/{id}', [CustomerController::class, 'show']);

            Route::get(
                '/documents/{id}/download-result',
                [CustomerController::class, 'downloadResult']
            );
        });

    // Penjoki
    Route::middleware('role:penjoki')
        ->prefix('penjoki')
        ->group(function () {

            Route::get('/dashboard', [
                PenjokiController::class,
                'dashboard'
            ]);

            Route::get('/available-documents', [
                PenjokiController::class,
                'availableDocuments'
            ]);

            Route::get('/my-documents', [
                PenjokiController::class,
                'myDocuments'
            ]);

            Route::post('/documents/{id}/assign', [
                PenjokiController::class,
                'assign'
            ]);

            Route::get('/documents/{id}/download', [
                PenjokiController::class,
                'download'
            ]);

            Route::patch('/documents/{id}/status', [
                PenjokiController::class,
                'updateStatus'
            ]);

            Route::post('/documents/{id}/result', [
                PenjokiController::class,
                'uploadResult'
            ]);
        });

    // Admin
    Route::middleware('role:admin')
        ->prefix('admin')
        ->group(function () {

            Route::get('/dashboard', [
                AdminController::class,
                'dashboard'
            ]);

            Route::get('/documents', [
                AdminController::class,
                'documents'
            ]);

            Route::get('/documents/{id}', [
                AdminController::class,
                'showDocument'
            ]);

            Route::patch('/documents/{id}/status', [
                AdminController::class,
                'updateStatus'
            ]);

            Route::get('/customers', [
                AdminController::class,
                'customers'
            ]);

            Route::get('/penjokis', [
                AdminController::class,
                'penjokis'
            ]);
        });
});
