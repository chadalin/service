<?php

use App\Http\Controllers\Auth\PinAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\CarController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Diagnostic\AISearchController;                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  use App\Http\Controllers\DemoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\DocumentProcessingController;
use App\Http\Controllers\Diagnostic\DiagnosticController;
use App\Http\Controllers\Diagnostic\ReportController;
use App\Http\Controllers\Diagnostic\Admin\SymptomController as DiagnosticSymptomController;
use App\Http\Controllers\Diagnostic\Admin\RuleController as DiagnosticRuleController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectInfoController;
use App\Http\Controllers\Diagnostic\ConsultationController;
use App\Http\Controllers\Admin\ExpertController;
use App\Http\Controllers\Diagnostic\Admin\RuleController;
use App\Http\Controllers\Admin\SymptomImportController;

// Главная посадочная страница (B2C)
Route::get('/index', [HomeController::class, 'index'])->name('home');

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
        Route::get('/admin/rules/create', [DiagnosticRuleController::class, 'create'])->name('rules.create');
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

    Route::get('/diagnostic/consultation/order-form', function() {
    return redirect()->route('diagnostic.start')->with('info', 'Пожалуйста, сначала создайте диагностический случай');
})->name('diagnostic.consultation.order-form')->middleware('auth');
    
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
    Route::get('/step2', [DiagnosticController::class, 'step2'])->name('step2');
    
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
  // Отчеты
Route::prefix('diagnostic/report')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('diagnostic.report.index');
    Route::get('/{case}', [ReportController::class, 'show'])->name('diagnostic.report.show');
    Route::get('/{case}/pdf', [ReportController::class, 'pdf'])->name('diagnostic.report.pdf');
    Route::post('/{case}/send-email', [ReportController::class, 'sendEmail'])->name('diagnostic.report.send-email');
});
    

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
// Консультации
Route::prefix('diagnostic/consultation')->group(function () {
    // Клиентские маршруты
    Route::get('/order/{case}/{type?}', [ConsultationController::class, 'showOrderForm'])->name('diagnostic.consultation.order');
    Route::post('/order/{case}', [ConsultationController::class, 'orderConsultation'])->name('diagnostic.consultation.order.submit');
    Route::get('/confirmation/{consultation}', [ConsultationController::class, 'confirmation'])->name('diagnostic.consultation.confirmation');
    Route::get('/', [ConsultationController::class, 'index'])->name('diagnostic.consultation.index');
    Route::get('/{consultation}', [ConsultationController::class, 'showClient'])->name('diagnostic.consultation.show');
    Route::post('/{consultation}/feedback', [ConsultationController::class, 'addFeedback'])->name('diagnostic.consultation.feedback');
    Route::delete('/{id}/cancel', [ConsultationController::class, 'cancel'])->name('diagnostic.consultation.cancel');
    
    
    // Экспертные маршруты
    Route::prefix('expert')->group(function () {
        Route::get('/', [ConsultationController::class, 'expertDashboard'])->name('diagnostic.consultation.expert.dashboard');
        Route::get('/{consultation}', [ConsultationController::class, 'showExpert'])->name('diagnostic.consultation.expert.show');
        Route::post('/{consultation}/start', [ConsultationController::class, 'startExpertConsultation'])->name('diagnostic.consultation.expert.start');
        Route::post('/{consultation}/analysis', [ConsultationController::class, 'addAnalysis'])->name('diagnostic.consultation.expert.analysis');
        Route::post('/{consultation}/request-data', [ConsultationController::class, 'requestData'])->name('diagnostic.consultation.expert.request-data');
        Route::post('/{consultation}/complete', [ConsultationController::class, 'completeConsultation'])->name('diagnostic.consultation.expert.complete');
    });
    
    // Общие маршруты для чата
    Route::post('/{consultation}/message', [ConsultationController::class, 'sendMessage'])->name('diagnostic.consultation.message');
    Route::post('/{consultation}/upload', [ConsultationController::class, 'uploadFile'])->name('diagnostic.consultation.upload');
    Route::get('/{consultation}/messages', [ConsultationController::class, 'getMessages'])->name('diagnostic.consultation.messages');
    Route::post('/{consultation}/read', [ConsultationController::class, 'markAsRead'])->name('diagnostic.consultation.read');
    
});

Route::middleware(['auth'])->group(function () {
    // ... другие маршруты
    
    // Чат консультации
    Route::post('/diagnostic/consultation/{id}/message', [ConsultationController::class, 'sendMessage'])
        ->name('diagnostic.consultation.message');
    
    Route::get('/diagnostic/consultation/{id}/messages', [ConsultationController::class, 'getMessages'])
        ->name('diagnostic.consultation.messages');
});


// Административные маршруты для консультаций
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::prefix('consultations')->group(function () {
        Route::get('/', [ConsultationController::class, 'index'])->name('admin.consultations.index');
        Route::get('/pending', [ConsultationController::class, 'pending'])->name('admin.consultations.pending');
        Route::get('/in-progress', [ConsultationController::class, 'inProgress'])->name('admin.consultations.in-progress');
        Route::get('/{consultation}', [ConsultationController::class, 'show'])->name('admin.consultations.show');
        Route::get('/consultation/order', [ConsultationController::class, 'showOrderForm'])->name('consultation.order-form');
        Route::get('/consultation/{consultation}', [ConsultationController::class, 'showClient'])->name('consultation.show-client');
        Route::post('/{consultation}/assign', [ConsultationController::class, 'assignExpert'])->name('admin.consultations.assign');
        Route::post('/{consultation}/cancel', [ConsultationController::class, 'cancel'])->name('admin.consultations.cancel');
        Route::get('/statistics', [ConsultationController::class, 'statistics'])->name('admin.consultations.statistics');
        
    });
    
    Route::prefix('experts')->group(function () {
        Route::get('/', [ExpertController::class, 'index'])->name('admin.experts.index');
        Route::get('/create', [ExpertController::class, 'create'])->name('admin.experts.create');
        Route::post('/', [ExpertController::class, 'store'])->name('admin.experts.store');
        Route::get('/{expert}/edit', [ExpertController::class, 'edit'])->name('admin.experts.edit');
        Route::put('/{expert}', [ExpertController::class, 'update'])->name('admin.experts.update');
        Route::delete('/{expert}', [ExpertController::class, 'destroy'])->name('admin.experts.destroy');
        Route::post('/{expert}/toggle-status', [ExpertController::class, 'toggleStatus'])->name('admin.experts.toggle-status');
    });
});

// Экспертные маршруты
Route::middleware(['auth', 'expert'])->prefix('expert')->group(function () {
    Route::get('/profile', [ExpertController::class, 'profile'])->name('expert.profile.edit');
    Route::put('/profile', [ExpertController::class, 'updateProfile'])->name('expert.profile.update');
    Route::get('/schedule', [ExpertController::class, 'schedule'])->name('expert.schedule.index');
    Route::put('/schedule', [ExpertController::class, 'updateSchedule'])->name('expert.schedule.update');
    Route::get('/analytics', [ExpertController::class, 'analytics'])->name('expert.analytics.index');
});

// API для уведомлений
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



// routes/web.php
// routes/web.php
Route::middleware(['auth'])->prefix('admin')->group(function() {
    // Импорт симптомов
    Route::get('/symptoms/import', [SymptomImportController::class, 'index'])
        ->name('admin.symptoms.import.page');
    Route::get('/symptoms/import/select', [SymptomImportController::class, 'selectBrandModel'])
        ->name('admin.symptoms.import.select');
    Route::post('/symptoms/import/brand-model', [SymptomImportController::class, 'importForBrandModel'])
        ->name('admin.symptoms.import.brand-model');
    Route::post('/symptoms/import/auto', [SymptomImportController::class, 'importAuto'])
        ->name('admin.symptoms.import.auto');
    Route::get('/symptoms/import/template', [SymptomImportController::class, 'downloadTemplate'])
        ->name('admin.symptoms.import.template');
    Route::get('/symptoms/get-models/{brandId}', [SymptomImportController::class, 'getModels']);
    Route::get('/symptoms/existing-data', [SymptomImportController::class, 'getExistingData']);
    
    // Статистика
    Route::get('/diagnostic/stats', function() {
        return response()->json([
            'success' => true,
            'symptoms' => \App\Models\Diagnostic\Symptom::count(),
            'rules' => \App\Models\Diagnostic\Rule::count(),
        ]);
    });
});



Route::middleware(['auth'])->group(function () {
    // AI поиск по симптомам
    Route::prefix('diagnostic/ai')->name('diagnostic.ai.')->group(function () {
        Route::get('/search', [AISearchController::class, 'index'])->name('search.page');
        Route::post('/search', [AISearchController::class, 'search'])->name('search');
        Route::get('/popular-symptoms', [AISearchController::class, 'getPopularSymptoms']);
        Route::get('/symptoms/by-system/{system}', [AISearchController::class, 'getSymptomsBySystem']);
    });
});


// Маршруты для правил (публичные)
Route::get('/diagnostic/rules/{id}', [RuleController::class, 'show'])->name('rules.show');

// Маршруты для диагностики и консультаций
Route::prefix('diagnostic')->group(function () {
    // ... существующие маршруты диагностики ...
    // например:
    //Route::get('/start', [DiagnosticController::class, 'start'])->name('diagnostic.start');
    //Route::get('/step1', [DiagnosticController::class, 'step1'])->name('diagnostic.step1');
    // ... остальные маршруты диагностики ...
    
    // Консультации - ВНУТРИ ТОГО ЖЕ PREFIX
    Route::prefix('consultation')->name('consultation.')->group(function () {
        Route::get('/order', [ConsultationController::class, 'orderForm'])->name('order.form');
        Route::post('/order', [ConsultationController::class, 'order'])->name('order');
        
        Route::get('/order/from-rule/{rule}', [ConsultationController::class, 'orderFromRule'])
            ->name('order.from-rule');
        
        Route::get('/order/from-case/{case}', [ConsultationController::class, 'orderFromCase'])
            ->name('order.from-case');
            
        Route::get('/confirmation/{consultation}', [ConsultationController::class, 'confirmation'])
            ->name('confirmation');
            
        // AJAX маршрут для загрузки моделей
        Route::get('/models/{brandId}', [ConsultationController::class, 'getModels'])
            ->name('models');
    });
});

// Временно добавьте простой маршрут для теста
Route::get('/test-rule/{id}', function($id) {
    return "Rule ID: " . $id;
});