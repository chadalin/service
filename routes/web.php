<?php

use App\Http\Controllers\Auth\PinAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\CarController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\HomeController;
// Добавьте эти use statements для новых контроллеров
use App\Http\Controllers\Diagnostic\DiagnosticController;
use App\Http\Controllers\Diagnostic\ReportController;
use App\Http\Controllers\Diagnostic\Admin\SymptomController as DiagnosticSymptomController;
use App\Http\Controllers\Diagnostic\Admin\RuleController as DiagnosticRuleController;


// Главная посадочная страница (B2C)
Route::get('/', [HomeController::class, 'index'])->name('home');

// Посадочная страница для сервисов (B2B)
Route::get('/services', [HomeController::class, 'landing'])->name('services.landing');

// Auth Routes
Route::get('/login', [PinAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login/send-pin', [PinAuthController::class, 'sendPin'])->name('login.send-pin');
Route::get('/login/verify', [PinAuthController::class, 'showVerifyForm'])->name('login.verify');
Route::post('/login/verify', [PinAuthController::class, 'verifyPin'])->name('login.verify');
Route::post('/logout', [PinAuthController::class, 'logout'])->name('logout');

// Admin Routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/documents/models/{brandId}', [DocumentController::class, 'getModels'])->name('documents.models');
    Route::post('/documents/{document}/reprocess', [DocumentController::class, 'reprocess'])->name('documents.reprocess');
      Route::get('/models/{brand}', [DocumentController::class, 'getModels'])->name('models');
    // Документы (добавьте в существующие роуты документов)
Route::post('/documents/upload-chunk', [DocumentController::class, 'uploadChunk'])->name('admin.documents.upload-chunk');
Route::post('/documents/check-file', [DocumentController::class, 'checkFile'])->name('admin.documents.check-file');
    // Search routes
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');
    Route::post('/search', [SearchController::class, 'search'])->name('search');
    Route::get('/search/advanced', [SearchController::class, 'advancedSearch'])->name('search.advanced');

    // Categories - ПЕРЕМЕСТИЛИ ВНУТРЬ admin группы
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
});

// Cars data - ОСТАВЛЯЕМ ВНЕ admin группы
Route::get('/cars/brands', [CarController::class, 'brands'])->name('cars.brands');
Route::get('/cars/models', [CarController::class, 'models'])->name('cars.models');
Route::get('/cars/import', [CarController::class, 'importForm'])->name('cars.import');
Route::post('/cars/import', [CarController::class, 'import'])->name('cars.import');

// Chat
Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::post('/chat/search', [ChatController::class, 'search'])->name('chat.search');
Route::get('/chat/models/{brandId}', [ChatController::class, 'getModels'])->name('chat.models');

// Documents AJAX
Route::get('/documents/models/{brandId}', [DocumentController::class, 'getModels'])->name('documents.models');
Route::redirect('/', '/login');

// Semantic search
Route::post('/search/semantic', [SearchController::class, 'semanticSearch'])->name('search.semantic');

// ===============================================
// Diagnostic Routes
// ===============================================
Route::middleware(['auth'])->prefix('diagnostic')->name('diagnostic.')->group(function () {
    // Шаги диагностики
    Route::get('/', [DiagnosticController::class, 'step1'])->name('start');
    Route::post('/step2', [DiagnosticController::class, 'step2'])->name('step2');
    Route::post('/step3', [DiagnosticController::class, 'step3'])->name('step3');
    Route::post('/analyze', [DiagnosticController::class, 'analyze'])->name('analyze');
    Route::get('/result/{case}', [DiagnosticController::class, 'result'])->name('result');
    
    // Консультации
    Route::post('/consultation/{case}/order', [DiagnosticController::class, 'orderConsultation'])->name('consultation.order');
    
    // Отчёты
    Route::get('/report/{case}', [ReportController::class, 'show'])->name('report.show');
    Route::get('/report/{case}/pdf', [ReportController::class, 'pdf'])->name('report.pdf');
    Route::post('/report/{case}/send', [ReportController::class, 'sendEmail'])->name('report.send');
    
    // AJAX
    Route::get('/models/{brandId}', [DiagnosticController::class, 'getModels'])->name('models');
});

// Admin Diagnostic Routes
Route::middleware(['auth'])->prefix('admin/diagnostic')->name('admin.diagnostic.')->group(function () {
    // Симптомы
    Route::get('/symptoms', [DiagnosticSymptomController::class, 'index'])->name('symptoms.index');
    Route::get('/symptoms/create', [DiagnosticSymptomController::class, 'create'])->name('symptoms.create');
    Route::post('/symptoms', [DiagnosticSymptomController::class, 'store'])->name('symptoms.store');
    Route::get('/symptoms/{symptom}/edit', [DiagnosticSymptomController::class, 'edit'])->name('symptoms.edit');
    Route::put('/symptoms/{symptom}', [DiagnosticSymptomController::class, 'update'])->name('symptoms.update');
    Route::delete('/symptoms/{symptom}', [DiagnosticSymptomController::class, 'destroy'])->name('symptoms.destroy');
    
    // Правила
    Route::get('/rules', [DiagnosticRuleController::class, 'index'])->name('rules.index');
    Route::get('/rules/create', [DiagnosticRuleController::class, 'create'])->name('rules.create');
    Route::post('/rules', [DiagnosticRuleController::class, 'store'])->name('rules.store');
    Route::get('/rules/{rule}/edit', [DiagnosticRuleController::class, 'edit'])->name('rules.edit');
    Route::put('/rules/{rule}', [DiagnosticRuleController::class, 'update'])->name('rules.update');
    Route::delete('/rules/{rule}', [DiagnosticRuleController::class, 'destroy'])->name('rules.destroy');
    Route::get('/rules/models/{brandId}', [DiagnosticRuleController::class, 'getModels'])->name('rules.models');
});

 // Демо-запросы (лиды)
    Route::prefix('demo-requests')->name('demo.')->group(function () {
        Route::get('/', [DemoController::class, 'adminIndex'])->name('index');
        Route::get('/{id}', [DemoController::class, 'adminShow'])->name('show');
        Route::put('/{id}/status', [DemoController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{id}', [DemoController::class, 'destroy'])->name('destroy');
        Route::get('/export', [DemoController::class, 'export'])->name('export');
    });


    // Маршруты для документов
Route::prefix('admin/documents')->name('admin.documents.')->middleware(['auth'])->group(function () {
    Route::get('/', [DocumentController::class, 'index'])->name('index');
    Route::get('/create', [DocumentController::class, 'create'])->name('create');
    Route::post('/', [DocumentController::class, 'store'])->name('store');
    Route::get('/{document}', [DocumentController::class, 'show'])->name('show');
    Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
    
    // AJAX маршруты для чанковой загрузки
    Route::post('/upload-chunk', [DocumentController::class, 'uploadChunk'])->name('upload-chunk');
    Route::post('/check-file', [DocumentController::class, 'checkFile'])->name('check-file');
});