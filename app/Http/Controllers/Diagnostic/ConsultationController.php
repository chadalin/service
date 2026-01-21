<?php

namespace App\Http\Controllers\Diagnostic;

use App\Http\Controllers\Controller;
use App\Models\Diagnostic\Consultation;
use App\Models\Diagnostic\DiagnosticCase; // Предполагаем, что Case переименован
use App\Models\User;
use App\Models\Diagnostic\ConsultationMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
    public function confirmation($id)
    {
        $consultation = Consultation::where('id', $id)
            ->where('user_id', Auth::id())
            ->with(['case.brand', 'case.model', 'expert'])
            ->firstOrFail();
            
        return view('diagnostic.consultation.confirmation', compact('consultation'));
    }
    
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
                    'is_read' => !is_null($message->read_at),
                ];
            })
        ]);
    }
}