<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductController::class, 'index']);
Route::post('/users', [ProductController::class, 'createUser'])->name('users.store');
Route::get('/users/{id}/switch', [ProductController::class, 'switchUser'])->name('users.switch');

Route::post('/products', [ProductController::class, 'store'])->name('products.store');
Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');
Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
Route::post('/products/{id}/restore', [ProductController::class, 'restore'])->name('products.restore');
Route::delete('/products/{id}/force', [ProductController::class, 'forceDelete'])->name('products.forceDelete');

Route::post('/logs/clean', [ProductController::class, 'clean'])->name('logs.clean');
Route::get('/logs/download/{filename}', [ProductController::class, 'downloadArchive'])->name('logs.download');
