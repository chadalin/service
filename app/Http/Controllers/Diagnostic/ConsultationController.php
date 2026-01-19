<?php

namespace App\Http\Controllers\Diagnostic;

use App\Http\Controllers\Controller;
use App\Models\Diagnostic\DiagnosticCase;
use App\Models\Diagnostic\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsultationController extends Controller
{
    public function __construct()
    {
       // $this->middleware('auth');
    }

    // Показать форму заказа консультации
    public function showOrderForm($caseId, $type = 'basic')
    {
        $case = DiagnosticCase::with(['brand', 'model', 'rule'])->findOrFail($caseId);
        
        // Проверка прав доступа
        if ($case->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }
        
        $validTypes = ['basic', 'premium', 'expert'];
        $type = in_array($type, $validTypes) ? $type : 'basic';
        
        // Рассчитать цену
        $price = $this->calculatePrice($case, $type);
        
        return view('consultation.order', compact('case', 'type', 'price'));
    }
    
    // Создать заказ консультации
    public function orderConsultation(Request $request, $caseId)
    {
        $case = DiagnosticCase::findOrFail($caseId);
        
        // Проверка прав доступа
        if ($case->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }
        
        $request->validate([
            'type' => 'required|in:basic,premium,expert',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email',
            'preferred_contact' => 'required|in:phone,email,telegram',
            'comment' => 'nullable|string|max:1000',
        ]);
        
        // Рассчитать цену
        $price = $this->calculatePrice($case, $request->type);
        
        // Создать консультацию
        $consultation = Consultation::create([
            'case_id' => $case->id,
            'user_id' => Auth::id(),
            'type' => $request->type,
            'price' => $price,
            'customer_name' => $request->name,
            'customer_phone' => $request->phone,
            'customer_email' => $request->email,
            'preferred_contact' => $request->preferred_contact,
            'comment' => $request->comment,
            'status' => 'pending',
        ]);
        
        // Обновить статус кейса
        $case->update(['status' => 'consultation_pending']);
        
        // Здесь можно добавить отправку email, уведомление и т.д.
        
        return redirect()->route('consultation.confirmation', $consultation->id)
            ->with('success', 'Заказ консультации успешно оформлен!');
    }
    
    // Страница подтверждения заказа
    public function confirmation($consultationId)
    {
        $consultation = Consultation::with(['case', 'case.brand', 'case.model'])
            ->findOrFail($consultationId);
            
        // Проверка прав доступа
        if ($consultation->user_id !== Auth::id()) {
            abort(403, 'Доступ запрещён');
        }
        
        return view('consultation.confirmation', compact('consultation'));
    }
    
    // Рассчитать цену
    private function calculatePrice(DiagnosticCase $case, $type)
    {
        $basePrice = $case->rule->base_consultation_price ?? 3000;
        $complexity = $case->rule->complexity_level ?? 1;
        
        $multipliers = [
            'basic' => 1,
            'premium' => 1.5,
            'expert' => 2,
        ];
        
        $multiplier = $multipliers[$type] ?? 1;
        
        return $basePrice * $multiplier * ($complexity / 5);
    }
}