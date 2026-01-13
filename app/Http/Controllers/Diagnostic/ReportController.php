<?php

namespace App\Http\Controllers\Diagnostic;

use App\Http\Controllers\Controller;
use App\Models\Diagnostic\Report;
use App\Models\Diagnostic\Case as DiagnosticCase;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    // Просмотр отчёта
    public function show($caseId)
    {
        $case = DiagnosticCase::with('activeReport')->findOrFail($caseId);
        
        if (!$case->activeReport) {
            abort(404, 'Отчёт не найден');
        }
        
        // Проверка прав доступа
        if ($case->user_id !== auth()->id() && !auth()->user()->is_admin) {
            abort(403);
        }
        
        return view('diagnostic.report.show', compact('case'));
    }
    
    // Генерация PDF
    public function pdf($caseId)
    {
        $case = DiagnosticCase::with(['activeReport', 'brand', 'model'])->findOrFail($caseId);
        $report = $case->activeReport;
        
        if (!$report) {
            abort(404);
        }
        
        $pdf = Pdf::loadView('diagnostic.report.pdf', compact('case', 'report'));
        
        return $pdf->download("diagnostic-report-{$caseId}.pdf");
    }
    
    // Отправить отчёт на email
    public function sendEmail(Request $request, $caseId)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
        
        $case = DiagnosticCase::findOrFail($caseId);
        $report = $case->activeReport;
        
        if ($report && $report->sendToEmail($request->email)) {
            return back()->with('success', 'Отчёт отправлен на указанный email');
        }
        
        return back()->with('error', 'Не удалось отправить отчёт');
    }
}