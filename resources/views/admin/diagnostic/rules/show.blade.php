@extends('layouts.app')

@section('title', $title)

@push('styles')
<style>
    .rule-card {
        border-left: 5px solid #2196F3;
        transition: all 0.3s ease;
    }
    
    .rule-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.1);
    }
    
    .badge-complexity {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-weight: 600;
    }
    
    .complexity-low { background: #4CAF50; color: white; }
    .complexity-medium { background: #FF9800; color: white; }
    .complexity-high { background: #F44336; color: white; }
    
    .list-steps {
        counter-reset: step-counter;
        list-style: none;
        padding-left: 0;
    }
    
    .list-steps li {
        position: relative;
        padding: 0.75rem 1rem 0.75rem 3rem;
        margin-bottom: 0.5rem;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 3px solid #2196F3;
    }
    
    .list-steps li:before {
        counter-increment: step-counter;
        content: counter(step-counter);
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        width: 24px;
        height: 24px;
        background: #2196F3;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.875rem;
    }
    
    .cause-badge {
        display: inline-block;
        padding: 0.375rem 0.75rem;
        margin: 0.25rem;
        background: #E3F2FD;
        color: #1565C0;
        border-radius: 20px;
        font-size: 0.875rem;
        transition: all 0.2s;
    }
    
    .cause-badge:hover {
        background: #BBDEFB;
        transform: scale(1.05);
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">{{ $title }}</h4>
                            <nav aria-label="breadcrumb" class="mt-2">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Главная</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('admin.diagnostic.rules.index') }}">Правила диагностики</a></li>
                                    <li class="breadcrumb-item active">Просмотр</li>
                                </ol>
                            </nav>
                        </div>
                        <div>
                            <a href="{{ route('admin.diagnostic.rules.edit', $rule->id) }}" 
                               class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil me-1"></i> Редактировать
                            </a>
                            <a href="{{ route('admin.diagnostic.rules.index') }}" 
                               class="btn btn-secondary btn-sm ms-2">
                                <i class="bi bi-arrow-left me-1"></i> Назад
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Основная информация -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <h5 class="text-primary">{{ $rule->symptom->name ?? 'Симптом не указан' }}</h5>
                                @if($rule->symptom->description)
                                    <p class="text-muted">{{ $rule->symptom->description }}</p>
                                @endif
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted mb-1">Марка:</label>
                                    <div class="d-flex align-items-center">
                                        @if($rule->brand)
                                            <span class="badge bg-info">
                                                <i class="bi bi-car-front me-1"></i>
                                                {{ $rule->brand->name }}
                                            </span>
                                        @else
                                            <span class="text-muted">Все марки</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted mb-1">Модель:</label>
                                    <div>
                                        @if($rule->model)
                                            <span class="badge bg-secondary">
                                                {{ $rule->model->name }}
                                            </span>
                                        @else
                                            <span class="text-muted">Все модели</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6 mb-3">
                                            <div class="text-muted small mb-1">Сложность</div>
                                            @php
                                                $complexityClass = 'complexity-medium';
                                                if ($rule->complexity_level <= 3) $complexityClass = 'complexity-low';
                                                elseif ($rule->complexity_level >= 7) $complexityClass = 'complexity-high';
                                            @endphp
                                            <div class="badge-complexity {{ $complexityClass }}">
                                                {{ $rule->complexity_level }}/10
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="text-muted small mb-1">Время</div>
                                            <div class="h5 mb-0">{{ $rule->estimated_time }} мин.</div>
                                        </div>
                                        <div class="col-12">
                                            <div class="text-muted small mb-1">Цена консультации</div>
                                            <div class="h4 text-success">{{ number_format($rule->base_consultation_price, 0, '', ' ') }} ₽</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Диагностические шаги -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="bi bi-clipboard-check text-primary me-2"></i>
                                Шаги диагностики
                            </h6>
                        </div>
                        <div class="card-body">
                            @if(!empty($rule->diagnostic_steps) && count($rule->diagnostic_steps) > 0)
                                <ol class="list-steps">
                                    @foreach($rule->diagnostic_steps as $step)
                                        <li>{{ $step }}</li>
                                    @endforeach
                                </ol>
                            @else
                                <div class="alert alert-warning mb-0">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Шаги диагностики не указаны
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Возможные причины -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                                        Возможные причины
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if(!empty($rule->possible_causes) && count($rule->possible_causes) > 0)
                                        <div class="d-flex flex-wrap">
                                            @foreach($rule->possible_causes as $cause)
                                                <span class="cause-badge">{{ $cause }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="alert alert-warning mb-0">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            Возможные причины не указаны
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Требуемые данные -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="bi bi-clipboard-data text-success me-2"></i>
                                        Требуемые данные
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if(!empty($rule->required_data) && count($rule->required_data) > 0)
                                        <ul class="list-group list-group-flush">
                                            @foreach($rule->required_data as $data)
                                                <li class="list-group-item d-flex align-items-center">
                                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                    {{ $data }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <div class="alert alert-warning mb-0">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            Требуемые данные не указаны
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Статистика и метаданные -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="bi bi-info-circle text-info me-2"></i>
                                Дополнительная информация
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="text-muted small mb-1">Статус</div>
                                    <span class="badge {{ $rule->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $rule->is_active ? 'Активно' : 'Неактивно' }}
                                    </span>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="text-muted small mb-1">Порядок</div>
                                    <div>{{ $rule->order }}</div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="text-muted small mb-1">Создано</div>
                                    <div>{{ $rule->created_at->format('d.m.Y H:i') }}</div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="text-muted small mb-1">Обновлено</div>
                                    <div>{{ $rule->updated_at->format('d.m.Y H:i') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Кнопки действий -->
                    <div class="d-flex justify-content-between mt-4">
                        <div>
                            <a href="{{ route('admin.diagnostic.rules.index') }}" 
                               class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i> К списку правил
                            </a>
                        </div>
                        <div>
                            @if($rule->is_active)
    <a href="{{ route('consultation.order.from-rule', $rule->id) }}" 
       class="btn btn-success">
        <i class="bi bi-chat-dots me-1"></i> Заказать консультацию
    </a>
@endif
                            
                            <a href="{{ route('admin.diagnostic.rules.edit', $rule->id) }}" 
                               class="btn btn-warning ms-2">
                                <i class="bi bi-pencil me-1"></i> Редактировать
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection