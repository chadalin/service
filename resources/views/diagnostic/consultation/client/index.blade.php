@extends('layouts.app')

@section('title', 'Мои диагностические случаи')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-3 mb-4">
            <!-- Фильтр по статусам -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Фильтр по статусу</h6>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="?status=all" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $status === 'all' ? 'active' : '' }}">
                            Все случаи
                            <span class="badge bg-{{ $status === 'all' ? 'light text-dark' : 'primary' }} rounded-pill">
                                {{ $statusStats['all'] }}
                            </span>
                        </a>
                        <a href="?status=draft" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $status === 'draft' ? 'active' : '' }}">
                            Черновики
                            <span class="badge bg-{{ $status === 'draft' ? 'light text-dark' : 'secondary' }} rounded-pill">
                                {{ $statusStats['draft'] }}
                            </span>
                        </a>
                        <a href="?status=analyzing" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $status === 'analyzing' ? 'active' : '' }}">
                            В анализе
                            <span class="badge bg-{{ $status === 'analyzing' ? 'light text-dark' : 'info' }} rounded-pill">
                                {{ $statusStats['analyzing'] }}
                            </span>
                        </a>
                        <a href="?status=report_ready" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $status === 'report_ready' ? 'active' : '' }}">
                            Готовы к консультации
                            <span class="badge bg-{{ $status === 'report_ready' ? 'light text-dark' : 'success' }} rounded-pill">
                                {{ $statusStats['report_ready'] }}
                            </span>
                        </a>
                        <a href="?status=consultation_pending" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $status === 'consultation_pending' ? 'active' : '' }}">
                            Ожидают консультации
                            <span class="badge bg-{{ $status === 'consultation_pending' ? 'light text-dark' : 'warning' }} rounded-pill">
                                {{ $statusStats['consultation_pending'] }}
                            </span>
                        </a>
                        <a href="?status=consultation_in_progress" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $status === 'consultation_in_progress' ? 'active' : '' }}">
                            Консультация
                            <span class="badge bg-{{ $status === 'consultation_in_progress' ? 'light text-dark' : 'primary' }} rounded-pill">
                                {{ $statusStats['consultation_in_progress'] }}
                            </span>
                        </a>
                        <a href="?status=completed" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $status === 'completed' ? 'active' : '' }}">
                            Завершенные
                            <span class="badge bg-{{ $status === 'completed' ? 'light text-dark' : 'dark' }} rounded-pill">
                                {{ $statusStats['completed'] }}
                            </span>
                        </a>
                        <a href="?status=archived" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $status === 'archived' ? 'active' : '' }}">
                            Архив
                            <span class="badge bg-{{ $status === 'archived' ? 'light text-dark' : 'light text-dark' }} rounded-pill">
                                {{ $statusStats['archived'] }}
                            </span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Быстрые действия -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Быстрые действия</h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('diagnostic.start') }}" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-plus-circle me-2"></i>Новая диагностика
                    </a>
                    @if($statusStats['report_ready'] > 0)
                        <a href="?status=report_ready" class="btn btn-success w-100 mb-2">
                            <i class="bi bi-chat-dots me-2"></i>Заказать консультацию ({{ $statusStats['report_ready'] }})
                        </a>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        @switch($status)
                            @case('all') Все диагностические случаи @break
                            @case('draft') Черновики @break
                            @case('analyzing') В анализе @break
                            @case('report_ready') Готовы к консультации @break
                            @case('consultation_pending') Ожидают консультации @break
                            @case('consultation_in_progress') Консультации в процессе @break
                            @case('completed') Завершенные @break
                            @case('archived') Архив @break
                            @default Все диагностические случаи
                        @endswitch
                        <span class="text-muted ms-2">({{ $consultations->total() }})</span>
                    </h5>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-sort-down"></i> Сортировка
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?status={{ $status }}&sort=newest">Сначала новые</a></li>
                            <li><a class="dropdown-item" href="?status={{ $status }}&sort=oldest">Сначала старые</a></li>
                            <li><a class="dropdown-item" href="?status={{ $status }}&sort=brand">По марке авто</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="card-body">
                    @if($consultations->count() > 0)
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
                                        <th>Консультация</th>
                                        <th>Создан</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($consultations as $case)
                                    @php
                                        // Получаем активную консультацию для этого кейса
                                        // Нужно загрузить консультации через with в контроллере
                                        // Добавьте это в ваш метод контроллера:
                                        // ->with(['brand', 'model', 'consultations' => function($q) {
                                        //     $q->with('expert')->latest();
                                        // }])
                                        $activeConsultation = $case->consultations->first();
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>#{{ substr($case->id, 0, 8) }}</strong>
                                        </td>
                                        <td>
                                            @if($case->brand && $case->model)
                                                <strong>{{ $case->brand->name }}</strong><br>
                                                <small>{{ $case->model->name }}</small>
                                            @else
                                                <em class="text-muted">Не указан</em>
                                            @endif
                                        </td>
                                        <td>{{ $case->year ?? '—' }}</td>
                                        <td>
                                            @if($case->mileage)
                                                <span class="badge bg-light text-dark">
                                                    {{ number_format($case->mileage) }} км
                                                </span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $caseSymptoms = getCaseSymptoms($case->symptoms ?? []);
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
                                            <span class="badge bg-{{ $statusColors[$case->status] ?? 'secondary' }}">
                                                {{ $statusLabels[$case->status] ?? $case->status }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($activeConsultation)
                                                @php
                                                    $consultationStatusColors = [
                                                        'pending' => 'warning',
                                                        'scheduled' => 'info',
                                                        'in_progress' => 'success',
                                                        'completed' => 'secondary',
                                                        'cancelled' => 'danger'
                                                    ];
                                                    $consultationStatusLabels = [
                                                        'pending' => 'Ожидает оплаты',
                                                        'scheduled' => 'Запланирована',
                                                        'in_progress' => 'В процессе',
                                                        'completed' => 'Завершена',
                                                        'cancelled' => 'Отменена'
                                                    ];
                                                @endphp
                                                <div class="d-flex flex-column">
                                                    <a href="{{ route('diagnostic.consultation.show', $activeConsultation->id) }}" 
                                                       class="btn btn-sm btn-outline-primary mb-1">
                                                        <i class="bi bi-chat-left-text me-1"></i> Чат
                                                    </a>
                                                    <small class="text-muted">
                                                        <span class="badge bg-{{ $consultationStatusColors[$activeConsultation->status] ?? 'secondary' }}">
                                                            {{ $consultationStatusLabels[$activeConsultation->status] ?? $activeConsultation->status }}
                                                        </span>
                                                        @if($activeConsultation->expert)
                                                            <br><small>Эксперт: {{ $activeConsultation->expert->name }}</small>
                                                        @endif
                                                    </small>
                                                </div>
                                            @elseif($case->status === 'report_ready')
                                                <a href="{{ route('consultation.order.form', ['case' => $case->id]) }}" 
                                                   class="btn btn-sm btn-outline-warning">
                                                    <i class="bi bi-chat-dots me-1"></i> Заказать
                                                </a>
                                            @elseif($case->consultations && $case->consultations->isNotEmpty())
                                                @php
                                                    $lastConsultation = $case->consultations->first();
                                                @endphp
                                                <a href="{{ route('diagnostic.consultation.show', $lastConsultation->id) }}" 
                                                   class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-clock-history me-1"></i> История
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $case->created_at->format('d.m.Y') }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('diagnostic.result', $case->id) }}" 
                                                   class="btn btn-outline-info" 
                                                   title="Просмотр отчета"
                                                   data-bs-toggle="tooltip">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                
                                                @if(in_array($case->status, ['report_ready', 'consultation_pending']))
                                                    <a href="{{ route('consultation.order.form', ['case' => $case->id]) }}" 
                                                       class="btn btn-outline-primary" 
                                                       title="Заказать консультацию"
                                                       data-bs-toggle="tooltip">
                                                        <i class="bi bi-chat-dots"></i>
                                                    </a>
                                                @endif
                                                
                                                @if($case->status === 'draft')
                                                    <a href="#" 
                                                       class="btn btn-outline-warning" 
                                                       title="Продолжить заполнение"
                                                       data-bs-toggle="tooltip">
                                                        <i class="bi bi-pencil"></i>
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
                            {{ $consultations->links('vendor.pagination.simple-bootstrap-4') }}
                        </div>
                        
                    @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                @switch($status)
                                    @case('draft')
                                        <i class="bi bi-clipboard text-muted" style="font-size: 3rem;"></i>
                                        <h5 class="text-muted mt-3">Черновиков нет</h5>
                                        <p class="text-muted mb-4">Создайте новый диагностический случай</p>
                                        @break
                                    @case('report_ready')
                                        <i class="bi bi-check-circle text-muted" style="font-size: 3rem;"></i>
                                        <h5 class="text-muted mt-3">Нет готовых к консультации случаев</h5>
                                        <p class="text-muted mb-4">Дождитесь завершения анализа текущих случаев</p>
                                        @break
                                    @case('consultation_in_progress')
                                        <i class="bi bi-chat-dots text-muted" style="font-size: 3rem;"></i>
                                        <h5 class="text-muted mt-3">Активных консультаций нет</h5>
                                        <p class="text-muted mb-4">Закажите консультацию для готовых случаев</p>
                                        @break
                                    @default
                                        <i class="bi bi-clipboard-x text-muted" style="font-size: 3rem;"></i>
                                        <h5 class="text-muted mt-3">Диагностических случаев нет</h5>
                                        <p class="text-muted mb-4">Создайте новый диагностический случай</p>
                                @endswitch
                            </div>
                            <a href="{{ route('diagnostic.start') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Создать диагностику
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .list-group-item.active {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация тултипов
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endpush