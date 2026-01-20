<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Диагностический отчет - {{ $case->id }}</title>
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
            background: linear-gradient(to right, #1e40af, #1d4ed8);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 24px;
            margin: 0 0 10px 0;
        }
        
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .section-title {
            background: #f3f4f6;
            padding: 10px 15px;
            border-left: 4px solid #3b82f6;
            margin-bottom: 15px;
            font-weight: bold;
            font-size: 16px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dashed #e5e7eb;
        }
        
        .info-label {
            font-weight: 600;
            color: #6b7280;
        }
        
        .info-value {
            font-weight: 600;
            color: #111827;
        }
        
        .cause-item {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            background: #f9fafb;
        }
        
        .step-item {
            display: flex;
            margin-bottom: 8px;
            padding: 8px;
            background: #f8fafc;
            border-radius: 4px;
        }
        
        .step-number {
            background: #3b82f6;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
            font-size: 11px;
        }
        
        .cost-summary {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .total-cost {
            background: linear-gradient(to right, #1e40af, #1d4ed8);
            color: white;
            padding: 15px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 18px;
            text-align: center;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .table th {
            background: #f3f4f6;
            padding: 8px;
            text-align: left;
            font-weight: 600;
            border: 1px solid #e5e7eb;
        }
        
        .table td {
            padding: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            font-size: 10px;
            color: #6b7280;
        }
        
        .disclaimer {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 5px;
            padding: 10px;
            margin-top: 20px;
            font-size: 10px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Шапка отчета -->
    <div class="header">
        <h1>Диагностический отчет</h1>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">ID отчета:</span>
                <span class="info-value">{{ $case->id }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Дата:</span>
                <span class="info-value">{{ $report->created_at->format('d.m.Y H:i') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Автомобиль:</span>
                <span class="info-value">{{ $case->brand->name ?? '' }} {{ $case->model->name ?? '' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Год выпуска:</span>
                <span class="info-value">{{ $case->year ?? '—' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Пробег:</span>
                <span class="info-value">{{ $case->mileage ? number_format($case->mileage, 0, '', ' ') . ' км' : '—' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Тип двигателя:</span>
                <span class="info-value">{{ $case->engine_type ?? '—' }}</span>
            </div>
        </div>
    </div>

    <!-- Краткая сводка -->
    @if(!empty($report->summary))
    <div class="section">
        <div class="section-title">Краткая сводка</div>
        @foreach($report->summary as $item)
        <div style="margin-bottom: 8px; display: flex;">
            <span style="margin-right: 8px;">•</span>
            <span>{{ $item }}</span>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Возможные причины -->
    @if(!empty($report->possible_causes))
    <div class="section">
        <div class="section-title">Возможные причины</div>
        @foreach($report->possible_causes as $index => $cause)
        <div class="cause-item">
            <div style="display: flex; align-items: center; margin-bottom: 5px;">
                <div style="background: #ef4444; color: white; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 10px; font-size: 10px;">
                    {{ $index + 1 }}
                </div>
                <strong>{{ is_array($cause) ? ($cause['title'] ?? $cause) : $cause }}</strong>
            </div>
            @if(is_array($cause) && isset($cause['description']))
            <div style="font-size: 11px; color: #6b7280;">
                {{ $cause['description'] }}
            </div>
            @endif
            @if(is_array($cause) && isset($cause['probability']))
            <div style="margin-top: 5px; font-size: 10px;">
                Вероятность: <strong>{{ $cause['probability'] }}%</strong>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <!-- План диагностики -->
    @if(!empty($report->diagnostic_plan))
    <div class="section">
        <div class="section-title">План диагностики</div>
        @foreach($report->diagnostic_plan as $index => $step)
        <div class="step-item">
            <div class="step-number">{{ $index + 1 }}</div>
            <div>
                <div style="font-weight: 600; margin-bottom: 2px;">
                    {{ is_array($step) ? ($step['title'] ?? $step) : $step }}
                </div>
                @if(is_array($step) && isset($step['description']))
                <div style="font-size: 10px; color: #6b7280;">
                    {{ $step['description'] }}
                </div>
                @endif
                @if(is_array($step) && isset($step['estimated_time']))
                <div style="font-size: 9px; color: #9ca3af; margin-top: 3px;">
                    Время: {{ $step['estimated_time'] }} мин.
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Ориентировочная стоимость -->
    @if(!empty($report->estimated_costs))
    <div class="page-break"></div>
    <div class="section">
        <div class="section-title">Ориентировочная стоимость</div>
        <div class="cost-summary">
            @if(isset($report->estimated_costs['diagnostic']))
            <div class="info-item">
                <span class="info-label">Диагностика:</span>
                <span class="info-value">{{ number_format($report->estimated_costs['diagnostic'], 0, '', ' ') }} ₽</span>
            </div>
            @endif
            
            @if(isset($report->estimated_costs['work']))
            <div class="info-item">
                <span class="info-label">Работы:</span>
                <span class="info-value">{{ number_format($report->estimated_costs['work'], 0, '', ' ') }} ₽</span>
            </div>
            @endif
            
            @if(isset($report->estimated_costs['total_parts']))
            <div class="info-item">
                <span class="info-label">Запчасти:</span>
                <span class="info-value">{{ number_format($report->estimated_costs['total_parts'], 0, '', ' ') }} ₽</span>
            </div>
            @endif
            
            @if(isset($report->estimated_costs['total']))
            <div style="margin-top: 15px;">
                <div class="total-cost">
                    Итого: {{ number_format($report->estimated_costs['total'], 0, '', ' ') }} ₽
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Список запчастей -->
    @if(!empty($report->parts_list))
    <div class="section">
        <div class="section-title">Рекомендуемые запчасти</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Артикул</th>
                    <th style="width: 60px;">Кол-во</th>
                    <th style="width: 100px;">Цена</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report->parts_list as $part)
                <tr>
                    <td>{{ $part['name'] ?? '—' }}</td>
                    <td style="font-family: monospace;">{{ $part['code'] ?? '—' }}</td>
                    <td style="text-align: center;">{{ $part['quantity'] ?? 1 }}</td>
                    <td style="text-align: right; font-weight: 600;">
                        {{ $part['price'] ? number_format($part['price'], 0, '', ' ') . ' ₽' : '—' }}
                    </td>
                </tr>
                @endforeach
                @if(isset($report->estimated_costs['total_parts']))
                <tr style="font-weight: bold; background: #f8fafc;">
                    <td colspan="3" style="text-align: right;">Итого запчасти:</td>
                    <td style="text-align: right;">
                        {{ number_format($report->estimated_costs['total_parts'], 0, '', ' ') }} ₽
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    @endif

    <!-- Рекомендации -->
    @if(!empty($report->recommended_actions))
    <div class="section">
        <div class="section-title">Рекомендации</div>
        @foreach($report->recommended_actions as $action)
        <div style="margin-bottom: 10px; padding: 10px; background: #f0f9ff; border-radius: 4px;">
            <div style="font-weight: 600; margin-bottom: 3px;">
                {{ is_array($action) ? ($action['title'] ?? $action) : $action }}
            </div>
            @if(is_array($action) && isset($action['description']))
            <div style="font-size: 10px; color: #6b7280;">
                {{ $action['description'] }}
            </div>
            @endif
            @if(is_array($action) && isset($action['deadline']))
            <div style="font-size: 9px; color: #dc2626; margin-top: 3px;">
                Срок: {{ $action['deadline'] }}
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <!-- Важная информация -->
    <div class="footer">
        <div class="disclaimer">
            <strong>Важная информация:</strong> Отчет носит рекомендательный характер и не является окончательным диагнозом. 
            Фактическая стоимость ремонта может отличаться в зависимости от состояния автомобиля. 
            Рекомендуем проконсультироваться со специалистом перед началом работ.
        </div>
        <div style="text-align: center; margin-top: 15px;">
            <div>Сгенерировано системой LR Diagnostic Flow</div>
            <div>Дата генерации: {{ date('d.m.Y H:i') }}</div>
            @if($report->is_white_label && $report->partner_name)
            <div style="margin-top: 10px;">Партнер: {{ $report->partner_name }}</div>
            @endif
        </div>
    </div>
</body>
</html>