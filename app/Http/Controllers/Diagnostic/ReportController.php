<?php

namespace App\Http\Controllers\Diagnostic;

use App\Http\Controllers\Controller;
use App\Models\Diagnostic\Report;
use App\Models\Diagnostic\DiagnosticCase;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function __construct()
    {
       // $this->middleware('auth');
    }
    
    // Просмотр отчёта
    public function show($caseId)
    {
        try {
            // Безопасная загрузка с обработкой возможных null значений
            $case = DiagnosticCase::with([
                'activeReport',
                'brand' => function($query) {
                    return $query->withDefault([
                        'name' => 'Не указана',
                        'id' => null,
                    ]);
                },
                'model' => function($query) {
                    return $query->withDefault([
                        'name' => 'Не указана',
                        'id' => null,
                    ]);
                }
            ])->findOrFail($caseId);
            
            // Проверка прав доступа
            if ($case->user_id !== auth()->id() && !auth()->user()->is_admin) {
                abort(403, 'Доступ запрещён');
            }
            
            // Если отчета нет, создадим его
            if (!$case->activeReport) {
                $case->createBasicReport();
                $case->load('activeReport'); // Перезагружаем связь
            }
            
            return view('diagnostic.report.show', compact('case'));
            
        } catch (\Exception $e) {
            // Логируем ошибку для отладки
            \Log::error('Ошибка при загрузке отчета: ' . $e->getMessage(), [
                'caseId' => $caseId,
                'userId' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Показываем пользователю понятное сообщение
            return redirect()->route('diagnostic.start')
                ->with('error', 'Произошла ошибка при загрузке отчета. Попробуйте создать новую диагностику.');
        }
    }
    
    // Генерация PDF
    public function pdf($caseId)
    {
        try {
            $case = DiagnosticCase::with([
                'activeReport',
                'brand' => function($query) {
                    return $query->withDefault([
                        'name' => 'Не указана',
                        'id' => null,
                    ]);
                },
                'model' => function($query) {
                    return $query->withDefault([
                        'name' => 'Не указана',
                        'id' => null,
                    ]);
                }
            ])->findOrFail($caseId);
            
            $report = $case->activeReport;
            
            if (!$report) {
                $report = $case->createBasicReport();
            }
            
            $pdf = Pdf::loadView('diagnostic.report.pdf', compact('case', 'report'));
            
            return $pdf->download("diagnostic-report-{$caseId}.pdf");
            
        } catch (\Exception $e) {
            \Log::error('Ошибка при генерации PDF: ' . $e->getMessage(), [
                'caseId' => $caseId,
                'trace' => $e->getTraceAsString()
            ]);
            
            abort(404, 'Не удалось сгенерировать PDF отчет');
        }
    }
    
    // Отправить отчёт на email
    public function sendEmail(Request $request, $caseId)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
        
        try {
            $case = DiagnosticCase::findOrFail($caseId);
            
            // Проверка прав доступа
            if ($case->user_id !== auth()->id() && !auth()->user()->is_admin) {
                abort(403, 'Доступ запрещён');
            }
            
            $report = $case->activeReport;
            
            if (!$report) {
                $report = $case->createBasicReport();
            }
            
            // TODO: Реализовать отправку email
            // Временная реализация - просто логируем
            \Log::info('Запрос на отправку отчета по email', [
                'caseId' => $caseId,
                'email' => $request->email,
                'userId' => auth()->id(),
            ]);
            
            return back()->with('success', 'Запрос на отправку отчета принят. Функция временно в разработке.');
            
        } catch (\Exception $e) {
            \Log::error('Ошибка при отправке отчета по email: ' . $e->getMessage(), [
                'caseId' => $caseId,
                'email' => $request->email,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Произошла ошибка при отправке отчета.');
        }
    }
    
    // Просмотр списка отчетов пользователя
    public function index()
    {
        $cases = DiagnosticCase::where('user_id', auth()->id())
            ->with(['brand', 'model', 'activeReport'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('report.index', compact('cases'));
    }
}