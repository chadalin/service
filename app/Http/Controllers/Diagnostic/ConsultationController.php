<?php

namespace App\Http\Controllers\Diagnostic;

use App\Http\Controllers\Controller;
use App\Models\Diagnostic\Consultation;
use App\Models\Diagnostic\DiagnosticCase; // Предполагаем, что Case переименован
use App\Models\User;
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
    }
    
    // Получаем все активные случаи пользователя
    $cases = DiagnosticCase::where('user_id', $user->id)
        ->whereIn('status', ['report_ready', 'consultation_pending'])
        ->with(['brand', 'model'])
        ->get();
        
    // Получаем доступных экспертов
    $experts = User::where('role', 'expert')
    ->orWhere('role', 'admin')
    ->where('status', 'active')
    ->get();
       // Добавляем переменную $status с значением по умолчанию
        $consultations = collect([]); // Пустая коллекция
    $status = 'all';
    return view('diagnostic.consultation.client.order-form', compact('selectedCase', 'cases', 'experts', 'type','status','consultations'));
}
    
    /**
     * Заказать консультацию
     */
    public function orderConsultation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'case_id' => 'required|exists:diagnostic_cases,id',
            'type' => 'required|in:basic,premium,expert',
            'expert_id' => 'nullable|exists:users,id',
            'scheduled_at' => 'nullable|date|after:now',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // Проверяем, что случай принадлежит пользователю
        $case = DiagnosticCase::where('id', $request->case_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        // Рассчитываем цену в зависимости от типа
        $prices = [
            'basic' => 3000,
            'premium' => 5000,
            'expert' => 10000,
        ];
        
        $price = $prices[$request->type] ?? 3000;
        
        // Создаем консультацию
        $consultation = Consultation::create([
            'case_id' => $case->id,
            'user_id' => Auth::id(),
            'expert_id' => $request->expert_id,
            'type' => $request->type,
            'price' => $price,
            'status' => 'pending',
            'scheduled_at' => $request->scheduled_at,
        ]);
        
        // Обновляем статус случая
        $case->update([
            'status' => 'consultation_pending'
        ]);
        
        return redirect()->route('diagnostic.consultation.confirmation', $consultation)
            ->with('success', 'Консультация успешно заказана!');
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
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        $user = Auth::user();
        
        $query = Consultation::where('user_id', $user->id)
            ->with(['case.brand', 'case.model', 'expert'])
            ->latest();
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        $consultations = $query->paginate(10);
        
        return view('diagnostic.consultation.client.index', compact('consultations', 'status'));
    }
    
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
     * Отправить сообщение в чате консультации
     */
    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|min:1|max:2000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:5120', // 5MB max per file
        ]);
        
        $user = Auth::user();
        $consultation = Consultation::findOrFail($id);
        
        // Проверяем, что пользователь имеет доступ к консультации
        if ($consultation->user_id !== $user->id && $consultation->expert_id !== $user->id) {
            abort(403, 'Доступ запрещен');
        }
        
        $message = $consultation->messages()->create([
            'user_id' => $user->id,
            'message' => $request->message,
            'type' => 'text',
        ]);
        
        // Обработка вложений, если есть
        if ($request->hasFile('attachments')) {
            $attachments = [];
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('consultation-attachments/' . $consultation->id, 'public');
                $attachments[] = [
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ];
            }
            $message->metadata = json_encode(['attachments' => $attachments]);
            $message->save();
        }
        
        return response()->json(['success' => true, 'message' => $message->load('user')]);
    }
    
    /**
     * Получить сообщения консультации
     */
    public function getMessages($id)
    {
        $user = Auth::user();
        $consultation = Consultation::findOrFail($id);
        
        // Проверяем доступ
        if ($consultation->user_id !== $user->id && $consultation->expert_id !== $user->id) {
            abort(403, 'Доступ запрещен');
        }
        
        $messages = $consultation->messages()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();
            
        return response()->json($messages);
    }
    
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
}