@extends('layouts.app')

@section('title', 'Заказ консультации')

@section('content')
<div class="container py-4">
    <!-- Вспомогательная функция прямо в шаблоне -->
    @php
        function getSymptomsArray($symptoms) {
            if (empty($symptoms)) {
                return [];
            }
            
            if (is_array($symptoms)) {
                return $symptoms;
            }
            
            if (is_string($symptoms)) {
                try {
                    $decoded = json_decode($symptoms, true);
                    return is_array($decoded) ? $decoded : [$symptoms];
                } catch (\Exception $e) {
                    return [$symptoms];
                }
            }
            
            return [];
        }
    @endphp

    <!-- Информация о выбранном случае -->
    @if($selectedCase)
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Выбранный диагностический случай</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Информация об автомобиле:</h6>
                    <p>
                        <strong>Марка:</strong> {{ $selectedCase->brand->name ?? 'Не указана' }}<br>
                        <strong>Модель:</strong> {{ $selectedCase->model->name ?? 'Не указана' }}<br>
                        <strong>Год:</strong> {{ $selectedCase->year ?? 'Не указан' }}<br>
                        <strong>Тип двигателя:</strong> {{ $selectedCase->engine_type ?? 'Не указан' }}<br>
                        <strong>Пробег:</strong> {{ $selectedCase->mileage ? number_format($selectedCase->mileage) . ' км' : 'Не указан' }}
                    </p>
                </div>
                <div class="col-md-6">
                    <h6>Симптомы:</h6>
                    @php
                        $symptoms = getSymptomsArray($selectedCase->symptoms);
                    @endphp
                    
                    @if(count($symptoms) > 0)
                        <ul class="mb-0">
                            @foreach($symptoms as $symptom)
                                <li>{{ is_string($symptom) ? $symptom : json_encode($symptom) }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0">Симптомы не указаны</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Форма заказа консультации -->
    @if($selectedCase && in_array($selectedCase->status, ['report_ready', 'consultation_pending']))
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Заказ консультации</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('consultation.order', $selectedCase->id) }}" method="POST">
                @csrf
                
                <div class="mb-4">
                    <h6>Выберите тип консультации:</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 {{ $type == 'basic' ? 'border-primary' : '' }}">
                                <div class="card-body text-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="type" 
                                               id="type_basic" value="basic" {{ $type == 'basic' ? 'checked' : '' }}>
                                    </div>
                                    <h6 class="card-title">Базовая</h6>
                                    <p class="card-text small text-muted">Общие рекомендации по диагностике</p>
                                    <h4 class="text-success">3 000 ₽</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 {{ $type == 'premium' ? 'border-primary' : '' }}">
                                <div class="card-body text-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="type" 
                                               id="type_premium" value="premium" {{ $type == 'premium' ? 'checked' : '' }}>
                                    </div>
                                    <h6 class="card-title">Премиум</h6>
                                    <p class="card-text small text-muted">Подробный анализ с проверкой кодов ошибок</p>
                                    <h4 class="text-success">5 000 ₽</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 {{ $type == 'expert' ? 'border-primary' : '' }}">
                                <div class="card-body text-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="type" 
                                               id="type_expert" value="expert" {{ $type == 'expert' ? 'checked' : '' }}>
                                    </div>
                                    <h6 class="card-title">Экспертная</h6>
                                    <p class="card-text small text-muted">Индивидуальная работа с экспертом</p>
                                    <h4 class="text-success">10 000 ₽</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Выбор эксперта -->
                <div class="mb-4" id="expertSection" style="display: none;">
                    <h6>Выберите эксперта (опционально):</h6>
                    <select name="expert_id" class="form-select" id="expertSelect">
                        <option value="">Система автоматически подберет эксперта</option>
                        @foreach($experts as $expert)
                            <option value="{{ $expert->id }}">
                                {{ $expert->name }}
                                @if($expert->expert_specialization)
                                    ({{ $expert->expert_specialization }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Предпочтительное время -->
                <div class="mb-4">
                    <h6>Предпочтительное время консультации:</h6>
                    <input type="datetime-local" name="scheduled_at" class="form-control" 
                           min="{{ date('Y-m-d\TH:i') }}">
                    <small class="text-muted">Оставьте пустым для ближайшего доступного времени</small>
                </div>

                <!-- Дополнительные заметки -->
                <div class="mb-4">
                    <h6>Дополнительные сведения:</h6>
                    <textarea name="notes" class="form-control" rows="3" 
                              placeholder="Опишите дополнительные детали проблемы, вопросы к эксперту и т.д."></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('diagnostic.result', $selectedCase->id) }}" class="btn btn-secondary">
                        Назад к отчету
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-chat-dots me-1"></i> Заказать консультацию
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Список всех диагностических случаев -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Все диагностические случаи</h5>
            <a href="{{ route('diagnostic.start') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Новая диагностика
            </a>
        </div>
        <div class="card-body">
            
            <!-- Фильтры по статусу -->
            <div class="mb-4">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a href="?status=all" class="btn btn-sm {{ $status == 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
                        Все <span class="badge bg-light text-dark ms-1">{{ $statusStats['all'] ?? 0 }}</span>
                    </a>
                    <a href="?status=report_ready" class="btn btn-sm {{ $status == 'report_ready' ? 'btn-success' : 'btn-outline-success' }}">
                        Готов к консультации <span class="badge bg-light text-dark ms-1">{{ $statusStats['report_ready'] ?? 0 }}</span>
                    </a>
                    <a href="?status=consultation_pending" class="btn btn-sm {{ $status == 'consultation_pending' ? 'btn-warning' : 'btn-outline-warning' }}">
                        Ожидает консультации <span class="badge bg-light text-dark ms-1">{{ $statusStats['consultation_pending'] ?? 0 }}</span>
                    </a>
                    <a href="?status=consultation_in_progress" class="btn btn-sm {{ $status == 'consultation_in_progress' ? 'btn-info' : 'btn-outline-info' }}">
                        Консультация в процессе <span class="badge bg-light text-dark ms-1">{{ $statusStats['consultation_in_progress'] ?? 0 }}</span>
                    </a>
                    <a href="?status=completed" class="btn btn-sm {{ $status == 'completed' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                        Завершено <span class="badge bg-light text-dark ms-1">{{ $statusStats['completed'] ?? 0 }}</span>
                    </a>
                </div>
            </div>

            @if($diagnosticCases->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Автомобиль</th>
                                <th>Год</th>
                                <th>Пробег</th>
                                <th>Симптомы</th>
                                <th>Статус</th>
                                <th>Создан</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($diagnosticCases as $caseItem)
                            <tr>
                                <td>
                                    <strong>#{{ substr($caseItem->id, 0, 8) }}</strong>
                                </td>
                                <td>
                                    @if($caseItem->brand && $caseItem->model)
                                        <strong>{{ $caseItem->brand->name }}</strong><br>
                                        <small>{{ $caseItem->model->name }}</small>
                                    @else
                                        <em class="text-muted">Не указан</em>
                                    @endif
                                </td>
                                <td>{{ $caseItem->year ?? '—' }}</td>
                                <td>
                                    @if($caseItem->mileage)
                                        <span class="badge bg-light text-dark">
                                            {{ number_format($caseItem->mileage) }} км
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $caseSymptoms = getSymptomsArray($caseItem->symptoms);
                                    @endphp
                                    @if(count($caseSymptoms) > 0)
                                        <span class="badge bg-light text-dark" 
                                              data-bs-toggle="tooltip" 
                                              title="{{ implode(', ', $caseSymptoms) }}">
                                            {{ count($caseSymptoms) }} симптомов
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'draft' => 'secondary',
                                            'analyzing' => 'info',
                                            'report_ready' => 'success',
                                            'consultation_pending' => 'warning',
                                            'consultation_in_progress' => 'primary',
                                            'completed' => 'dark',
                                            'archived' => 'light'
                                        ];
                                        $statusLabels = [
                                            'draft' => 'Черновик',
                                            'analyzing' => 'Анализ',
                                            'report_ready' => 'Готов к консультации',
                                            'consultation_pending' => 'Ожидает консультации',
                                            'consultation_in_progress' => 'Консультация',
                                            'completed' => 'Завершен',
                                            'archived' => 'Архив'
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $statusColors[$caseItem->status] ?? 'secondary' }}">
                                        {{ $statusLabels[$caseItem->status] ?? $caseItem->status }}
                                    </span>
                                </td>
                                <td>{{ $caseItem->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('diagnostic.result', $caseItem->id) }}" 
                                           class="btn btn-outline-info" 
                                           title="Просмотр отчета">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        @if(in_array($caseItem->status, ['report_ready', 'consultation_pending']))
                                            <a href="{{ route('consultation.order.form', ['case' => $caseItem->id]) }}" 
                                               class="btn btn-outline-primary" 
                                               title="Заказать консультацию">
                                                <i class="bi bi-chat-dots"></i>
                                            </a>
                                        @endif
                                        
                                        @if($caseItem->status == 'consultation_in_progress')
                                            <a href="#" 
                                               class="btn btn-outline-success" 
                                               title="Перейти к консультации">
                                                <i class="bi bi-chat-left-text"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Пагинация -->
                <div class="mt-3">
                    {{ $diagnosticCases->links('vendor.pagination.simple-bootstrap-4') }}
                </div>
                
            @else
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-clipboard-x text-muted" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="text-muted">Диагностических случаев нет</h5>
                    <p class="text-muted mb-4">Создайте новый диагностический случай для получения помощи экспертов</p>
                    <a href="{{ route('diagnostic.start') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Создать диагностику
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Показать/скрыть выбор эксперта
        const typeRadios = document.querySelectorAll('input[name="type"]');
        const expertSection = document.getElementById('expertSection');
        
        function toggleExpertSection() {
            const selectedType = document.querySelector('input[name="type"]:checked');
            if (selectedType && (selectedType.value === 'premium' || selectedType.value === 'expert')) {
                expertSection.style.display = 'block';
            } else {
                expertSection.style.display = 'none';
            }
        }
        
        typeRadios.forEach(radio => {
            radio.addEventListener('change', toggleExpertSection);
        });
        
        // Инициализация
        toggleExpertSection();
        
        // Инициализация тултипов
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush
@endsection