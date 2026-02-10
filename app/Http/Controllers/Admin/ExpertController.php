<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Diagnostic\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class ExpertController extends Controller
{
    public function __construct()
    {
        //$this->middleware('auth');
       // $this->middleware('admin');
    }

    /**
     * Display a listing of the experts.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status', 'all');
        
        $query = User::where('expert_is_available', true)
            ->withCount([
                'consultations as total_consultations',
                'consultations as completed_consultations' => function($q) {
                    $q->where('status', 'completed');
                },
                'consultations as in_progress_consultations' => function($q) {
                    $q->where('status', 'in_progress');
                }
            ])
            ->withAvg('consultations', 'rating')
            ->orderBy('created_at', 'desc');
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }
        
        if ($status === 'active') {
            $query->where('status', 'active');
        } elseif ($status === 'inactive') {
            $query->where('status', 'inactive');
        }
        
        $experts = $query->paginate(20);
        
        return view('admin.experts.index', compact('experts', 'search', 'status'));
    }

    /**
     * Show the form for creating a new expert.
     */
    public function create()
    {
        $specializations = [
            'engine' => 'Двигатель',
            'transmission' => 'Трансмиссия',
            'electrics' => 'Электрика',
            'suspension' => 'Подвеска',
            'brakes' => 'Тормоза',
            'electronics' => 'Электроника',
            'diagnostics' => 'Диагностика',
            'general' => 'Общая диагностика',
        ];
        
        return view('admin.experts.create', compact('specializations'));
    }

    /**
     * Store a newly created expert in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'specialization' => 'nullable|array',
            'specialization.*' => 'string',
            'description' => 'nullable|string|max:1000',
            'hourly_rate' => 'nullable|numeric|min:0',
            'is_available' => 'boolean',
        ]);
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company_name' => $request->company_name,
            'phone' => $request->phone,
            'role' => 'expert',
            'expert_is_available' => true,
            'status' => 'active',
            'expert_data' => [
                'experience_years' => $request->experience_years,
                'specialization' => $request->specialization ?? [],
                'description' => $request->description,
                'hourly_rate' => $request->hourly_rate,
                'is_available' => $request->is_available ?? true,
                'created_at' => now(),
                'created_by' => auth()->id(),
            ],
        ]);
        
        return redirect()->route('admin.experts.index')
            ->with('success', 'Эксперт успешно создан');
    }

    /**
     * Display the specified expert.
     */
    public function show($id)
    {
        $expert = User::where('expert_is_available', true)
            ->withCount([
                'consultations as total_consultations',
                'consultations as completed_consultations' => function($q) {
                    $q->where('status', 'completed');
                },
                'consultations as pending_consultations' => function($q) {
                    $q->where('status', 'pending');
                },
                'consultations as in_progress_consultations' => function($q) {
                    $q->where('status', 'in_progress');
                },
            ])
            ->withAvg('consultations', 'rating')
            ->with(['consultations' => function($query) {
                $query->latest()->limit(10)->with('case.brand', 'case.model', 'user');
            }])
            ->findOrFail($id);
        
        $statistics = $this->getExpertStatistics($expert);
        
        return view('admin.experts.show', compact('expert', 'statistics'));
    }

    /**
     * Show the form for editing the specified expert.
     */
    public function edit($id)
    {
        $expert = User::where('expert_is_available', true)->findOrFail($id);
        
        $specializations = [
            'engine' => 'Двигатель',
            'transmission' => 'Трансмиссия',
            'electrics' => 'Электрика',
            'suspension' => 'Подвеска',
            'brakes' => 'Тормоза',
            'electronics' => 'Электроника',
            'diagnostics' => 'Диагностика',
            'general' => 'Общая диагностика',
        ];
        
        return view('admin.experts.edit', compact('expert', 'specializations'));
    }

    /**
     * Update the specified expert in storage.
     */
    public function update(Request $request, $id)
    {
        $expert = User::where('expert_is_available', true)->findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'specialization' => 'nullable|array',
            'specialization.*' => 'string',
            'description' => 'nullable|string|max:1000',
            'hourly_rate' => 'nullable|numeric|min:0',
            'is_available' => 'boolean',
            'status' => 'required|in:active,inactive',
        ]);
        
        $expert->update([
            'name' => $request->name,
            'email' => $request->email,
            'company_name' => $request->company_name,
            'phone' => $request->phone,
            'status' => $request->status,
            'expert_data' => array_merge($expert->expert_data ?? [], [
                'experience_years' => $request->experience_years,
                'specialization' => $request->specialization ?? [],
                'description' => $request->description,
                'hourly_rate' => $request->hourly_rate,
                'is_available' => $request->is_available ?? true,
                'updated_at' => now(),
                'updated_by' => auth()->id(),
            ]),
        ]);
        
        // Обновляем пароль, если указан
        if ($request->filled('password')) {
            $request->validate([
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);
            
            $expert->update([
                'password' => Hash::make($request->password),
            ]);
        }
        
        return redirect()->route('admin.experts.show', $expert->id)
            ->with('success', 'Данные эксперта обновлены');
    }

    /**
     * Remove the specified expert from storage.
     */
    public function destroy($id)
    {
        $expert = User::where('expert_is_available', true)->findOrFail($id);
        
        // Проверяем, нет ли активных консультаций
        $activeConsultations = $expert->consultations()
            ->whereIn('status', ['pending', 'in_progress', 'scheduled'])
            ->count();
            
        if ($activeConsultations > 0) {
            return back()->with('error', 'Нельзя удалить эксперта с активными консультациями');
        }
        
        // Снимаем флаг эксперта, но не удаляем пользователя
        $expert->update([
            'expert_is_available' => false,
            'expert_data' => array_merge($expert->expert_data ?? [], [
                'deactivated_at' => now(),
                'deactivated_by' => auth()->id(),
            ]),
        ]);
        
        return redirect()->route('admin.experts.index')
            ->with('success', 'Эксперт успешно деактивирован');
    }

    /**
     * Toggle expert status.
     */
    public function toggleStatus($id)
    {
        $expert = User::where('expert_is_available', true)->findOrFail($id);
        
        $newStatus = $expert->status === 'active' ? 'inactive' : 'active';
        
        $expert->update([
            'status' => $newStatus,
        ]);
        
        return back()->with('success', 'Статус эксперта изменен');
    }

    /**
     * Get expert statistics.
     */
    private function getExpertStatistics(User $expert)
    {
        $consultations = $expert->consultations()
            ->where('status', 'completed')
           // ->where('completed_at', '>=', now()->subDays(30))
            ->get();
        
        // Статистика по месяцам
        $monthlyStats = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->format('Y-m');
            $monthName = $date->translatedFormat('F Y');
            
            $monthConsultations = $consultations->filter(function($consultation) use ($month) {
                return substr($consultation->completed_at, 0, 7) === $month;
            });
            
            $monthlyStats[] = [
                'month' => $monthName,
                'consultations' => $monthConsultations->count(),
                'revenue' => $monthConsultations->sum('price'),
                'avg_rating' => $monthConsultations->avg('rating') ?? 0,
            ];
        }
        
        // Распределение по типам консультаций
        $typeStats = $expert->consultations()
            ->selectRaw('type, COUNT(*) as count, AVG(rating) as avg_rating, SUM(price) as revenue')
            ->where('status', 'completed')
            ->groupBy('type')
            ->get();
        
        // Среднее время консультации
        $avgDuration = $expert->consultations()
            ->where('status', 'completed')
            ->whereNotNull('duration')
            ->avg('duration');
        
        return [
            'monthly_stats' => $monthlyStats,
            'type_stats' => $typeStats,
            'avg_duration' => $avgDuration,
            'total_revenue' => $consultations->sum('price'),
            'avg_rating' => $consultations->avg('rating') ?? 0,
            'response_time_avg' => 0, // Можно добавить логику расчета
        ];
    }
}