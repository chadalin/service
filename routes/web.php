<?php

use App\Http\Controllers\Auth\PinAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\CarController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::get('/login', [PinAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login/send-pin', [PinAuthController::class, 'sendPin'])->name('login.send-pin');
Route::get('/login/verify', [PinAuthController::class, 'showVerifyForm'])->name('login.verify');
Route::post('/login/verify', [PinAuthController::class, 'verifyPin'])->name('login.verify');
Route::post('/logout', [PinAuthController::class, 'logout'])->name('logout');

// Admin Routes
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    
    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('admin.documents.index');
    Route::get('/documents/create', [DocumentController::class, 'create'])->name('admin.documents.create');
    Route::post('/documents', [DocumentController::class, 'store'])->name('admin.documents.store');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('admin.documents.destroy');
    // В admin группе добавьте:
Route::get('/documents/models/{brandId}', [DocumentController::class, 'getModels'])->name('admin.documents.models');


// Search routes
Route::get('/search', [SearchController::class, 'index'])->name('search.index');
Route::post('/search', [SearchController::class, 'search'])->name('search');
Route::get('/search/advanced', [SearchController::class, 'advancedSearch'])->name('search.advanced');
});


// Cars data
Route::get('/cars/brands', [CarController::class, 'brands'])->name('admin.cars.brands');
Route::get('/cars/models', [CarController::class, 'models'])->name('admin.cars.models');
Route::get('/cars/import', [CarController::class, 'importForm'])->name('admin.cars.import');
Route::post('/cars/import', [CarController::class, 'import'])->name('admin.cars.import');

// Categories
Route::get('/admin/categories', [CategoryController::class, 'index'])->name('admin.categories.index');
Route::get('/admin/categories/create', [CategoryController::class, 'create'])->name('admin.categories.create');
Route::post('/admin/categories', [CategoryController::class, 'store'])->name('admin.categories.store');
Route::get('/admin/categories/{category}/edit', [CategoryController::class, 'edit'])->name('admin.categories.edit');
Route::put('/admin/categories/{category}', [CategoryController::class, 'update'])->name('admin.categories.update');
Route::delete('/admin/categories/{category}', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');

// Chat
Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::post('/chat/search', [ChatController::class, 'search'])->name('chat.search');
Route::get('/chat/models/{brandId}', [ChatController::class, 'getModels'])->name('chat.models');


// Documents AJAX
Route::get('/documents/models/{brandId}', [DocumentController::class, 'getModels'])->name('admin.documents.models');
Route::redirect('/', '/login');

// Semantic search
Route::post('/search/semantic', [SearchController::class, 'semanticSearch'])->name('search.semantic');