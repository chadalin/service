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
use App\Http\Controllers\Admin\DocumentProcessingController;
use App\Http\Controllers\Diagnostic\DiagnosticController;
use App\Http\Controllers\Diagnostic\ReportController;
use App\Http\Controllers\Diagnostic\Admin\SymptomController as DiagnosticSymptomController;
use App\Http\Controllers\Diagnostic\Admin\RuleController as DiagnosticRuleController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectInfoController;
use App\Http\Controllers\Diagnostic\ConsultationController;

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

// ===============================================
// Admin Routes
// ===============================================
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Обработка документов - ДОЛЖЕН БЫТЬ ПЕРЕД ДРУГИМИ МАРШРУТАМИ ДОКУМЕНТОВ
     Route::prefix('documents-processing')->name('documents.processing.')->group(function () {
        Route::get('/', [DocumentProcessingController::class, 'index'])->name('index');
        
        Route::post('/parse/{id}', [DocumentProcessingController::class, 'parseDocument'])->name('parse');
        Route::post('/index/{id}', [DocumentProcessingController::class, 'indexDocument'])->name('index');
        Route::post('/process/{id}', [DocumentProcessingController::class, 'processDocument'])->name('process');
        Route::post('/parse-multiple', [DocumentProcessingController::class, 'parseMultiple'])->name('parse.multiple');
        Route::post('/index-multiple', [DocumentProcessingController::class, 'indexMultiple'])->name('index.multiple');
        Route::get('/status/{id}', [DocumentProcessingController::class, 'getStatus'])->name('status');
        Route::post('/reset/{id}', [DocumentProcessingController::class, 'resetStatus'])->name('reset');
    });
    
    // Documents Routes (ОСНОВНЫЕ)
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::get('/create', [DocumentController::class, 'create'])->name('create');
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::get('/{document}', [DocumentController::class, 'show'])->name('show');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
        Route::get('/{document}/preview', [DocumentController::class, 'preview'])->name('preview');
        Route::get('/{document}/download', [DocumentController::class, 'download'])->name('download');
        Route::post('/{document}/reprocess', [DocumentController::class, 'reprocess'])->name('reprocess');
        
        // AJAX маршруты для документов
        Route::get('/models/{brandId}', [DocumentController::class, 'getModels'])->name('models');
        Route::post('/upload-chunk', [DocumentController::class, 'uploadChunk'])->name('upload-chunk');
        Route::post('/check-file', [DocumentController::class, 'checkFile'])->name('check-file');
    });
    
    // Search Routes
    Route::prefix('search')->name('search.')->group(function () {
        Route::get('/', [SearchController::class, 'index'])->name('index');
        Route::post('/', [SearchController::class, 'search'])->name('perform');
        Route::get('/advanced', [SearchController::class, 'advancedSearch'])->name('advanced');
        Route::post('/semantic', [SearchController::class, 'semanticSearch'])->name('semantic');
    });
    
    // Categories Routes
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/create', [CategoryController::class, 'create'])->name('create');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
    });
    
    // Cars Routes
    Route::prefix('cars')->name('cars.')->group(function () {
        Route::get('/import', [CarController::class, 'importForm'])->name('import');
        Route::post('/import', [CarController::class, 'import'])->name('import.submit');
        Route::get('/brands', [CarController::class, 'brands'])->name('brands');
        Route::get('/models', [CarController::class, 'models'])->name('models');
    });
    
    // Demo Requests Routes
    Route::prefix('demo-requests')->name('demo.')->group(function () {
        Route::get('/', [DemoController::class, 'adminIndex'])->name('index');
        Route::get('/{id}', [DemoController::class, 'adminShow'])->name('show');
        Route::put('/{id}/status', [DemoController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{id}', [DemoController::class, 'destroy'])->name('destroy');
        Route::get('/export', [DemoController::class, 'export'])->name('export');
    });
    
    // Diagnostic Admin Routes
    Route::prefix('diagnostic')->name('diagnostic.')->group(function () {
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
    
    // Тестовый поиск
    Route::get('/test-search', function() {
        $brands = \App\Models\Brand::orderBy('name')->get();
        $models = \App\Models\CarModel::orderBy('name')->get()
            ->groupBy('brand_id')
            ->map(function($group) {
                return $group->map(function($model) {
                    return [
                        'id' => $model->id,
                        'name' => $model->name_cyrillic ?? $model->name,
                        'year_from' => $model->year_from,
                        'year_to' => $model->year_to
                    ];
                })->values();
            });
        
        \Log::info('Test search - Brands:', $brands->pluck('name')->toArray());
        
        return view('test-search', compact('brands', 'models'));
    })->name('test.search');
    
    // Дополнительные AJAX маршруты
    Route::get('/search/models/{brandId}', [SearchController::class, 'getModels'])
        ->name('search.models');
    Route::post('/documents/batch-index', [DocumentController::class, 'batchIndex'])
        ->name('documents.batch-index');
    Route::post('/documents/{document}/reindex', [DocumentController::class, 'reprocess'])
        ->name('documents.reindex');
    Route::post('/search/semantic', [SearchController::class, 'semanticSearch'])
        ->name('search.semantic');
    Route::post('/search/analyze-query', [SearchController::class, 'analyzeQuery'])
        ->name('search.analyze-query');
});

// ===============================================
// Public Routes
// ===============================================

// Chat (доступно авторизованным)
Route::middleware(['auth'])->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/search', [ChatController::class, 'search'])->name('chat.search');
    Route::get('/chat/models/{brandId}', [ChatController::class, 'getModels'])->name('chat.models');
});

// Diagnostic Routes (доступно авторизованным)
Route::middleware(['auth'])->prefix('diagnostic')->name('diagnostic.')->group(function () {
    // Шаги диагностики
    Route::get('/', [DiagnosticController::class, 'step1'])->name('start');
    Route::post('/step2', [DiagnosticController::class, 'step2'])->name('step2');
    
    // GET роуты для отображения шагов (если нужны)
    Route::get('/step2', [DiagnosticController::class, 'showStep2'])->name('step2.show');
    Route::get('/step3', [DiagnosticController::class, 'showStep3'])->name('step3.show');
    
    Route::post('/step3', [DiagnosticController::class, 'step3'])->name('step3');
     Route::post('/step3/process', [DiagnosticController::class, 'processStep3'])->name('step3.process'); // POST для обработки
    Route::post('/analyze', [DiagnosticController::class, 'analyze'])->name('analyze');
    Route::get('/result/{case}', [DiagnosticController::class, 'result'])->name('result');
    
    // Консультации
    Route::post('/consultation/{case}/order', [DiagnosticController::class, 'orderConsultation'])->name('consultation.order');
    
    // AJAX для диагностики
    Route::get('/models/{brandId}', [DiagnosticController::class, 'getModels'])->name('models');
});

// Отчёты
    Route::get('/report/{case}', [ReportController::class, 'show'])->name('report.show');
    Route::get('/report/{case}/pdf', [ReportController::class, 'pdf'])->name('report.pdf');
    Route::post('/report/{case}/send', [ReportController::class, 'sendEmail'])->name('report.send');
    

// Главная страница перенаправляет на логин
Route::redirect('/', '/login')->middleware('guest');
Route::redirect('/', '/admin/dashboard')->middleware('auth');



// Маршрут для показа структуры проекта
Route::get('/project-info', [ProjectInfoController::class, 'showProjectInfo']);
Route::get('/project-info/database', [ProjectInfoController::class, 'showDatabaseStructure']);
Route::get('/project-info/models', [ProjectInfoController::class, 'showModels']);
Route::get('/project-info/controllers', [ProjectInfoController::class, 'showControllers']);
Route::get('/project-info/all', [ProjectInfoController::class, 'showAllInfo']);



// Consultation Routes
Route::middleware(['auth'])->prefix('consultation')->name('consultation.')->group(function () {
    Route::get('/order/{case}/{type?}', [ConsultationController::class, 'showOrderForm'])->name('order.form');
    Route::post('/order/{case}', [ConsultationController::class, 'orderConsultation'])->name('order');
    Route::get('/confirmation/{consultation}', [ConsultationController::class, 'confirmation'])->name('confirmation');
});

// Обновим существующий роут диагностики
Route::post('/diagnostic/consultation/{case}/order', [ConsultationController::class, 'orderConsultation'])->name('diagnostic.consultation.order');