<?php

namespace App\Http\Controllers;

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
       // $this->middleware('expert');
    }

    /**
     * Display the expert's profile.
     */
    public function profile()
    {
        $user = auth()->user();
        
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
        
        $consultationStats = [
            'total' => $user->consultations()->count(),
            'completed' => $user->consultations()->where('status', 'completed')->count(),
            'in_progress' => $user->consultations()->where('status', 'in_progress')->count(),
            'avg_rating' => $user->consultations()->whereNotNull('rating')->avg('rating') ?? 0,
            'total_revenue' => $user->consultations()->where('status', 'completed')->sum('price'),
        ];
        
        return view('expert.profile', compact('user', 'specializations', 'consultationStats'));
    }

    /**
     * Update the expert's profile.
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'specialization' => 'nullable|array',
            'specialization.*' => 'string',
            'description' => 'nullable|string|max:1000',
            'hourly_rate' => 'nullable|numeric|min:0',
            'is_available' => 'boolean',
        ]);
        
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'company_name' => $request->company_name,
            'phone' => $request->phone,
            'expert_data' => array_merge($user->expert_data ?? [], [
                'experience_years' => $request->experience_years,
                'specialization' => $request->specialization ?? [],
                'description' => $request->description,
                'hourly_rate' => $request->hourly_rate,
                'is_available' => $request->is_available ?? true,
                'updated_at' => now(),
            ]),
        ]);
        
        // Обновляем пароль, если указан
        if ($request->filled('password')) {
            $request->validate([
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);
            
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }
        
        return back()->with('success', 'Профиль успешно обновлен');
    }

    /**
     * Display the expert's schedule.
     */
    public function schedule()
    {
        $user = auth()->user();
        $schedule = $user->expert_data['schedule'] ?? $this->getDefaultSchedule();
        
        $workingHours = [
            '09:00-10:00', '10:00-11:00', '11:00-12:00', '12:00-13:00',
            '13:00-14:00', '14:00-15:00', '15:00-16:00', '16:00-17:00',
            '17:00-18:00', '18:00-19:00', '19:00-20:00',
        ];
        
        return view('expert.schedule', compact('user', 'schedule', 'workingHours'));
    }

    /**
     * Update the expert's schedule.
     */
    public function updateSchedule(Request $request)
    {
        $user = auth()->user();
        
        $request->validate([
            'schedule' => 'required|array',
            'schedule.*' => 'array',
            'schedule.*.*' => 'boolean',
            'max_consultations_per_day' => 'nullable|integer|min:1|max:20',
            'min_advance_booking_hours' => 'nullable|integer|min:1|max:168',
            'break_start' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|date_format:H:i|after:break_start',
        ]);
        
        $user->update([
            'expert_data' => array_merge($user->expert_data ?? [], [
                'schedule' => $request->schedule,
                'max_consultations_per_day' => $request->max_consultations_per_day ?? 8,
                'min_advance_booking_hours' => $request->min_advance_booking_hours ?? 2,
                'break_time' => $request->break_start && $request->break_end 
                    ? ['start' => $request->break_start, 'end' => $request->break_end]
                    : null,
                'schedule_updated_at' => now(),
            ]),
        ]);
        
        return back()->with('success', 'График работы обновлен');
    }

    /**
     * Display the expert's analytics.
     */
    public function analytics(Request $request)
    {
        $user = auth()->user();
        
        $period = $request->get('period', 'month');
        $startDate = $this->getStartDate($period);
        
        // Основная статистика
        $stats = [
            'total_consultations' => $user->consultations()
                ->where('created_at', '>=', $startDate)
                ->count(),
            'completed_consultations' => $user->consultations()
                ->where('status', 'completed')
                ->where('completed_at', '>=', $startDate)
                ->count(),
            'total_revenue' => $user->consultations()
                ->where('status', 'completed')
                ->where('completed_at', '>=', $startDate)
                ->sum('price'),
            'avg_rating' => $user->consultations()
                ->whereNotNull('rating')
                ->where('completed_at', '>=', $startDate)
                ->avg('rating') ?? 0,
            'avg_duration' => $user->consultations()
                ->where('status', 'completed')
                ->whereNotNull('duration')
                ->where('completed_at', '>=', $startDate)
                ->avg('duration') ?? 0,
        ];
        
        // Статистика по дням/неделям
        $dailyStats = $this->getDailyStats($user, $startDate);
        
        // Распределение по типам консультаций
        $typeStats = $user->consultations()
            ->selectRaw('type, COUNT(*) as count, AVG(rating) as avg_rating, SUM(price) as revenue')
            ->where('status', 'completed')
            ->where('completed_at', '>=', $startDate)
            ->groupBy('type')
            ->get();
        
        // Последние отзывы
        $recentFeedback = $user->consultations()
            ->whereNotNull('customer_feedback')
            ->where('status', 'completed')
            ->with('user')
            ->orderBy('feedback_at', 'desc')
            ->limit(10)
            ->get();
        
        return view('expert.analytics', compact(
            'user', 'stats', 'dailyStats', 'typeStats', 'recentFeedback', 'period'
        ));
    }

    /**
     * Get default schedule.
     */
    private function getDefaultSchedule()
    {
        $schedule = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        foreach ($days as $day) {
            $schedule[$day] = array_fill_keys([
                '09:00-10:00', '10:00-11:00', '11:00-12:00', '12:00-13:00',
                '13:00-14:00', '14:00-15:00', '15:00-16:00', '16:00-17:00',
                '17:00-18:00', '18:00-19:00',
            ], $day !== 'saturday' && $day !== 'sunday');
        }
        
        return $schedule;
    }

    /**
     * Get start date based on period.
     */
    private function getStartDate($period)
    {
        return match($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'quarter' => now()->subMonths(3),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };
    }

    /**
     * Get daily statistics.
     */
    private function getDailyStats(User $user, $startDate)
    {
        $consultations = $user->consultations()
            ->where('status', 'completed')
            ->where('completed_at', '>=', $startDate)
            ->selectRaw('DATE(completed_at) as date, COUNT(*) as count, AVG(rating) as avg_rating, SUM(price) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');
        
        $dailyStats = [];
        $currentDate = clone $startDate;
        $today = now();
        
        while ($currentDate <= $today) {
            $dateStr = $currentDate->format('Y-m-d');
            $consultation = $consultations->get($dateStr);
            
            $dailyStats[] = [
                'date' => $dateStr,
                'day' => $currentDate->translatedFormat('D'),
                'count' => $consultation->count ?? 0,
                'revenue' => $consultation->revenue ?? 0,
                'avg_rating' => $consultation->avg_rating ?? 0,
            ];
            
            $currentDate->addDay();
        }
        
        return $dailyStats;
    }
}