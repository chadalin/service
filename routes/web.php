<?php

use App\Http\Controllers\Auth\PinAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\DocumentProcessingController;
use App\Http\Controllers\Admin\CarController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ExpertController;
use App\Http\Controllers\Admin\SymptomImportController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectInfoController;
use App\Http\Controllers\Diagnostic\AISearchController;
use App\Http\Controllers\Diagnostic\DiagnosticController;
use App\Http\Controllers\Diagnostic\EnhancedAISearchController;
use App\Http\Controllers\Diagnostic\ReportController;
use App\Http\Controllers\Diagnostic\ConsultationController;
use App\Http\Controllers\Admin\PriceItemController;
use App\Http\Controllers\Admin\PriceImportController;

use App\Http\Controllers\Diagnostic\Admin\SymptomController as DiagnosticSymptomController;
use App\Http\Controllers\Diagnostic\Admin\RuleController as DiagnosticRuleController;

use Illuminate\Support\Facades\Route;

// ===============================================
// PUBLIC ROUTES (без авторизации)
// ===============================================

// Главные страницы
Route::get('/index', [HomeController::class, 'index'])->name('home');
Route::get('/services', [HomeController::class, 'landing'])->name('services.landing');

// Auth Routes
Route::get('/login', [PinAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login/send-pin', [PinAuthController::class, 'sendPin'])->name('login.send-pin');
Route::get('/login/verify', [PinAuthController::class, 'showVerifyForm'])->name('login.verify');
Route::post('/login/verify', [PinAuthController::class, 'verifyPin'])->name('login.verify');
Route::post('/logout', [PinAuthController::class, 'logout'])->name('logout');

// Project info (для разработки)
Route::get('/project-info', [ProjectInfoController::class, 'showProjectInfo']);
Route::get('/project-info/database', [ProjectInfoController::class, 'showDatabaseStructure']);
Route::get('/project-info/models', [ProjectInfoController::class, 'showModels']);
Route::get('/project-info/controllers', [ProjectInfoController::class, 'showControllers']);
Route::get('/project-info/all', [ProjectInfoController::class, 'showAllInfo']);

// Test route
Route::get('/test-rule/{id}', function($id) {
    return "Test Route - Rule ID: " . $id;
});

// ===============================================
// AUTHENTICATED ROUTES (требуют авторизации)
// ===============================================

Route::middleware(['auth'])->group(function () {
    
    // Главная перенаправления
    Route::redirect('/', '/admin/dashboard');
    
    // ===============================================
    // CHAT ROUTES
    // ===============================================
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/search', [ChatController::class, 'search'])->name('chat.search');
    Route::get('/chat/models/{brandId}', [ChatController::class, 'getModels'])->name('chat.models');
    
    // ===============================================
    // AI SEARCH ROUTES
    // ===============================================
    Route::prefix('diagnostic/ai')->name('diagnostic.ai.')->group(function () {
        Route::get('/search', [AISearchController::class, 'index'])->name('search.page');
        Route::post('/search', [AISearchController::class, 'search'])->name('search');
        Route::get('/popular-symptoms', [AISearchController::class, 'getPopularSymptoms'])->name('popular');
        Route::get('/symptoms/by-system/{system}', [AISearchController::class, 'getSymptomsBySystem']);
    });
    
    // ===============================================
    // DIAGNOSTIC ROUTES
    // ===============================================
    Route::prefix('diagnostic')->name('diagnostic.')->group(function () {
        // Старт и шаги диагностики
        Route::get('/', [DiagnosticController::class, 'start'])->name('start');
        Route::get('/step1', [DiagnosticController::class, 'step1'])->name('step1');
        Route::post('/step1', [DiagnosticController::class, 'step2'])->name('step2.process'); // POST обработка шага 1
        Route::get('/step2', [DiagnosticController::class, 'showStep2'])->name('step2.show'); // GET отображение шага 2
        Route::post('/step2', [DiagnosticController::class, 'processStep2'])->name('step2.process'); // POST обработка шага 2
        Route::get('/step3', [DiagnosticController::class, 'showStep3'])->name('step3.show'); // GET отображение шага 3
        Route::post('/step3', [DiagnosticController::class, 'processStep3'])->name('step3.process'); // POST обработка шага 3
        Route::post('/analyze', [DiagnosticController::class, 'analyze'])->name('analyze');
        Route::get('/result/{case}', [DiagnosticController::class, 'result'])->name('result');
        
        // AJAX для диагностики
        Route::get('/models/{brandId}', [DiagnosticController::class, 'getModels'])->name('models');
        
        // ===============================================
        // CONSULTATION ROUTES (клиентские)
        // ===============================================
        Route::prefix('consultation')->name('consultation.')->group(function () {
            // Список консультаций пользователя
            Route::get('/', [ConsultationController::class, 'index'])->name('index');
            
            // Основные маршруты заказа
            Route::get('/order', [ConsultationController::class, 'orderForm'])->name('order.form');
            Route::post('/order', [ConsultationController::class, 'order'])->name('order');
            
            // Заказ из разных источников
            Route::get('/order/from-rule/{rule}', [ConsultationController::class, 'orderFromRule'])
                ->name('order.from-rule');
            Route::get('/order/from-case/{case}', [ConsultationController::class, 'orderFromCase'])
                ->name('order.from-case');
                
            // Просмотр консультации
            Route::get('/{consultation}', [ConsultationController::class, 'showClient'])->name('show');
            Route::post('/{consultation}/feedback', [ConsultationController::class, 'addFeedback'])->name('feedback');
            Route::delete('/{id}/cancel', [ConsultationController::class, 'cancel'])->name('cancel');
                
            // Подтверждение заказа
            Route::get('/confirmation/{consultation}', [ConsultationController::class, 'confirmation'])
                ->name('confirmation');
                
            // Чат консультации
            Route::post('/{consultation}/message', [ConsultationController::class, 'sendMessage'])->name('message');
            Route::get('/{consultation}/messages', [ConsultationController::class, 'getMessages'])->name('messages');
            Route::post('/{consultation}/upload', [ConsultationController::class, 'uploadFile'])->name('upload');
            Route::post('/{consultation}/read', [ConsultationController::class, 'markAsRead'])->name('read');
                
            // AJAX
            Route::get('/models/{brandId}', [ConsultationController::class, 'getModels'])
                ->name('models');
        });
        
        // ===============================================
        // REPORT ROUTES
        // ===============================================
        Route::prefix('report')->name('report.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/{case}', [ReportController::class, 'show'])->name('show');
            Route::get('/{case}/pdf', [ReportController::class, 'pdf'])->name('pdf');
            Route::post('/{case}/send-email', [ReportController::class, 'sendEmail'])->name('send-email');
        });
    });
    
    // ===============================================
    // CONSULTATION CHAT MESSAGES ROUTES
    // ===============================================
    Route::post('/diagnostic/consultation/{id}/message', [ConsultationController::class, 'sendMessage'])
        ->name('diagnostic.consultation.message');
    Route::get('/diagnostic/consultation/{id}/messages', [ConsultationController::class, 'getMessages'])
        ->name('diagnostic.consultation.messages');
});

// ===============================================
// ADMIN ROUTES (auth + внутри группы admin)
// ===============================================

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // ===============================================
    // DOCUMENT PROCESSING ROUTES
    // ===============================================
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
    
    // ===============================================
    // DOCUMENTS ROUTES
    // ===============================================
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::get('/create', [DocumentController::class, 'create'])->name('create');
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::get('/{document}', [DocumentController::class, 'show'])->name('show');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
        Route::get('/{document}/preview', [DocumentController::class, 'preview'])->name('preview');
        Route::get('/{document}/download', [DocumentController::class, 'download'])->name('download');
        Route::post('/{document}/reprocess', [DocumentController::class, 'reprocess'])->name('reprocess');
        
        // AJAX
        Route::get('/models/{brandId}', [DocumentController::class, 'getModels'])->name('models');
        Route::post('/upload-chunk', [DocumentController::class, 'uploadChunk'])->name('upload-chunk');
        Route::post('/check-file', [DocumentController::class, 'checkFile'])->name('check-file');
        Route::post('/batch-index', [DocumentController::class, 'batchIndex'])->name('batch-index');
    });
    
    // ===============================================
    // SEARCH ROUTES
    // ===============================================
    Route::prefix('search')->name('search.')->group(function () {
        Route::get('/', [SearchController::class, 'index'])->name('index');
        Route::post('/', [SearchController::class, 'search'])->name('perform');
        Route::get('/advanced', [SearchController::class, 'advancedSearch'])->name('advanced');
        Route::post('/semantic', [SearchController::class, 'semanticSearch'])->name('semantic');
        Route::post('/analyze-query', [SearchController::class, 'analyzeQuery'])->name('analyze-query');
        Route::get('/models/{brandId}', [SearchController::class, 'getModels'])->name('models');
    });
    
    // ===============================================
    // CATEGORIES ROUTES
    // ===============================================
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/create', [CategoryController::class, 'create'])->name('create');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
    });
    
    // ===============================================
    // CARS ROUTES
    // ===============================================
    Route::prefix('cars')->name('cars.')->group(function () {
        Route::get('/import', [CarController::class, 'importForm'])->name('import');
        Route::post('/import', [CarController::class, 'import'])->name('import.submit');
        Route::get('/brands', [CarController::class, 'brands'])->name('brands');
        Route::get('/models', [CarController::class, 'models'])->name('models');
    });
    
    // ===============================================
    // DIAGNOSTIC ADMIN ROUTES
    // ===============================================
    Route::prefix('diagnostic')->name('diagnostic.')->group(function () {
        // Симптомы
        Route::get('/symptoms', [DiagnosticSymptomController::class, 'index'])->name('symptoms.index');
        Route::get('/symptoms/create', [DiagnosticSymptomController::class, 'create'])->name('symptoms.create');
        Route::post('/symptoms', [DiagnosticSymptomController::class, 'store'])->name('symptoms.store');
         Route::get('/{symptom}', [DiagnosticSymptomController::class, 'show'])->name('symptoms.show');
        Route::get('/symptoms/{symptom}/edit', [DiagnosticSymptomController::class, 'edit'])->name('symptoms.edit');
        Route::put('/symptoms/{symptom}', [DiagnosticSymptomController::class, 'update'])->name('symptoms.update');
        Route::delete('/symptoms/{symptom}', [DiagnosticSymptomController::class, 'destroy'])->name('symptoms.destroy');
        
        // Правила
        Route::get('/rules', [DiagnosticRuleController::class, 'index'])->name('rules.index');
        Route::get('/rules/create', [DiagnosticRuleController::class, 'create'])->name('rules.create');
        Route::post('/rules', [DiagnosticRuleController::class, 'store'])->name('rules.store');
        Route::get('/rules/{rule}', [DiagnosticRuleController::class, 'show'])->name('rules.show');
        Route::get('/rules/{rule}/edit', [DiagnosticRuleController::class, 'edit'])->name('rules.edit');
        Route::put('/rules/{rule}', [DiagnosticRuleController::class, 'update'])->name('rules.update');
        Route::delete('/rules/{rule}', [DiagnosticRuleController::class, 'destroy'])->name('rules.destroy');
        Route::get('/rules/models/{brandId}', [DiagnosticRuleController::class, 'getModels'])->name('rules.models');
    });
    
    // ===============================================
    // SYMPTOM IMPORT ROUTES
    // ===============================================
    Route::prefix('symptoms')->name('symptoms.')->group(function () {
        Route::get('/import', [SymptomImportController::class, 'index'])->name('import.page');
        Route::get('/import/select', [SymptomImportController::class, 'selectBrandModel'])->name('import.select');
        Route::post('/import/brand-model', [SymptomImportController::class, 'importForBrandModel'])->name('import.brand-model');
        Route::post('/import/auto', [SymptomImportController::class, 'importAuto'])->name('import.auto');
        Route::get('/import/template', [SymptomImportController::class, 'downloadTemplate'])->name('import.template');
        Route::get('/get-models/{brandId}', [SymptomImportController::class, 'getModels'])->name('import.models');
        Route::get('/existing-data', [SymptomImportController::class, 'getExistingData'])->name('import.existing-data');
    });
    
    // ===============================================
    // CONSULTATIONS ADMIN ROUTES
    // ===============================================
    Route::prefix('consultations')->name('consultations.')->group(function () {
        Route::get('/', [ConsultationController::class, 'index'])->name('index');
        Route::get('/pending', [ConsultationController::class, 'pending'])->name('pending');
        Route::get('/in-progress', [ConsultationController::class, 'inProgress'])->name('in-progress');
        Route::get('/{consultation}', [ConsultationController::class, 'show'])->name('show');
        Route::post('/{consultation}/assign', [ConsultationController::class, 'assignExpert'])->name('assign');
        Route::post('/{consultation}/cancel', [ConsultationController::class, 'cancel'])->name('cancel');
        Route::get('/statistics', [ConsultationController::class, 'statistics'])->name('statistics');
        
        // Формы заказа (редиректы)
        Route::get('/consultation/order-form', function() {
            return redirect()->route('diagnostic.start')->with('info', 'Пожалуйста, сначала создайте диагностический случай');
        })->name('consultation.order-form');
        
        Route::get('/consultation/{consultation}', [ConsultationController::class, 'showClient'])->name('consultation.show-client');
    });
    
    // ===============================================
    // EXPERTS ADMIN ROUTES
    // ===============================================
    Route::prefix('experts')->name('experts.')->group(function () {
        Route::get('/', [ExpertController::class, 'index'])->name('index');
        Route::get('/create', [ExpertController::class, 'create'])->name('create');
        Route::post('/', [ExpertController::class, 'store'])->name('store');
        Route::get('/{expert}/edit', [ExpertController::class, 'edit'])->name('edit');
        Route::put('/{expert}', [ExpertController::class, 'update'])->name('update');
        Route::delete('/{expert}', [ExpertController::class, 'destroy'])->name('destroy');
        Route::post('/{expert}/toggle-status', [ExpertController::class, 'toggleStatus'])->name('toggle-status');
    });
    
    // ===============================================
    // DIAGNOSTICS STATS ROUTES
    // ===============================================
    Route::get('/diagnostic/stats', function() {
        return response()->json([
            'success' => true,
            'symptoms' => \App\Models\Diagnostic\Symptom::count(),
            'rules' => \App\Models\Diagnostic\Rule::count(),
        ]);
    })->name('diagnostic.stats');
    
    // ===============================================
    // TEST SEARCH ROUTES
    // ===============================================
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
});

// ===============================================
// EXPERT ROUTES (для экспертов)
// ===============================================

Route::middleware(['auth'])->prefix('expert')->name('expert.')->group(function () {
    // Профиль эксперта
    Route::get('/profile', [ExpertController::class, 'profile'])->name('profile.edit');
    Route::put('/profile', [ExpertController::class, 'updateProfile'])->name('profile.update');
    
    // Расписание
    Route::get('/schedule', [ExpertController::class, 'schedule'])->name('schedule.index');
    Route::put('/schedule', [ExpertController::class, 'updateSchedule'])->name('schedule.update');
    
    // Аналитика
    Route::get('/analytics', [ExpertController::class, 'analytics'])->name('analytics.index');
    
    // ===============================================
    // EXPERT CONSULTATION ROUTES
    // ===============================================
    Route::prefix('consultation')->name('consultation.')->group(function () {
        Route::get('/', [ConsultationController::class, 'expertDashboard'])->name('dashboard');
        Route::get('/{consultation}', [ConsultationController::class, 'showExpert'])->name('show');
        Route::post('/{consultation}/start', [ConsultationController::class, 'startExpertConsultation'])->name('start');
        Route::post('/{consultation}/analysis', [ConsultationController::class, 'addAnalysis'])->name('analysis');
        Route::post('/{consultation}/request-data', [ConsultationController::class, 'requestData'])->name('request-data');
        Route::post('/{consultation}/complete', [ConsultationController::class, 'completeConsultation'])->name('complete');
    });
});

// ===============================================
// LEGACY CONSULTATION ROUTES (для обратной совместимости)
// ===============================================

Route::middleware(['auth'])->group(function () {
    Route::get('/consultation/order/{case}/{type?}', [ConsultationController::class, 'showOrderForm'])
        ->name('consultation.order.form.legacy');
    Route::post('/consultation/order/{case}', [ConsultationController::class, 'orderConsultation'])
        ->name('consultation.order.legacy');
    Route::get('/consultation/confirmation/{consultation}', [ConsultationController::class, 'confirmation'])
        ->name('consultation.confirmation.legacy');
});

// ===============================================
// PUBLIC RULES VIEW (публичный доступ к правилам)
// ===============================================

Route::get('/diagnostic/public-rules/{id}', [DiagnosticRuleController::class, 'publicShow'])
    ->name('rules.public.show')
    ->where('id', '[0-9]+');

// ===============================================
// API ROUTES (для уведомлений и AJAX)
// ===============================================

Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('/consultations/unread-count', function() {
        $user = auth()->user();
        $count = 0;
        
        if ($user->is_expert) {
            $count += \App\Models\Diagnostic\Consultation::where('expert_id', $user->id)
                ->where('status', 'in_progress')
                ->whereHas('messages', function($query) use ($user) {
                    $query->where('user_id', '!=', $user->id)
                          ->where('is_read', false);
                })
                ->count();
        }
        
        $count += \App\Models\Diagnostic\Consultation::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->whereHas('messages', function($query) use ($user) {
                $query->where('user_id', '!=', $user->id)
                      ->where('is_read', false);
            })
            ->count();
            
        return response()->json(['unread_count' => $count]);
    });
    
    Route::get('/expert/pending-consultations', function() {
        $user = auth()->user();
        if (!$user->is_expert) {
            return response()->json(['count' => 0]);
        }
        
        $count = \App\Models\Diagnostic\Consultation::whereNull('expert_id')
            ->where('status', 'pending')
            ->count();
            
        return response()->json(['count' => $count]);
    });
});

// ===============================================
// GUEST REDIRECT
// ===============================================

Route::redirect('/', '/login')->middleware('guest');


Route::prefix('consultation')->group(function () {
    Route::get('/order/{case}/form', [ConsultationController::class, 'orderForm'])
        ->name('consultation.order.form');
});



// routes/web.php
Route::prefix('consultation')->middleware(['web', 'auth'])->group(function () {
    Route::get('/order/form', [ConsultationController::class, 'showOrderForm'])
        ->name('consultation.order.form');
    Route::post('/order', [ConsultationController::class, 'storeOrder'])
        ->name('consultation.order');
});




// routes/web.php
Route::middleware(['web', 'auth'])->group(function () {
    // Основной маршрут для формы консультации
    Route::get('/consultation/order', [ConsultationController::class, 'start'])
        ->name('consultation.order.form');
    
    Route::post('/consultation/order', [ConsultationController::class, 'store'])
        ->name('consultation.order');
    
    // Маршрут для страницы успеха
    Route::get('/consultation/success/{id}', [ConsultationController::class, 'success'])
        ->name('consultation.success');
    
    // Альтернативный маршрут для нового заказа
    Route::get('/consultation/success', [ConsultationController::class, 'successNew'])
        ->name('consultation.success.new');
});


// Экспертные консультации
Route::prefix('diagnostic/consultation/expert')->name('diagnostic.consultation.expert.')->group(function () {
    Route::get('/dashboard', function () {
        return view('diagnostic.consultation.expert.dashboard');
    })->name('dashboard');
    
    Route::get('/', function () {
        return redirect()->route('diagnostic.consultation.expert.dashboard');
    });
});


// Маршруты для расширенной обработки документов
Route::prefix('admin/documents')->name('admin.documents.')->group(function () {
    // Просмотр отдельной страницы документа
    Route::get('/{document}/page/{page}', [DocumentController::class, 'showPage'])
        ->name('page');
    
    // Предпросмотр документа
    Route::post('/{document}/preview', [DocumentProcessingController::class, 'previewDocument'])
        ->name('preview');
    
    // Полный парсинг
    Route::post('/{document}/parse-full', [DocumentProcessingController::class, 'parseFullDocument'])
        ->name('parse-full');
    
    // Прогресс обработки
    Route::get('/{document}/progress', [DocumentProcessingController::class, 'getProcessingProgress'])
        ->name('progress');
    
    // Список страниц
    Route::get('/{document}/pages', [DocumentProcessingController::class, 'getDocumentPages'])
        ->name('pages');
    
    // Экспорт
    Route::get('/{document}/export', [DocumentProcessingController::class, 'exportDocument'])
        ->name('export');
    
    // Переиндексация
    Route::post('/{document}/reindex', [DocumentProcessingController::class, 'reindexDocument'])
        ->name('reindex');
    
    // Статистика
    Route::get('/{document}/stats', [DocumentProcessingController::class, 'getDocumentStats'])
        ->name('stats');
    
    // Удаление предпросмотра
    Route::delete('/{document}/delete-preview', [DocumentProcessingController::class, 'deletePreview'])
        ->name('delete-preview');
    
    // Расширенная обработка
    Route::get('/{document}/processing/advanced', [DocumentProcessingController::class, 'advancedProcessing'])
        ->name('processing.advanced');

        // Расширенная обработка конкретного документа
    Route::get('/{id}/advanced', [DocumentProcessingController::class, 'advancedProcessing'])
        ->name('advanced');

          // API эндпоинты
    Route::post('/parse/{id}', [DocumentProcessingController::class, 'parseDocument'])
        ->name('parse');
    
    Route::post('/index/{id}', [DocumentProcessingController::class, 'indexDocument'])
        ->name('index');
    
    Route::post('/process/{id}', [DocumentProcessingController::class, 'processDocument'])
        ->name('process');
    
    Route::post('/parse-multiple', [DocumentProcessingController::class, 'parseMultiple'])
        ->name('parse.multiple');
    
    Route::post('/index-multiple', [DocumentProcessingController::class, 'indexMultiple'])
        ->name('index.multiple');
    
    Route::get('/status/{id}', [DocumentProcessingController::class, 'getStatus'])
        ->name('status');
    
    
});

Route::prefix('admin/documents-processing')->name('admin.documents.processing.')->group(function () {
    Route::get('/', [DocumentProcessingController::class, 'index'])->name('index');
    Route::get('/advanced/{id}', [DocumentProcessingController::class, 'advancedProcessing'])->name('advanced');
    
    // Основные операции
    Route::post('/create-preview/{id}', [DocumentProcessingController::class, 'createPreview'])->name('create-preview');
    Route::post('/parse-full/{id}', [DocumentProcessingController::class, 'parseFull'])->name('parse-full');
    Route::post('/reset-status/{id}', [DocumentProcessingController::class, 'resetStatus'])->name('reset-status');
    Route::post('/parse-multiple', [DocumentProcessingController::class, 'parseMultiple'])->name('parse-multiple');
    
    // Получение информации
    Route::get('/progress/{id}', [DocumentProcessingController::class, 'getProcessingProgress'])->name('progress');
    Route::get('/pages/{id}', [DocumentProcessingController::class, 'getDocumentPages'])->name('pages');
    Route::get('/stats/{id}', [DocumentProcessingController::class, 'getDocumentStats'])->name('stats');
    
    // Управление
    Route::delete('/delete-preview/{id}', [DocumentProcessingController::class, 'deletePreview'])->name('delete-preview');
});

Route::prefix('admin/documents-processing')->name('admin.documents.processing.')->group(function () {
    Route::get('/', [DocumentProcessingController::class, 'index'])->name('index');
    Route::get('/advanced/{id}', [DocumentProcessingController::class, 'advancedProcessing'])->name('advanced');
    
    // Основные операции
    Route::post('/create-preview/{id}', [DocumentProcessingController::class, 'createPreview'])->name('create-preview');
    Route::post('/parse-full/{id}', [DocumentProcessingController::class, 'parseFull'])->name('parse-full');
    Route::post('/parse/{id}', [DocumentProcessingController::class, 'parseDocument'])->name('parse');
    
    // Управление статусом
    Route::post('/reset-status/{id}', [DocumentProcessingController::class, 'resetStatus'])->name('reset-status');
    Route::post('/parse-multiple', [DocumentProcessingController::class, 'parseMultiple'])->name('parse-multiple');
    
    // Получение информации
    Route::get('/progress/{id}', [DocumentProcessingController::class, 'getProcessingProgress'])->name('progress');
    Route::get('/pages/{id}', [DocumentProcessingController::class, 'getDocumentPages'])->name('pages');
    Route::get('/stats/{id}', [DocumentProcessingController::class, 'getDocumentStats'])->name('stats');
    
    // Управление предпросмотром
    Route::delete('/delete-preview/{id}', [DocumentProcessingController::class, 'deletePreview'])->name('delete-preview');

    // Просмотр изображений
    Route::get('/images/{id}', [DocumentProcessingController::class, 'viewImages'])->name('images');
});




Route::get('/test-pdf-images/{id}', function($id) {
    $document = \App\Models\Document::find($id);
    if (!$document) {
        return 'Документ не найден';
    }
    
    $filePath = storage_path('app/' . $document->file_path);
    
    if (!file_exists($filePath)) {
        return 'Файл не найден: ' . $filePath;
    }
    
    // Проверяем доступность команд
    $commands = ['pdfimages', 'pdftoppm', 'convert'];
    $available = [];
    
    foreach ($commands as $cmd) {
        $which = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where' : 'which';
        $process = new Symfony\Component\Process\Process([$which, $cmd]);
        $process->run();
        $available[$cmd] = $process->isSuccessful();
    }
    
    // Пытаемся извлечь первую страницу
    $tempDir = storage_path('app/temp_test_' . time());
    mkdir($tempDir, 0755, true);
    
    $result = [
        'file_path' => $filePath,
        'file_exists' => file_exists($filePath),
        'file_size' => filesize($filePath),
        'commands' => $available,
    ];
    
    // Тестируем pdfimages
    if ($available['pdfimages']) {
        $output = [];
        $returnCode = 0;
        $command = "pdfimages -f 1 -l 1 -list \"{$filePath}\" 2>&1";
        exec($command, $output, $returnCode);
        
        $result['pdfimages_test'] = [
            'command' => $command,
            'return_code' => $returnCode,
            'output' => $output,
        ];
    }
    
    return response()->json($result);
});

// Price Import Routes
Route::prefix('admin/price')->name('admin.price.')->group(function () {
    Route::get('/import', [PriceImportController::class, 'selectBrand'])->name('import.select');
    Route::get('/import/page', [PriceImportController::class, 'index'])->name('import.page');
    Route::post('/import/preview', [PriceImportController::class, 'preview'])->name('import.preview');
    Route::post('/import/process', [PriceImportController::class, 'import'])->name('import.process');
    Route::get('/import/template', [PriceImportController::class, 'downloadTemplate'])->name('import.template');
    Route::get('/import/status', [PriceImportController::class, 'checkImportStatus'])->name('import.status');
    
    // Если нужен CRUD для прайс-листа
    Route::get('/', [PriceItemController::class, 'index'])->name('index');
    Route::get('/{priceItem}', [PriceItemController::class, 'show'])->name('show');
    Route::delete('/{priceItem}', [PriceItemController::class, 'destroy'])->name('destroy');
    Route::post('/{priceItem}/match-symptoms', [PriceItemController::class, 'matchSymptoms'])->name('match.symptoms');
});

Route::prefix('diagnostic')->group(function () {
    Route::get('/ai-search/enhanced', [EnhancedAISearchController::class, 'index'])
        ->name('diagnostic.ai-search.enhanced');
    
    Route::post('/ai-search/enhanced', [EnhancedAISearchController::class, 'enhancedSearch'])
        ->name('diagnostic.ai.enhanced.search');
    
    Route::get('/rules/{id}/with-parts', [EnhancedAISearchController::class, 'showRuleWithParts'])
        ->name('diagnostic.rules.with-parts');
});