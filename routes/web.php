<?php

use App\Http\Controllers\KmdbController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('kmdb.index'));

Route::get('/kmdb', [KmdbController::class, 'index'])->name('kmdb.index');
Route::post('/kmdb', [KmdbController::class, 'store'])->name('kmdb.store');