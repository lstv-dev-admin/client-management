<?php

use App\Http\Controllers\ClientContactController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientMergeController;
use App\Http\Controllers\ClientProductController;
use App\Http\Controllers\DryRunController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ClientController::class, 'index'])->name('clients.index');
Route::post('/dry-run', [DryRunController::class, 'toggle'])->name('dry-run.toggle');
Route::get('/clients/search', [ClientMergeController::class, 'search'])->name('clients.search');
Route::get('/clients/{client}/summary', [ClientMergeController::class, 'summary'])->name('clients.summary');
Route::post('/clients/{client}/merge', [ClientMergeController::class, 'store'])->name('clients.merge');
Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

Route::post('/clients/{client}/products', [ClientProductController::class, 'store'])->name('clients.products.store');
Route::put('/clients/{client}/products/{product}', [ClientProductController::class, 'update'])->name('clients.products.update');
Route::delete('/clients/{client}/products/{product}', [ClientProductController::class, 'destroy'])->name('clients.products.destroy');

Route::post('/clients/{client}/contacts', [ClientContactController::class, 'store'])->name('clients.contacts.store');
Route::put('/clients/{client}/contacts/{contact}', [ClientContactController::class, 'update'])->name('clients.contacts.update');
Route::delete('/clients/{client}/contacts/{contact}', [ClientContactController::class, 'destroy'])->name('clients.contacts.destroy');
