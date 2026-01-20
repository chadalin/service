<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Diagnostic\Consultation;
use App\Models\User;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $expert = $request->get('expert');
        $type = $request->get('type');
        
        $query = Consultation::with(['case.brand', 'case.model', 'user', 'expert'])
            ->latest();
            
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($expert) {
            $query->where('expert_id', $expert);
        }
        
        if ($type) {
            $query->where('type', $type);
        }
        
        $consultations = $query->paginate(20);
        $experts = User::where('is_expert', true)->get();
        
        return view('admin.consultations.index', compact('consultations', 'status', 'experts', 'type'));
    }
    
    public function pending()
    {
        $consultations = Consultation::where('status', 'pending')
            ->with(['case.brand', 'case.model', 'user'])
            ->latest()
            ->paginate(20);
            
        return view('admin.consultations.pending', compact('consultations'));
    }
    
    public function inProgress()
    {
        $consultations = Consultation::where('status', 'in_progress')
            ->with(['case.brand', 'case.model', 'user', 'expert'])
            ->latest()
            ->paginate(20);
            
        return view('admin.consultations.in-progress', compact('consultations'));
    }
    
    public function show($id)
    {
        $consultation = Consultation::with([
            'case.brand',
            'case.model',
            'case.activeReport',
            'user',
            'expert',
            'messages' => function($query) {
                $query->with('user')->latest();
            }
        ])->findOrFail($id);
        
        return view('admin.consultations.show', compact('consultation'));
    }
    
    public function assignExpert(Request $request, $id)
    {
        $request->validate([
            'expert_id' => 'required|exists:users,id'
        ]);
        
        $consultation = Consultation::findOrFail($id);
        $consultation->update([
            'expert_id' => $request->expert_id,
            'status' => 'scheduled'
        ]);
        
        return back()->with('success', 'Эксперт назначен');
    }
    
    public function cancel($id)
    {
        $consultation = Consultation::findOrFail($id);
        $consultation->update(['status' => 'cancelled']);
        
        return back()->with('success', 'Консультация отменена');
    }
    
    public function statistics()
    {
        // Статистика за последние 30 дней
        $startDate = now()->subDays(30);
        
        $stats = [
            'total' => Consultation::where('created_at', '>=', $startDate)->count(),
            'completed' => Consultation::where('status', 'completed')
                ->where('created_at', '>=', $startDate)->count(),
            'in_progress' => Consultation::where('status', 'in_progress')->count(),
            'pending' => Consultation::where('status', 'pending')->count(),
            'revenue' => Consultation::where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->sum('price'),
            'avg_rating' => Consultation::whereNotNull('rating')
                ->where('created_at', '>=', $startDate)
                ->avg('rating'),
        ];
        
        // Распределение по типам
        $typeStats = Consultation::selectRaw('type, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('type')
            ->get();
            
        // Топ экспертов
        $topExperts = User::where('is_expert', true)
            ->withCount(['consultations as completed_count' => function($query) use ($startDate) {
                $query->where('status', 'completed')
                      ->where('created_at', '>=', $startDate);
            }])
            ->withAvg(['consultations as avg_rating' => function($query) {
                $query->where('status', 'completed')
                      ->whereNotNull('rating');
            }], 'rating')
            ->orderBy('completed_count', 'desc')
            ->limit(10)
            ->get();
        
        return view('admin.consultations.statistics', compact('stats', 'typeStats', 'topExperts'));
    }
}