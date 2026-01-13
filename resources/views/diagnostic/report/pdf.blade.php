<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчёт по диагностике #{{ substr($case->id, 0, 8) }}</title>
    <style>
        @page {
            margin: 20mm;
            size: A4;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            line-height: 1.6;
            color: #333;
            font-size: 12px;
        }
        
        .header {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        
        .header .subtitle {
            color: #93c5fd;
            font-size: 14px;
        }
        
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        
        .car-info {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .info-item {
            margin-bottom: 0;
        }
        
        .info-label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        
        .info-value {
            font-weight: bold;
            font-size: 13px;
            color: #1e293b;
        }
        
        .symptoms-list {
            background: #eff6ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .symptom-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        
        .symptom-icon {
            color: #3b82f6;
            margin-right: 8px;
            margin-top: 2px;
        }
        
        .causes-list {
            margin-left: 20px;
        }
        
        .cause-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 5px;
        }
        
        .cause-number {
            background: #fee2e2;
            color: #dc2626;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
            margin-right: 8px;
            flex-shrink: 0;
        }
        
        .diagnostic-steps {
            counter-reset: step-counter;
        }
        
        .step-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 8px;
            padding: 10px;
            background: #f8fafc;
            border-radius: 5px;
            border-left: 3px solid #3b82f6;
        }
        
        .step-item:before {
            counter-increment: step-counter;
            content: counter(step-counter);
            background: #3b82f6;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            margin-right: 10px;
            flex-shrink: 0;
        }
        
        .cost-estimate {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .cost-item {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .cost-type {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .cost-value {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
        }
        
        .total-cost {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .total-label {
            font-size: 14px;
            font-weight: bold;
        }
        
        .total-value {
            font-size: 24px;
            font-weight: bold;
        }
        
        .recommendations {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .recommendation-item {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            border-radius: 5px;
            padding: 10px;
            display: flex;
            align-items: center;
        }
        
        .recommendation-icon {
            color: #10b981;
            margin-right: 8px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            color: #64748b;
            font-size: 10px;
            text-align: center;
        }
        
        .footer .generated {
            margin-bottom: 5px;
        }
        
        .footer .contact {
            font-size: 9px;
            color: #94a3b8;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(59, 130, 246, 0.1);
            font-weight: bold;
            z-index: -1;
        }
        
        .complexity-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .complexity-high { background: #fee2e2; color: #dc2626; }
        .complexity-medium { background: #fef3c7; color: #d97706; }
        .complexity-low { background: #d1fae5; color: #059669; }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                font-size: 11px;
            }
            
            .header {
                background: #1e40af !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .total-cost {
                background: #1e40af !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <!-- Водяной знак -->
    <div class="watermark">LR DIAGNOSTIC FLOW</div>
    
    <!-- Заголовок -->
    <div class="header">
        <h1>ОТЧЁТ ПО ДИАГНОСТИКЕ</h1>
        <div class="subtitle">LR Diagnostic Flow • Экспертная система диагностики</div>
    </div>
    
    <!-- Информация о автомобиле -->
    <div class="section">
        <div class="section-title">Данные автомобиля</div>
        <div class="car-info">
            <div class="info-item">
                <div class="info-label">Марка</div>
                <div class="info-value">{{ $case->brand->name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Модель</div>
                <div class="info-value">{{ $case->model->name ?? 'Не указана' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Год выпуска</div>
                <div class="info-value">{{ $case->year ?? 'Не указан' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Тип двигателя</div>
                <div class="info-value">{{ $case->engine_type ?? 'Не указан' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Пробег</div>
                <div class="info-value">
                    {{ $case->mileage ? number_format($case->mileage, 0, '', ' ') . ' км' : 'Не указан' }}
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">VIN</div>
                <div class="info-value">{{ $case->vin ?? 'Не указан' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Дата анализа</div>
                <div class="info-value">{{ $case->created_at->format('d.m.Y H:i') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">ID кейса</div>
                <div class="info-value">{{ $case->id }}</div>
            </div>
        </div>
    </div>
    
    <!-- Описанные симптомы -->
    <div class="section">
        <div class="section-title">Описанные симптомы</div>
        <div class="symptoms-list">
            @foreach($case->symptoms as $symptomId)
                @php
                    $symptom = \App\Models\Diagnostic\Symptom::find($symptomId);
                @endphp
                @if($symptom)
                    <div class="symptom-item">
                        <div class="symptom-icon">•</div>
                        <div>
                            <strong>{{ $symptom->name }}</strong>
                            @if($symptom->description)
                                <div style="font-size: 11px; color: #4b5563; margin-top: 2px;">
                                    {{ $symptom->description }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
            @if($case->description)
                <div style="margin-top: 10px; padding: 10px; background: white; border-radius: 5px; border: 1px solid #d1d5db;">
                    <div style="font-size: 10px; color: #6b7280; margin-bottom: 2px;">Дополнительное описание:</div>
                    <div>{{ $case->description }}</div>
                </div>
            @endif
        </div>
    </div>
    
    @if($report = $case->activeReport)
        <div class="page-break"></div>
        
        <!-- Анализ проблемы -->
        <div class="section">
            <div class="section-title">Анализ проблемы</div>
            
            <!-- Возможные причины -->
            <div style="margin-bottom: 20px;">
                <div style="font-weight: bold; margin-bottom: 10px; color: #374151;">Возможные причины:</div>
                <div class="causes-list">
                    @foreach($report->possible_causes as $index => $cause)
                        <div class="cause-item">
                            <div class="cause-number">{{ $index + 1 }}</div>
                            <div>{{ $cause }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <!-- План диагностики -->
            <div style="margin-bottom: 20px;">
                <div style="font-weight: bold; margin-bottom: 10px; color: #374151;">План диагностики:</div>
                <div class="diagnostic-steps">
                    @foreach($report->diagnostic_plan as $step)
                        <div class="step-item">{{ $step }}</div>
                    @endforeach
                </div>
            </div>
        </div>
        
        <!-- Оценка стоимости -->
        @if($report->estimated_costs)
            <div class="section">
                <div class="section-title">Ориентировочная стоимость</div>
                <div class="cost-estimate">
                    @foreach($report->estimated_costs as $type => $cost)
                        @if($type !== 'total')
                            <div class="cost-item">
                                <div class="cost-type">{{ str_replace('_', ' ', $type) }}</div>
                                <div class="cost-value">{{ number_format($cost, 0, '', ' ') }} ₽</div>
                            </div>
                        @endif
                    @endforeach
                </div>
                
                @if(isset($report->estimated_costs['total']))
                    <div class="total-cost">
                        <div class="total-label">ОБЩАЯ ОРИЕНТИРОВОЧНАЯ СТОИМОСТЬ</div>
                        <div class="total-value">{{ number_format($report->estimated_costs['total'], 0, '', ' ') }} ₽</div>
                    </div>
                @endif
            </div>
        @endif
        
        <!-- Рекомендации -->
        @if($report->recommended_actions)
            <div class="section">
                <div class="section-title">Рекомендации</div>
                <div class="recommendations">
                    @foreach($report->recommended_actions as $action)
                        <div class="recommendation-item">
                            <div class="recommendation-icon">✓</div>
                            <div>{{ $action }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
    
    <!-- Информация для сервиса -->
    <div class="page-break"></div>
    
    <div class="section">
        <div class="section-title">Информация для сервисного центра</div>
        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 15px;">
            <div style="color: #92400e; font-weight: bold; margin-bottom: 10px;">Важная информация:</div>
            <ul style="color: #92400e; margin: 0; padding-left: 20px;">
                <li>Данный отчёт содержит предварительную диагностику на основе описанных симптомов</li>
                <li>Для точной диагностики требуется инструментальная проверка</li>
                <li>Рекомендуем проверить все указанные в отчёте возможные причины</li>
                <li>При возникновении вопросов, покажите этот отчёт специалисту</li>
                <li>Отчёт сгенерирован системой LR Diagnostic Flow и не является официальным документом</li>
            </ul>
        </div>
    </div>
    
    <!-- Контакты -->
    <div class="section">
        <div class="section-title">Контакты</div>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
            <div style="text-align: center;">
                <div style="font-weight: bold; color: #1e40af; margin-bottom: 5px;">Консультация эксперта</div>
                <div style="font-size: 11px; color: #4b5563;">Личный разбор вашего случая</div>
            </div>
            <div style="text-align: center;">
                <div style="font-weight: bold; color: #1e40af; margin-bottom: 5px;">Telegram-бот</div>
                <div style="font-size: 11px; color: #4b5563;">@lrdiagnostic_bot</div>
            </div>
            <div style="text-align: center;">
                <div style="font-weight: bold; color: #1e40af; margin-bottom: 5px;">Телефон</div>
                <div style="font-size: 11px; color: #4b5563;">+7 (999) 123-45-67</div>
            </div>
        </div>
    </div>
    
    <!-- Футер -->
    <div class="footer">
        <div class="generated">Сгенерировано системой LR Diagnostic Flow • {{ now()->format('d.m.Y H:i:s') }}</div>
        <div class="contact">www.lrdiagnostic.ru • ID отчёта: {{ $case->id }}</div>
        <div class="contact" style="margin-top: 5px;">
            Отчёт является собственностью LR Diagnostic Flow. Распространение без разрешения запрещено.
        </div>
    </div>
</body>
</html>