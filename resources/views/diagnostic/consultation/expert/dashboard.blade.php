<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>AutoDoc AI - Панель эксперта</title>
    
    <!-- Bootstrap и иконки -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
            --sidebar-width: 280px;
            --header-height: 60px;
        }
        
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        /* Карточки */
        .stat-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .stat-card .card-body {
            padding: 1.5rem;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        /* Статусы консультаций */
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .status-pending {
            background-color: rgba(255, 193, 7, 0.15);
            color: #856404;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        
        .status-in_progress {
            background-color: rgba(23, 162, 184, 0.15);
            color: #0c5460;
            border: 1px solid rgba(23, 162, 184, 0.3);
        }
        
        .status-scheduled {
            background-color: rgba(108, 117, 125, 0.15);
            color: #495057;
            border: 1px solid rgba(108, 117, 125, 0.3);
        }
        
        .status-completed {
            background-color: rgba(40, 167, 69, 0.15);
            color: #155724;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        
        .status-cancelled {
            background-color: rgba(220, 53, 69, 0.15);
            color: #721c24;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        /* Типы консультаций */
        .type-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .type-basic {
            background-color: rgba(108, 117, 125, 0.1);
            color: #495057;
        }
        
        .type-premium {
            background-color: rgba(0, 123, 255, 0.1);
            color: #0056b3;
        }
        
        .type-expert {
            background-color: rgba(255, 193, 7, 0.15);
            color: #856404;
        }
        
        /* Таблица консультаций */
        .consultation-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .consultation-table .table {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .consultation-table .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            padding: 1rem;
            font-weight: 600;
            color: #495057;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .consultation-table .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }
        
        .consultation-table .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .consultation-table .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Карточка консультации для мобильных */
        .consultation-mobile-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary-color);
        }
        
        /* Фильтры */
        .filter-badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .filter-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .filter-badge.active {
            box-shadow: 0 2px 5px rgba(0,0,0,0.15);
        }
        
        /* Графики и метрики */
        .metric-value {
            font-size: 1.8rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.25rem;
        }
        
        .metric-label {
            font-size: 0.875rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .metric-change {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.15rem 0.5rem;
            border-radius: 10px;
        }
        
        .metric-change.positive {
            background-color: rgba(40, 167, 69, 0.15);
            color: #155724;
        }
        
        .metric-change.negative {
            background-color: rgba(220, 53, 69, 0.15);
            color: #721c24;
        }
        
        /* Аватар */
        .avatar-sm {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #495057;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .stat-card .card-body {
                padding: 1.25rem;
            }
            
            .metric-value {
                font-size: 1.5rem;
            }
            
            .consultation-table {
                display: none;
            }
            
            .mobile-consultations {
                display: block !important;
            }
            
            .filter-badges-container {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 0.5rem;
            }
            
            .filter-badges-container::-webkit-scrollbar {
                height: 4px;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-consultations {
                display: none !important;
            }
        }
        
        /* Анимации */
        .fade-in {
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Спиннер */
        .spinner-container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        /* Кнопки действий */
        .action-btn {
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        /* Прогресс бар */
        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
            overflow: hidden;
        }
        
        .progress-bar-custom .progress {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        /* Рейтинг */
        .rating-stars {
            color: #ffc107;
            font-size: 0.9rem;
        }
        
        .rating-value {
            font-weight: 600;
            color: #495057;
        }
    </style>
</head>
<body>
    @extends('layouts.app')

    @section('content')
    <div class="container-fluid py-3 fade-in">
        <!-- Заголовок -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
                    <div>
                        <h1 class="h2 mb-2">👨‍🔧 Панель эксперта</h1>
                        <p class="text-muted mb-0">
                            Добро пожаловать, {{ auth()->user()->name }}! 
                            @if(auth()->user()->expert_specialization)
                                <span class="badge bg-info ms-2">{{ auth()->user()->expert_specialization }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <div class="text-muted">
                                <i class="bi bi-clock-history me-1"></i>
                                {{ now()->format('d.m.Y H:i') }}
                            </div>
                            @if($pendingConsultationsCount ?? 0 > 0)
                                <span class="badge bg-danger">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    {{ $pendingConsultationsCount }} ожидают
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Блок общей аналитики -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="h4 mb-3">📊 Общая аналитика</h3>
            </div>
            
            <!-- Карточка 1: Всего консультаций -->
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-chat-dots"></i>
                            </div>
                            <div class="ms-auto">
                                <span class="metric-change {{ $stats['total'] > 0 ? 'positive' : '' }}">
                                    <i class="bi bi-arrow-up-right me-1"></i>
                                    {{ $stats['total'] > 0 ? 'Активен' : 'Нет данных' }}
                                </span>
                            </div>
                        </div>
                        <div class="metric-value text-primary">
                            {{ $stats['total'] }}
                        </div>
                        <div class="metric-label">
                            Всего консультаций
                        </div>
                        <div class="mt-3">
                            <div class="progress-bar-custom mb-2">
                                <div class="progress bg-primary" style="width: 100%"></div>
                            </div>
                            <small class="text-muted">За все время работы</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Карточка 2: Ожидающие консультации -->
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div class="ms-auto">
                                <span class="badge bg-warning">
                                    {{ $stats['pending'] }} шт.
                                </span>
                            </div>
                        </div>
                        <div class="metric-value text-warning">
                            {{ $stats['pending'] }}
                        </div>
                        <div class="metric-label">
                            Ожидающие
                        </div>
                        <div class="mt-3">
                            <div class="progress-bar-custom mb-2">
                                <div class="progress bg-warning" 
                                     style="width: {{ $stats['total'] > 0 ? ($stats['pending'] / $stats['total'] * 100) : 0 }}%">
                                </div>
                            </div>
                            <small class="text-muted">
                                {{ $stats['total'] > 0 ? round(($stats['pending'] / $stats['total'] * 100), 1) : 0 }}% от общего числа
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Карточка 3: В работе -->
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="stat-icon bg-info bg-opacity-10 text-info">
                                <i class="bi bi-gear"></i>
                            </div>
                            <div class="ms-auto">
                                <span class="badge bg-info">
                                    {{ $stats['in_progress'] }} шт.
                                </span>
                            </div>
                        </div>
                        <div class="metric-value text-info">
                            {{ $stats['in_progress'] }}
                        </div>
                        <div class="metric-label">
                            В работе
                        </div>
                        <div class="mt-3">
                            <div class="progress-bar-custom mb-2">
                                <div class="progress bg-info" 
                                     style="width: {{ $stats['total'] > 0 ? ($stats['in_progress'] / $stats['total'] * 100) : 0 }}%">
                                </div>
                            </div>
                            <small class="text-muted">
                                {{ $stats['total'] > 0 ? round(($stats['in_progress'] / $stats['total'] * 100), 1) : 0 }}% от общего числа
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Карточка 4: Средний рейтинг -->
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="stat-icon bg-success bg-opacity-10 text-success">
                                <i class="bi bi-star"></i>
                            </div>
                            <div class="ms-auto">
                                <span class="metric-change {{ $stats['avg_rating'] >= 4 ? 'positive' : ($stats['avg_rating'] >= 3 ? '' : 'negative') }}">
                                    {{ $stats['avg_rating'] >= 4 ? 'Высокий' : ($stats['avg_rating'] >= 3 ? 'Средний' : 'Низкий') }}
                                </span>
                            </div>
                        </div>
                        <div class="metric-value text-success">
                            {{ number_format($stats['avg_rating'], 1) }}
                        </div>
                        <div class="metric-label">
                            Средний рейтинг
                        </div>
                        <div class="mt-3">
                            <div class="rating-stars mb-1">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= floor($stats['avg_rating']))
                                        <i class="bi bi-star-fill"></i>
                                    @elseif($i - 0.5 <= $stats['avg_rating'])
                                        <i class="bi bi-star-half"></i>
                                    @else
                                        <i class="bi bi-star"></i>
                                    @endif
                                @endfor
                            </div>
                            <small class="text-muted">
                                На основе {{ $stats['completed'] }} завершенных консультаций
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Быстрая статистика -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="bi bi-graph-up me-2"></i>Быстрая статистика
                        </h5>
                        <div class="row">
                            <div class="col-md-3 col-6 mb-3 mb-md-0">
                                <div class="text-center">
                                    <div class="metric-value text-primary">{{ $stats['completed'] }}</div>
                                    <div class="metric-label">Завершено</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3 mb-md-0">
                                <div class="text-center">
                                    <div class="metric-value text-warning">{{ $stats['pending'] }}</div>
                                    <div class="metric-label">В ожидании</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="text-center">
                                    <div class="metric-value text-success">{{ number_format($stats['avg_rating'], 1) }}</div>
                                    <div class="metric-label">Рейтинг</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="text-center">
                                    <div class="metric-value text-info">
                                        {{ $stats['total'] > 0 ? round(($stats['completed'] / $stats['total'] * 100), 0) : 0 }}%
                                    </div>
                                    <div class="metric-label">Эффективность</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Фильтры консультаций -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h4 mb-0">
                        <i class="bi bi-list-task me-2"></i>Мои консультации
                    </h3>
                    <div class="text-muted">
                        Показано: {{ $consultations->count() }} из {{ $consultations->total() }}
                    </div>
                </div>
                
                <div class="filter-badges-container mb-4">
                    <a href="?status=all" 
                       class="filter-badge {{ $status == 'all' ? 'active bg-primary text-white' : 'bg-light text-dark' }}">
                        Все ({{ $stats['total'] }})
                    </a>
                    <a href="?status=pending" 
                       class="filter-badge {{ $status == 'pending' ? 'active bg-warning text-dark' : 'bg-light text-dark' }}">
                        ⏳ Ожидание ({{ $stats['pending'] }})
                    </a>
                    <a href="?status=in_progress" 
                       class="filter-badge {{ $status == 'in_progress' ? 'active bg-info text-white' : 'bg-light text-dark' }}">
                        🔄 В работе ({{ $stats['in_progress'] }})
                    </a>
                    <a href="?status=completed" 
                       class="filter-badge {{ $status == 'completed' ? 'active bg-success text-white' : 'bg-light text-dark' }}">
                        ✅ Завершено ({{ $stats['completed'] }})
                    </a>
                </div>
            </div>
        </div>

        <!-- Десктопная таблица консультаций -->
        @if($consultations->count() > 0)
            <div class="row mb-4 d-none d-md-block">
                <div class="col-12">
                    <div class="consultation-table">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th width="5%">ID</th>
                                        <th width="20%">Клиент / Автомобиль</th>
                                        <th width="15%">Тип / Статус</th>
                                        <th width="15%">Дата</th>
                                        <th width="15%">Стоимость</th>
                                        <th width="15%">Эксперт</th>
                                        <th width="15%">Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($consultations as $consultation)
                                        <tr>
                                            <td>
                                                <strong>#{{ $consultation->id }}</strong>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm me-3">
                                                        @if($consultation->user && $consultation->user->avatar)
                                                            <img src="{{ $consultation->user->avatar }}" 
                                                                 alt="{{ $consultation->user->name }}" 
                                                                 class="rounded-circle" 
                                                                 style="width: 32px; height: 32px; object-fit: cover;">
                                                        @else
                                                            {{ substr($consultation->user->name ?? 'К', 0, 1) }}
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">{{ $consultation->user->name ?? 'Клиент' }}</div>
                                                        <div class="text-muted small">
                                                            @if($consultation->case && $consultation->case->brand)
                                                                {{ $consultation->case->brand->name ?? '' }} 
                                                                {{ $consultation->case->model->name ?? '' }}
                                                            @else
                                                                Не указан
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="mb-1">
                                                    <span class="type-badge type-{{ $consultation->type }}">
                                                        @switch($consultation->type)
                                                            @case('basic') Базовая @break
                                                            @case('premium') Премиум @break
                                                            @case('expert') Экспертная @break
                                                        @endswitch
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="status-badge status-{{ $consultation->status }}">
                                                        @switch($consultation->status)
                                                            @case('pending') ⏳ Ожидание @break
                                                            @case('scheduled') 📅 Запланирована @break
                                                            @case('in_progress') 🔄 В работе @break
                                                            @case('completed') ✅ Завершена @break
                                                            @case('cancelled') ❌ Отменена @break
                                                        @endswitch
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $consultation->created_at->format('d.m.Y') }}</div>
                                                <div class="text-muted small">{{ $consultation->created_at->format('H:i') }}</div>
                                                @if($consultation->scheduled_at)
                                                    <div class="text-info small mt-1">
                                                        <i class="bi bi-clock me-1"></i>
                                                        {{ \Carbon\Carbon::parse($consultation->scheduled_at)->format('d.m H:i') }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ number_format($consultation->price, 0) }} ₽</div>
                                                <div class="text-muted small">
                                                    @switch($consultation->payment_status)
                                                        @case('paid') <span class="text-success">Оплачено</span> @break
                                                        @case('pending') <span class="text-warning">Ожидает оплаты</span> @break
                                                        @case('cancelled') <span class="text-danger">Отменено</span> @break
                                                    @endswitch
                                                </div>
                                            </td>
                                            <td>
                                                @if($consultation->expert_id == auth()->id())
                                                    <span class="badge bg-primary">Вы</span>
                                                @elseif($consultation->expert)
                                                    {{ $consultation->expert->name }}
                                                @else
                                                    <span class="text-muted small">Не назначен</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="{{ route('expert.consultation.show', $consultation->id) }}" 
                                                       class="btn btn-sm btn-outline-primary action-btn">
                                                        <i class="bi bi-eye me-1"></i>Просмотр
                                                    </a>
                                                    
                                                    @if($consultation->status == 'pending')
                                                        <form action="{{ route('expert.consultation.start', $consultation->id) }}" 
                                                              method="POST" 
                                                              class="d-inline">
                                                            @csrf
                                                            <button type="submit" 
                                                                    class="btn btn-sm btn-success action-btn"
                                                                    onclick="return confirm('Начать консультацию?')">
                                                                <i class="bi bi-play-fill me-1"></i>Начать
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Мобильный список консультаций -->
            <div class="row mb-4 mobile-consultations d-block d-md-none">
                <div class="col-12">
                    @foreach($consultations as $consultation)
                        <div class="consultation-mobile-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="fw-bold mb-1">Консультация #{{ $consultation->id }}</div>
                                    <div class="text-muted small">
                                        {{ $consultation->created_at->format('d.m.Y H:i') }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-primary">{{ number_format($consultation->price, 0) }} ₽</div>
                                    <div>
                                        <span class="status-badge status-{{ $consultation->status }}">
                                            @switch($consultation->status)
                                                @case('pending') ⏳ @break
                                                @case('in_progress') 🔄 @break
                                                @case('completed') ✅ @break
                                            @endswitch
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="small text-muted mb-1">Клиент</div>
                                <div class="fw-bold">{{ $consultation->user->name ?? 'Клиент' }}</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="small text-muted mb-1">Автомобиль</div>
                                <div>
                                    @if($consultation->case && $consultation->case->brand)
                                        {{ $consultation->case->brand->name ?? '' }} 
                                        {{ $consultation->case->model->name ?? '' }}
                                    @else
                                        <span class="text-muted">Не указан</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="type-badge type-{{ $consultation->type }}">
                                    @switch($consultation->type)
                                        @case('basic') Базовая @break
                                        @case('premium') Премиум @break
                                        @case('expert') Экспертная @break
                                    @endswitch
                                </span>
                                
                                <div class="d-flex gap-2">
                                    <a href="{{ route('expert.consultation.show', $consultation->id) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    @if($consultation->status == 'pending')
                                        <form action="{{ route('expert.consultation.start', $consultation->id) }}" 
                                              method="POST" 
                                              class="d-inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="btn btn-sm btn-success"
                                                    onclick="return confirm('Начать консультацию?')">
                                                <i class="bi bi-play-fill"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Пагинация -->
            @if($consultations->hasPages())
                <div class="row">
                    <div class="col-12">
                        <nav aria-label="Навигация по страницам">
                            <ul class="pagination justify-content-center">
                                {{-- Previous Page Link --}}
                                @if($consultations->onFirstPage())
                                    <li class="page-item disabled">
                                        <span class="page-link">«</span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $consultations->previousPageUrl() }}" rel="prev">«</a>
                                    </li>
                                @endif

                                {{-- Pagination Elements --}}
                                @for($page = 1; $page <= $consultations->lastPage(); $page++)
                                    @if($page == $consultations->currentPage())
                                        <li class="page-item active">
                                            <span class="page-link">{{ $page }}</span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $consultations->url($page) }}">{{ $page }}</a>
                                        </li>
                                    @endif
                                @endfor

                                {{-- Next Page Link --}}
                                @if($consultations->hasMorePages())
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $consultations->nextPageUrl() }}" rel="next">»</a>
                                    </li>
                                @else
                                    <li class="page-item disabled">
                                        <span class="page-link">»</span>
                                    </li>
                                @endif
                            </ul>
                        </nav>
                    </div>
                </div>
            @endif
        @else
            <!-- Пустой список -->
            <div class="row">
                <div class="col-12">
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-chat-dots text-muted" style="font-size: 4rem;"></i>
                        </div>
                        <h4 class="text-muted mb-3">Консультаций пока нет</h4>
                        <p class="text-muted mb-4">
                            @if($status == 'all')
                                У вас еще нет назначенных консультаций.
                            @else
                                Нет консультаций со статусом 
                                @switch($status)
                                    @case('pending') "Ожидание" @break
                                    @case('in_progress') "В работе" @break
                                    @case('completed') "Завершено" @break
                                @endswitch
                            @endif
                        </p>
                        <div class="d-flex justify-content-center gap-3">
                            @if($status != 'all')
                                <a href="?status=all" class="btn btn-primary">
                                    <i class="bi bi-arrow-left me-2"></i>Все консультации
                                </a>
                            @endif
                            <a href="{{ route('expert.profile.edit') }}" class="btn btn-outline-primary">
                                <i class="bi bi-person-circle me-2"></i>Мой профиль
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Быстрые действия -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="bi bi-lightning me-2"></i>Быстрые действия
                        </h5>
                        <div class="row">
                            <div class="col-md-3 col-6 mb-3">
                                <a href="{{ route('expert.profile.edit') }}" 
                                   class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center">
                                    <i class="bi bi-person-circle mb-2" style="font-size: 1.5rem;"></i>
                                    <span>Профиль</span>
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="{{ route('expert.schedule.index') }}" 
                                   class="btn btn-outline-success w-100 py-3 d-flex flex-column align-items-center">
                                    <i class="bi bi-calendar mb-2" style="font-size: 1.5rem;"></i>
                                    <span>График</span>
                                </a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="{{ route('expert.analytics.index') }}" 
                                   class="btn btn-outline-info w-100 py-3 d-flex flex-column align-items-center">
                                    <i class="bi bi-graph-up mb-2" style="font-size: 1.5rem;"></i>
                                    <span>Аналитика</span>
                                </a>
                            </div>
                            <div class="col-md-3 col-6">
                                <button type="button" 
                                        class="btn btn-outline-warning w-100 py-3 d-flex flex-column align-items-center"
                                        onclick="location.reload()">
                                    <i class="bi bi-arrow-clockwise mb-2" style="font-size: 1.5rem;"></i>
                                    <span>Обновить</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Автоматическое обновление статусов
            function updateConsultationStats() {
                fetch('/api/expert/pending-consultations')
                    .then(response => response.json())
                    .then(data => {
                        // Обновляем бейдж ожидающих консультаций
                        const pendingBadge = document.querySelector('.badge.bg-danger');
                        if (pendingBadge) {
                            pendingBadge.innerHTML = `<i class="bi bi-exclamation-triangle me-1"></i>${data.count} ожидают`;
                        }
                        
                        // Обновляем цифры в фильтрах
                        document.querySelectorAll('.filter-badge').forEach(badge => {
                            const href = badge.getAttribute('href');
                            if (href.includes('pending')) {
                                badge.textContent = `⏳ Ожидание (${data.count})`;
                            }
                        });
                    })
                    .catch(err => console.error('Ошибка обновления:', err));
            }
            
            // Обновляем каждые 30 секунд
            setInterval(updateConsultationStats, 30000);
            
            // Анимация карточек при наведении
            const cards = document.querySelectorAll('.stat-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-2px)';
                    card.style.boxShadow = '0 4px 12px rgba(0,0,0,0.12)';
                });
                
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(0)';
                    card.style.boxShadow = '0 2px 8px rgba(0,0,0,0.08)';
                });
            });
            
            // Подтверждение действий
            document.querySelectorAll('form').forEach(form => {
                if (form.hasAttribute('data-confirm')) {
                    form.addEventListener('submit', function(e) {
                        if (!confirm(this.getAttribute('data-confirm'))) {
                            e.preventDefault();
                        }
                    });
                }
            });
            
            // Автоматическое скрытие алертов
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
            
            // Плавная прокрутка для фильтров на мобильных
            const filterContainer = document.querySelector('.filter-badges-container');
            if (filterContainer) {
                filterContainer.addEventListener('wheel', function(e) {
                    if (window.innerWidth < 768) {
                        e.preventDefault();
                        this.scrollLeft += e.deltaY;
                    }
                });
            }
        });
    </script>
    @endsection
</body>
</html>