<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductController::class, 'index'])->name('products.index');

Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'list'])->name('list');
    Route::post('/', [ProductController::class, 'store'])->name('store');
    Route::put('/{id}', [ProductController::class, 'update'])->name('update')->whereUuid('id');
});
