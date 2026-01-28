<?php

namespace App\Http\Controllers\Diagnostic;

use App\Http\Controllers\Controller;
use App\Models\Diagnostic\Consultation;
use App\Models\Diagnostic\DiagnosticCase; // Предполагаем, что Case переименован
use App\Models\User;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Diagnostic\Rule;
use App\Models\Diagnostic\ConsultationMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ConsultationController extends Controller
{
    /**
     * Показать форму заказа консультации
     */
   public function showOrderForm(Request $request, $case = null, $type = 'basic')
    {
        $user = Auth::user();
        
        // Если case передан, проверяем принадлежность
        $selectedCase = null;
        if ($case) {
            $selectedCase = DiagnosticCase::where('user_id', $user->id)
                ->where('id', $case)
                ->with(['brand', 'model'])
                ->first();
                
            if (!$selectedCase) {
                abort(404, 'Диагностический случай не найден');
            }
        }
        
        // Получаем случаи, которые готовы для консультации
        $cases = DiagnosticCase::where('user_id', $user->id)
            ->whereIn('status', ['report_ready', 'consultation_pending'])
            ->with(['brand', 'model'])
            ->get();
            
        // Получаем экспертов - убираем проверку is_expert
$experts = User::where('status', 'active')
    ->where(function($query) {
        $query->where('role', 'expert')
              ->orWhere('role', 'admin');
    })
    ->get();
        
        // Статус для фильтрации
        $status = $request->input('status', 'all');
        
        // Запрос для диагностических случаев
        $query = DiagnosticCase::where('user_id', $user->id)
            ->with(['brand', 'model']);
        
        // Фильтрация по статусу
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        // Получаем с пагинацией
        $diagnosticCases = $query->latest()->paginate(10);
        
        // Статистика по статусам
        $statusStats = [
            'all' => DiagnosticCase::where('user_id', $user->id)->count(),
            'draft' => DiagnosticCase::where('user_id', $user->id)->where('status', 'draft')->count(),
            'analyzing' => DiagnosticCase::where('user_id', $user->id)->where('status', 'analyzing')->count(),
            'report_ready' => DiagnosticCase::where('user_id', $user->id)->where('status', 'report_ready')->count(),
            'consultation_pending' => DiagnosticCase::where('user_id', $user->id)->where('status', 'consultation_pending')->count(),
            'consultation_in_progress' => DiagnosticCase::where('user_id', $user->id)->where('status', 'consultation_in_progress')->count(),
            'completed' => DiagnosticCase::where('user_id', $user->id)->where('status', 'completed')->count(),
            'archived' => DiagnosticCase::where('user_id', $user->id)->where('status', 'archived')->count(),
        ];
        
        return view('diagnostic.consultation.client.order-form', compact(
            'selectedCase', 
            'cases', 
            'experts', 
            'type',
            'status',
            'diagnosticCases',
            'statusStats'
        ));
    }
    
     /**
     * Обработать заказ консультации
     */
    /**
 * Обработать заказ консультации
 */
/**
 * Обработать заказ консультации
 */
public function orderConsultation(Request $request, $caseId)
{
    $request->validate([
        'type' => 'required|in:basic,premium,expert',
        'expert_id' => 'nullable|exists:users,id',
        'scheduled_at' => 'nullable|date|after:now',
        'notes' => 'nullable|string|max:1000',
    ]);
    
    $user = Auth::user();
    $case = DiagnosticCase::where('user_id', $user->id)
        ->with(['brand', 'model'])
        ->findOrFail($caseId);
    
    // Цены по типам
    $prices = [
        'basic' => 500,
        'premium' => 1500,
        'expert' => $case->price_estimate ?? 3000,
    ];
    
    $price = $prices[$request->type] ?? 500;
    
    // Создаем запись о консультации
    $consultation = \App\Models\Diagnostic\Consultation::create([
        'case_id' => $case->id,
        'user_id' => $user->id,
        'expert_id' => $request->expert_id,
        'type' => $request->type,
        'price' => $price,
        'status' => 'pending',
        'scheduled_at' => $request->scheduled_at,
        'payment_status' => 'pending',
    ]);
    
    // Обновляем статус диагностического случая
    $case->update([
        'status' => 'consultation_pending',
    ]);
    
    // Создаем первое сообщение в чат консультации
    if ($request->notes) {
       \App\Models\Diagnostic\ConsultationMessage::create([
            'consultation_id' => $consultation->id,
            'user_id' => $user->id,
            'message' => $request->notes,
            'type' => 'text',
        ]);
    } else {
        // Стандартное приветственное сообщение
        \App\Models\ConsultationMessage::create([
            'consultation_id' => $consultation->id,
            'user_id' => $user->id,
            'message' => "Запрошена консультация типа: {$request->type}",
            'type' => 'system',
        ]);
    }
    
    return redirect()->route('diagnostic.consultation.show', $consultation->id)
        ->with('success', 'Консультация заказана! Ожидайте ответа эксперта.');
}
    /**
     * Показать список всех диагностических случаев
     */
    /**
 * Список всех диагностических случаев пользователя
 */
public function index(Request $request)
{
    $user = Auth::user();
    $status = $request->input('status', 'all');
    
    // Запрос для диагностических случаев
    $query = DiagnosticCase::where('user_id', $user->id)
        ->with(['brand', 'model', 'consultations' => function($q) {
            $q->with('expert')->latest();
        }]);
    
    // Фильтрация по статусу
    if ($status !== 'all') {
        $query->where('status', $status);
    }
    
    // Сортировка
    $sort = $request->input('sort', 'newest');
    switch ($sort) {
        case 'oldest':
            $query->orderBy('created_at', 'asc');
            break;
        case 'brand':
            $query->join('brands', 'diagnostic_cases.brand_id', '=', 'brands.id')
                  ->orderBy('brands.name', 'asc')
                  ->select('diagnostic_cases.*');
            break;
        case 'newest':
        default:
            $query->latest();
            break;
    }
    
    // Получаем с пагинацией
    $consultations = $query->paginate(15);
    
    // Статистика по статусам
    $statusStats = [
        'all' => DiagnosticCase::where('user_id', $user->id)->count(),
        'draft' => DiagnosticCase::where('user_id', $user->id)->where('status', 'draft')->count(),
        'analyzing' => DiagnosticCase::where('user_id', $user->id)->where('status', 'analyzing')->count(),
        'report_ready' => DiagnosticCase::where('user_id', $user->id)->where('status', 'report_ready')->count(),
        'consultation_pending' => DiagnosticCase::where('user_id', $user->id)->where('status', 'consultation_pending')->count(),
        'consultation_in_progress' => DiagnosticCase::where('user_id', $user->id)->where('status', 'consultation_in_progress')->count(),
        'completed' => DiagnosticCase::where('user_id', $user->id)->where('status', 'completed')->count(),
        'archived' => DiagnosticCase::where('user_id', $user->id)->where('status', 'archived')->count(),
    ];
    
    return view('diagnostic.consultation.client.index', compact(
        'consultations',
        'status',
        'statusStats'
    ));
}
    /**
     * Показать страницу подтверждения
     */
   // public function confirmation($id)
    //{
   //     $consultation = Consultation::where('id', $id)
    //        ->where('user_id', Auth::id())
    //        ->with(['case.brand', 'case.model', 'expert'])
    //        ->firstOrFail();
            
   ////     return view('diagnostic.consultation.confirmation', compact('consultation'));
  // }
    
    /**
     * Список консультаций пользователя (клиент)
     */
   // public function index(Request $request)
   // {
   //     $status = $request->input('status', 'all');
   //     $user = Auth::user();
   //     
    //    $query = Consultation::where('user_id', $user->id)
     //       ->with(['case.brand', 'case.model', 'expert'])
    //        ->latest();
     //   
     //   if ($status !== 'all') {
      //      $query->where('status', $status);
      //  }
        
     //   $consultations = $query->paginate(10);
        
     //   return view('diagnostic.consultation.client.index', compact('consultations', 'status'));
  //  }
    
    /**
     * Просмотр консультации для клиента
     */
    public function showClient($id)
    {
        $consultation = Consultation::where('id', $id)
            ->where('user_id', Auth::id())
            ->with([
                'case.brand',
                'case.model',
                'case.activeReport',
                'expert',
                'messages' => function($query) {
                    $query->with('user')->latest();
                }
            ])
            ->firstOrFail();
            
        return view('diagnostic.consultation.client.show', compact('consultation'));
    }
    
    /**
     * Добавить отзыв клиента
     */
    public function addFeedback(Request $request, $id)
    {
        $request->validate([
            'feedback' => 'required|string|min:10|max:1000',
            'rating' => 'required|integer|min:1|max:5',
        ]);
        
        $consultation = Consultation::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('status', 'completed')
            ->firstOrFail();
            
        $consultation->update([
            'customer_feedback' => $request->feedback,
            'rating' => $request->rating,
        ]);
        
        return back()->with('success', 'Спасибо за ваш отзыв!');
    }
    
    /**
     * Дашборд эксперта
     */
    public function expertDashboard(Request $request)
    {
        $expert = Auth::user();
        
        if (!$expert->is_expert) {
            abort(403, 'Доступ запрещен');
        }
        
        $status = $request->input('status', 'all');
        
        $query = Consultation::where('expert_id', $expert->id)
            ->with(['case.brand', 'case.model', 'user'])
            ->latest();
            
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        $consultations = $query->paginate(15);
        
        $stats = [
            'total' => Consultation::where('expert_id', $expert->id)->count(),
            'pending' => Consultation::where('expert_id', $expert->id)
                ->where('status', 'pending')->count(),
            'in_progress' => Consultation::where('expert_id', $expert->id)
                ->where('status', 'in_progress')->count(),
            'completed' => Consultation::where('expert_id', $expert->id)
                ->where('status', 'completed')->count(),
            'avg_rating' => Consultation::where('expert_id', $expert->id)
                ->whereNotNull('rating')
                ->avg('rating') ?? 0,
        ];
        
        return view('diagnostic.consultation.expert.dashboard', compact('consultations', 'stats', 'status'));
    }
    
    /**
     * Просмотр консультации для эксперта
     */
    public function showExpert($id)
    {
        $expert = Auth::user();
        
        if (!$expert->is_expert) {
            abort(403, 'Доступ запрещен');
        }
        
        $consultation = Consultation::where('id', $id)
            ->where('expert_id', $expert->id)
            ->with([
                'case.brand',
                'case.model',
                'case.activeReport',
                'case.symptoms',
                'user',
                'messages' => function($query) {
                    $query->with('user')->latest();
                }
            ])
            ->firstOrFail();
            
        return view('diagnostic.consultation.expert.show', compact('consultation'));
    }
    
    /**
     * Начать консультацию экспертом
     */
    public function startExpertConsultation($id)
    {
        $expert = Auth::user();
        
        if (!$expert->is_expert) {
            abort(403, 'Доступ запрещен');
        }
        
        $consultation = Consultation::where('id', $id)
            ->where('expert_id', $expert->id)
            ->where('status', 'scheduled')
            ->firstOrFail();
            
        $consultation->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
        
        return back()->with('success', 'Консультация начата');
    }
    
    /**
     * Добавить анализ эксперта
     */
    public function addAnalysis(Request $request, $id)
    {
        $request->validate([
            'analysis' => 'required|string|min:100|max:5000',
            'recommendations' => 'required|string|min:50|max:2000',
            'estimated_cost' => 'nullable|numeric|min:0',
            'estimated_time' => 'nullable|integer|min:1',
        ]);
        
        $expert = Auth::user();
        
        if (!$expert->is_expert) {
            abort(403, 'Доступ запрещен');
        }
        
        $consultation = Consultation::where('id', $id)
            ->where('expert_id', $expert->id)
            ->where('status', 'in_progress')
            ->firstOrFail();
            
        $consultation->update([
            'expert_notes' => json_encode([
                'analysis' => $request->analysis,
                'recommendations' => $request->recommendations,
                'estimated_cost' => $request->estimated_cost,
                'estimated_time' => $request->estimated_time,
                'added_at' => now(),
            ]),
        ]);
        
        return back()->with('success', 'Анализ добавлен');
    }
    
    /**
     * Запросить дополнительные данные у клиента
     */
    public function requestData(Request $request, $id)
    {
        $request->validate([
            'request_message' => 'required|string|min:20|max:1000',
            'required_data' => 'required|array|min:1',
        ]);
        
        $expert = Auth::user();
        
        if (!$expert->is_expert) {
            abort(403, 'Доступ запрещен');
        }
        
        $consultation = Consultation::where('id', $id)
            ->where('expert_id', $expert->id)
            ->whereIn('status', ['in_progress', 'scheduled'])
            ->firstOrFail();
            
        // Создаем сообщение с запросом данных
        $consultation->messages()->create([
            'user_id' => $expert->id,
            'message' => $request->request_message,
            'type' => 'data_request',
            'metadata' => json_encode([
                'required_data' => $request->required_data,
                'is_urgent' => $request->has('is_urgent'),
            ]),
        ]);
        
        return back()->with('success', 'Запрос данных отправлен клиенту');
    }
    
    /**
     * Завершить консультацию
     */
    public function completeConsultation($id)
    {
        $expert = Auth::user();
        
        if (!$expert->is_expert) {
            abort(403, 'Доступ запрещен');
        }
        
        $consultation = Consultation::where('id', $id)
            ->where('expert_id', $expert->id)
            ->where('status', 'in_progress')
            ->firstOrFail();
            
        $consultation->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        
        // Обновляем статус случая
        $consultation->case->update([
            'status' => 'completed'
        ]);
        
        return redirect()->route('diagnostic.consultation.expert-dashboard')
            ->with('success', 'Консультация завершена');
    }
    
   
    
    /**
     * Получить сообщения консультации
     */
    
    
    /**
     * Отметить сообщения как прочитанные
     */
    public function markAsRead(Request $request, $id)
    {
        $user = Auth::user();
        $consultation = Consultation::findOrFail($id);
        
        // Проверяем доступ
        if ($consultation->user_id !== $user->id && $consultation->expert_id !== $user->id) {
            abort(403, 'Доступ запрещен');
        }
        
        $consultation->messages()
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
            
        return response()->json(['success' => true]);
    }
    
    /**
     * Загрузить файл в консультацию
     */
    public function uploadFile(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string|max:500',
        ]);
        
        $user = Auth::user();
        $consultation = Consultation::findOrFail($id);
        
        // Проверяем доступ
        if ($consultation->user_id !== $user->id && $consultation->expert_id !== $user->id) {
            abort(403, 'Доступ запрещен');
        }
        
        $file = $request->file('file');
        $path = $file->store('consultation-files/' . $consultation->id, 'public');
        
        // Создаем сообщение с файлом
        $message = $consultation->messages()->create([
            'user_id' => $user->id,
            'message' => $request->description ?? 'Прикрепленный файл',
            'type' => 'file',
            'metadata' => json_encode([
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Файл успешно загружен',
            'file' => [
                'name' => $file->getClientOriginalName(),
                'url' => asset('storage/' . $path),
                'size' => $file->getSize(),
            ]
        ]);
    }

    /**
 * Показать консультацию с чатом
 */
public function show($id)
    {
        $consultation = Consultation::with([
            'user',
            'expert',
            'case' => function($query) {
                $query->with(['brand', 'model']);
            },
            'messages' => function($query) {
                $query->with('user')->orderBy('created_at', 'asc');
            }
        ])->findOrFail($id);
        
        // Проверка прав доступа
        $user = auth()->user();
        $hasAccess = $user->id === $consultation->user_id || 
                    $user->id === $consultation->expert_id || 
                    $user->role === 'admin';
        
        if (!$hasAccess) {
            abort(403, 'У вас нет доступа к этой консультации');
        }
        
        return view('diagnostic.consultation.client.show', compact('consultation'));
    }

/**
 * Отправить сообщение в консультации
 */


   public function sendMessage(Request $request, $id)
    {
        $consultation = Consultation::with(['user', 'expert', 'case'])->findOrFail($id);
        
        // Проверка прав доступа
        $user = auth()->user();
        $hasAccess = $user->id === $consultation->user_id || 
                    $user->id === $consultation->expert_id || 
                    $user->role === 'admin';
        
        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет доступа к этому чату'
            ], 403);
        }
        
        // Проверка статуса консультации
        if (!in_array($consultation->status, ['in_progress', 'scheduled', 'pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Чат закрыт для новых сообщений'
            ], 400);
        }
        
        $request->validate([
            'message' => 'required|string|max:5000',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max
        ]);
        
        // Создаем сообщение
        $message = new ConsultationMessage([
            'consultation_id' => $consultation->id,
            'user_id' => $user->id,
            'message' => $request->input('message'),
            'type' => 'text',
        ]);
        
        // Обработка вложений
        if ($request->hasFile('attachments')) {
            $attachments = [];
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('consultation_attachments/' . $consultation->id, 'public');
                $attachments[] = [
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ];
            }
            
            $message->type = count($attachments) > 0 ? 'file' : 'text';
            $message->metadata = ['attachments' => $attachments];
        }
        
        $message->save();
        $message->load('user');
        
        // Обновляем время обновления консультации
        $consultation->touch();
        
        // Если консультация была в статусе pending или scheduled и эксперт отправил первое сообщение,
        // автоматически переводим в статус in_progress
        if (in_array($consultation->status, ['pending', 'scheduled']) && 
            $user->id === $consultation->expert_id) {
            $consultation->update(['status' => 'in_progress']);
        }
        
        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'user_id' => $message->user_id,
                'user_name' => $message->user->name,
                'message' => $message->message,
                'type' => $message->type,
                'metadata' => $message->metadata,
                'created_at' => $message->created_at->toISOString(),
            ]
        ]);
    }
    
    /**
     * Получить новые сообщения
     */
     public function getMessages($id)
    {
        $consultation = Consultation::findOrFail($id);
        
        // Проверка прав доступа
        $user = auth()->user();
        $hasAccess = $user->id === $consultation->user_id || 
                    $user->id === $consultation->expert_id || 
                    $user->role === 'admin';
        
        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет доступа к этому чату'
            ], 403);
        }
        
        $lastId = request()->input('last_id', 0);
        
        $messages = $consultation->messages()
            ->with('user')
            ->where('id', '>', $lastId)
            ->orderBy('created_at', 'asc')
            ->get();
        
        return response()->json([
            'success' => true,
            'messages' => $messages->map(function($message) {
                return [
                    'id' => $message->id,
                    'user_id' => $message->user_id,
                    'user_name' => $message->user->name,
                    'message' => $message->message,
                    'type' => $message->type,
                    'metadata' => $message->metadata,
                    'created_at' => $message->created_at->toISOString(),
                    'read_at' => !is_null($message->read_at),
                ];
            })
        ]);
    }


     public function orderForm(Request $request)
    {
        try {
            Log::info('Consultation order form requested', $request->all());
            
            $data = [
                'consultationType' => $request->get('type', 'expert'),
                'brands' => Brand::orderBy('name')->get(),
                'models' => collect(),
                'symptoms' => [],
            ];
            
            // Если есть правило
            if ($request->has('rule') || $request->filled('rule_id')) {
                $ruleId = $request->rule ?? $request->rule_id;
                $rule = Rule::with(['symptom', 'brand', 'model'])->find($ruleId);
                
                if ($rule) {
                    $data['rule'] = $rule;
                    $data['brand_id'] = $rule->brand_id;
                    $data['model_id'] = $rule->model_id;
                    $data['symptoms'] = [$rule->symptom_id];
                    
                    // Получаем название симптома
                    if ($rule->symptom) {
                        $data['symptom_name'] = $rule->symptom->name;
                        $data['symptom_description'] = $rule->symptom->description;
                    }
                    
                    // Загружаем модели для выбранной марки
                    if ($rule->brand_id) {
                        $data['models'] = CarModel::where('brand_id', $rule->brand_id)->get();
                    }
                }
            }
            
            // Если есть кейс
            if ($request->has('case') || $request->filled('case_id')) {
                $caseId = $request->case ?? $request->case_id;
                $case = DiagnosticCase::with(['brand', 'model'])->find($caseId);
                
                if ($case) {
                    $data['case'] = $case;
                    $data['brand_id'] = $case->brand_id;
                    $data['model_id'] = $case->model_id;
                    $data['year'] = $case->year;
                    $data['mileage'] = $case->mileage;
                    $data['engine_type'] = $case->engine_type;
                    $data['symptoms'] = $case->symptoms;
                    
                    // Загружаем модели для выбранной марки
                    if ($case->brand_id) {
                        $data['models'] = CarModel::where('brand_id', $case->brand_id)->get();
                    }
                    
                    // Получаем названия симптомов
                    $symptomNames = Symptom::whereIn('id', $case->symptoms)
                        ->pluck('name')
                        ->toArray();
                    $data['symptom_names'] = $symptomNames;
                }
            }
            
            // Если переданы прямые параметры
            if ($request->filled('brand_id')) {
                $data['brand_id'] = $request->brand_id;
                $data['models'] = CarModel::where('brand_id', $request->brand_id)->get();
            }
            
            if ($request->filled('model_id')) {
                $data['model_id'] = $request->model_id;
            }
            
            if ($request->filled('year')) {
                $data['year'] = $request->year;
            }
            
            if ($request->filled('mileage')) {
                $data['mileage'] = $request->mileage;
            }
            
            if ($request->filled('engine_type')) {
                $data['engine_type'] = $request->engine_type;
            }
            
            if ($request->filled('symptoms')) {
                $data['symptoms'] = is_array($request->symptoms) 
                    ? $request->symptoms 
                    : explode(',', $request->symptoms);
            }
            
            return view('diagnostic.consultation.order', $data);
            
        } catch (\Exception $e) {
            Log::error('Consultation order form error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return redirect()->route('diagnostic.start')
                ->with('error', 'Ошибка загрузки формы: ' . $e->getMessage());
        }
    }
    
     /**
     * Заказ консультации из правила
     */
    public function orderFromRule($ruleId)
    { 
        try {
            Log::info('Order from rule requested', ['rule_id' => $ruleId]);
            
            $rule = Rule::with(['symptom', 'brand', 'model'])->findOrFail($ruleId);
            // Установите значение по умолчанию для consultationType
            $consultationType = $request->query('type', 'basic');
            // Редирект на универсальную форму с параметрами
            return redirect()->route('consultation.order.form', [
                'rule' => $ruleId,
                'type' => 'expert',
                'brand_id' => $rule->brand_id,
                'model_id' => $rule->model_id,
                'consultationType' => $consultationType,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Order from rule error', [
                'rule_id' => $ruleId,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('diagnostic.start')
                ->with('error', 'Правило не найдено: ' . $e->getMessage());
        }
    }
    
     /**
     * Заказ консультации из кейса
     */
    public function orderFromCase($caseId)
    {
        try {
            Log::info('Order from case requested', ['case_id' => $caseId]);
            
            $case = DiagnosticCase::with(['brand', 'model'])->findOrFail($caseId);
            
            // Редирект на универсальную форму с параметрами
            return redirect()->route('consultation.order.form', [
                'case' => $caseId,
                'type' => 'expert',
                'brand_id' => $case->brand_id,
                'model_id' => $case->model_id,
                'year' => $case->year,
                'mileage' => $case->mileage,
                'engine_type' => $case->engine_type,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Order from case error', [
                'case_id' => $caseId,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('diagnostic.start')
                ->with('error', 'Кейс не найден: ' . $e->getMessage());
        }
    }
    
     /**
     * Обработка заказа консультации
     */
    public function order(Request $request)
    {
        try {
            Log::info('Consultation order submitted', $request->except(['_token']));
            
            $validator = Validator::make($request->all(), [
                'consultation_type' => 'required|in:basic,premium,expert',
                'rule_id' => 'nullable|exists:diagnostic_rules,id',
                'case_id' => 'nullable|exists:diagnostic_cases,id',
                'brand_id' => 'required|exists:brands,id',
                'model_id' => 'nullable|exists:car_models,id',
                'year' => 'nullable|integer|min:1990|max:' . date('Y'),
                'mileage' => 'nullable|integer|min:0|max:1000000',
                'engine_type' => 'nullable|string',
                'description' => 'nullable|string|max:2000',
                'contact_name' => 'required|string|max:255',
                'contact_phone' => 'required|string|max:20',
                'contact_email' => 'required|email|max:255',
                'symptoms' => 'nullable|array',
                'agreement' => 'required|accepted',
            ], [
                'agreement.required' => 'Необходимо согласиться с условиями',
                'agreement.accepted' => 'Необходимо согласиться с условиями',
                'contact_name.required' => 'Укажите ваше имя',
                'contact_phone.required' => 'Укажите телефон для связи',
                'contact_email.required' => 'Укажите email для связи',
                'contact_email.email' => 'Укажите корректный email',
                'brand_id.required' => 'Выберите марку автомобиля',
                'brand_id.exists' => 'Выбранная марка не найдена',
            ]);
            
            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }
            
            // Рассчитываем цену
            $price = $this->calculatePrice(
                $request->consultation_type, 
                $request->rule_id
            );
            
            // Создаем консультацию
            $consultation = Consultation::create([
                'user_id' => Auth::id(),
                'rule_id' => $request->rule_id,
                'case_id' => $request->case_id,
                'brand_id' => $request->brand_id,
                'model_id' => $request->model_id,
                'year' => $request->year,
                'mileage' => $request->mileage,
                'engine_type' => $request->engine_type,
                'consultation_type' => $request->consultation_type,
                'description' => $request->description,
                'contact_name' => $request->contact_name,
                'contact_phone' => $request->contact_phone,
                'contact_email' => $request->contact_email,
                'price' => $price,
                'status' => 'pending',
                'payment_status' => 'pending',
                'symptoms' => $request->symptoms ?? [],
                'additional_data' => [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'created_at' => now()->toDateTimeString(),
                ],
            ]);
            
            Log::info('Consultation created', [
                'id' => $consultation->id,
                'type' => $consultation->consultation_type,
                'price' => $price
            ]);
            
            // Отправка уведомлений (можно добавить позже)
            // $this->sendNotifications($consultation);
            
            return redirect()->route('consultation.confirmation', $consultation->id)
                ->with('success', 'Заказ консультации создан успешно!');
            
        } catch (\Exception $e) {
            Log::error('Consultation order error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->except(['_token'])
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Ошибка при создании заказа: ' . $e->getMessage());
        }
    }
    
   /**
     * Расчет цены консультации
     */
    private function calculatePrice($type, $ruleId = null)
    {
        $basePrices = [
            'basic' => 500,
            'premium' => 1500,
            'expert' => 3000,
        ];
        
        $price = $basePrices[$type] ?? 3000;
        
        // Для экспертной консультации берем цену из правила
        if ($type === 'expert' && $ruleId) {
            $rule = Rule::find($ruleId);
            if ($rule && $rule->base_consultation_price > 0) {
                $price = $rule->base_consultation_price;
            }
        }
        
        return $price;
    }
    
   /**
     * Страница подтверждения заказа
     */
    public function confirmation($consultationId)
    {
        try {
            $consultation = Consultation::with(['rule', 'case', 'brand', 'model'])
                ->findOrFail($consultationId);
            
            // Для гостей проверяем по email или телефону
            // Для авторизованных - по user_id
            if (Auth::check()) {
                if ($consultation->user_id !== Auth::id()) {
                    abort(403, 'Доступ запрещён');
                }
            } else {
                // Можно добавить проверку по токену или сессии
            }
            
            return view('diagnostic.consultation.confirmation', [
                'consultation' => $consultation,
                'title' => 'Подтверждение заказа консультации'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Consultation confirmation error', [
                'consultation_id' => $consultationId,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('diagnostic.start')
                ->with('error', 'Консультация не найдена');
        }
    }

     /**
     * AJAX загрузка моделей по марке
     */
    public function getModels($brandId)
    {
        try {
            $models = CarModel::where('brand_id', $brandId)
                ->orderBy('name')
                ->get(['id', 'name']);
            
            return response()->json([
                'success' => true,
                'models' => $models
            ]);
        } catch (\Exception $e) {
            Log::error('Get models error', [
                'brand_id' => $brandId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки моделей'
            ], 500);
        }
    }

    

   public function store(Request $request)
{
     // Код отладки ДОЛЖЕН быть здесь, в начале метода
    \Log::info('=== CONSULTATION STORE START ===');
    \Log::info('Full request data:', $request->all());
    \Log::info('Brand ID from request:', ['value' => $request->input('brand_id'), 'type' => gettype($request->input('brand_id'))]);
    
    // ВРЕМЕННО: посмотрим все бренды
    $allBrands = Brand::all();
    \Log::info('All brands:', $allBrands->map(function($b) {
        return ['id' => $b->id, 'name' => $b->name, 'cyrillic' => $b->name_cyrillic];
    })->toArray());
    
    $validated = $request->validate([
        'consultation_type' => 'required|in:basic,premium,expert',
        'contact_name' => 'required|string|max:255',
        'contact_phone' => 'required|string|max:20',
        'contact_email' => 'required|email',
        'brand_id' => 'required', // Убираем exists проверку
        'model_id' => 'nullable',
        'year' => 'nullable|integer|min:1990|max:' . date('Y'),
        'engine_type' => 'nullable|string|max:50',
        'vin' => 'nullable|string|max:17',
        'mileage' => 'nullable|integer|min:0|max:1000000',
        'description' => 'nullable|string|max:2000',
        'agreement' => 'required|accepted',
        'rule_id' => 'required|exists:diagnostic_rules,id',
        'symptoms' => 'nullable|array',
        'symptoms.*' => 'exists:diagnostic_symptoms,id',
        'symptom_description' => 'required|string|min:20|max:2000',
        'additional_info' => 'nullable|string|max:1000',
    ]);
    
    // Преобразуем строковый brand_id в числовой
    $brandId = $this->getBrandId($validated['brand_id']);
    if (!$brandId) {
        return response()->json([
            'success' => false,
            'message' => 'Марка автомобиля не найдена'
        ], 422);
    }
    
    // Преобразуем строковый model_id в числовой (если передан)
    $modelId = null;
    if (!empty($validated['model_id'])) {
        $modelId = $this->getModelId($validated['model_id'], $brandId);
    }
    
    // Создание диагностического кейса
    $case = DiagnosticCase::create([
        'user_id' => Auth::id(),
        'rule_id' => $validated['rule_id'],
        'brand_id' => $brandId,
        'model_id' => $modelId,
        'engine_type' => $validated['engine_type'] ?? null,
        'year' => $validated['year'] ?? null,
        'vin' => $validated['vin'] ?? null,
        'mileage' => $validated['mileage'] ?? null,
        'symptoms' => $validated['symptoms'] ?? [],
        'description' => $validated['description'] ?? $validated['symptom_description'],
        'status' => 'draft',
        'step' => 1,
        'contact_name' => $validated['contact_name'],
        'contact_phone' => $validated['contact_phone'],
        'contact_email' => $validated['contact_email'],
        'consultation_type' => $validated['consultation_type'],
        'price_estimate' => $this->calculatePrice($validated['consultation_type'], $validated['rule_id']),
    ]);
    
    // Обработка загруженных файлов
    if ($request->hasFile('protocol_files') || $request->hasFile('symptom_photos') || $request->hasFile('symptom_videos')) {
        $this->processConsultationFiles($case, $request);
    }
    
    // Сохраняем дополнительное описание симптома
    $additionalData = [
        'symptom_description' => $validated['symptom_description'],
        'additional_info' => $validated['additional_info'] ?? null,
    ];
    
    $case->update($additionalData);
    
    // Перенаправление на страницу успеха
    return response()->json([
        'success' => true,
        'message' => 'Заявка на консультацию успешно создана!',
        'case_id' => $case->id,
        'redirect_url' => route('consultation.success', $case->id)
    ]);
}

private function getBrandId($brandIdentifier)
{
    // Ищем любой бренд
    $brand = Brand::where('name', 'LIKE', '%' . $brandIdentifier . '%')->first();
    
    if ($brand) {
        return $brand->id; // Это будет строка "LAND_ROVER"
    }
    
    // Возвращаем первый попавшийся бренд
    return Brand::first()->id ?? 'ACURA';
}

private function getModelId($modelIdentifier, $brandId)
{
    // Если передан числовой ID
    if (is_numeric($modelIdentifier)) {
        return (int) $modelIdentifier;
    }
    
    // Если передано название, ищем по имени для конкретной марки
    $model = CarModel::where('brand_id', $brandId)
                     ->where(function($query) use ($modelIdentifier) {
                         $query->where('name', $modelIdentifier)
                               ->orWhere('name_cyrillic', $modelIdentifier);
                     })
                     ->first();
    
    return $model ? $model->id : null;
}


    
    private function processConsultationFiles($consultationOrder, $request)
    {
        $uploadedFiles = [];
        
        // Обработка протоколов
        if ($request->hasFile('protocol_files')) {
            foreach ($request->file('protocol_files') as $file) {
                $path = $file->store('consultations/' . $consultationOrder->id . '/protocols', 'public');
                $uploadedFiles[] = [
                    'type' => 'protocol',
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }
        
        // Обработка фото
        if ($request->hasFile('symptom_photos')) {
            foreach ($request->file('symptom_photos') as $file) {
                $path = $file->store('consultations/' . $consultationOrder->id . '/photos', 'public');
                $uploadedFiles[] = [
                    'type' => 'photo',
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }
        
        // Обработка видео
        if ($request->hasFile('symptom_videos')) {
            foreach ($request->file('symptom_videos') as $file) {
                $path = $file->store('consultations/' . $consultationOrder->id . '/videos', 'public');
                $uploadedFiles[] = [
                    'type' => 'video',
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }
        
        // Сохраняем описание симптома и дополнительные файлы
        if ($request->has('symptom_description')) {
            $consultationOrder->update([
                'symptom_description' => $request->input('symptom_description'),
                'additional_info' => $request->input('additional_info'),
                'uploaded_files' => $uploadedFiles,
            ]);
        }
    }
    
    public function success($id = null)
{
    if ($id === 'new' || $id === null) {
        // Для нового заказа без ID
        return view('consultation.success', [
            'case' => null,
            'isNew' => true
        ]);
    }
    
    $case = DiagnosticCase::findOrFail($id);
    
    // Проверка прав доступа
    if ($case->user_id !== Auth::id()) {
        abort(403, 'Доступ запрещён');
    }
    
    return view('consultation.success', [
        'case' => $case,
        'isNew' => false
    ]);
}
    
}
