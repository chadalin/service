@extends('layouts.app')

@section('title', 'Мои диагностические случаи')

@section('content')
<div class="container-fluid py-4">
    <!-- Шапка с заголовком и быстрыми действиями -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0">
                        <i class="bi bi-clipboard-data me-2 text-primary"></i>
                        Мои диагностические случаи
                        <span class="text-muted fs-6">({{ $cases->total() }})</span>
                    </h4>
                    <p class="text-muted mb-0">Управляйте вашими автомобильными диагностиками</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group" role="group">
                        <a href="{{ route('diagnostic.start') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Новая диагностика
                        </a>
                        @if($statusStats['report_ready'] > 0)
                            <a href="{{ route('consultation.order.form', ['case' => 'new']) }}" class="btn btn-success ms-2">
                                <i class="bi bi-chat-dots me-2"></i>Заказать консультацию
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтры по статусам (горизонтальные табы) -->
    <div class="card mb-4">
        <div class="card-body p-2">
            <div class="d-flex flex-wrap gap-2">
                <a href="?status=all&sort={{ request('sort', 'newest') }}" 
                   class="btn btn-status {{ $status === 'all' ? 'active' : '' }}">
                    <span class="status-label">Все случаи</span>
                    <span class="badge bg-{{ $status === 'all' ? 'light text-dark' : 'primary' }} ms-2">
                        {{ $statusStats['all'] }}
                    </span>
                </a>
                
                <a href="?status=draft&sort={{ request('sort', 'newest') }}" 
                   class="btn btn-status {{ $status === 'draft' ? 'active' : '' }}">
                    <i class="bi bi-pencil me-1"></i>
                    <span class="status-label">Черновики</span>
                    <span class="badge bg-{{ $status === 'draft' ? 'light text-dark' : 'secondary' }} ms-2">
                        {{ $statusStats['draft'] }}
                    </span>
                </a>
                
                <a href="?status=analyzing&sort={{ request('sort', 'newest') }}" 
                   class="btn btn-status {{ $status === 'analyzing' ? 'active' : '' }}">
                    <i class="bi bi-hourglass-split me-1"></i>
                    <span class="status-label">В анализе</span>
                    <span class="badge bg-{{ $status === 'analyzing' ? 'light text-dark' : 'info' }} ms-2">
                        {{ $statusStats['analyzing'] }}
                    </span>
                </a>
                
                <a href="?status=report_ready&sort={{ request('sort', 'newest') }}" 
                   class="btn btn-status {{ $status === 'report_ready' ? 'active' : '' }}">
                    <i class="bi bi-check-circle me-1"></i>
                    <span class="status-label">Готовы</span>
                    <span class="badge bg-{{ $status === 'report_ready' ? 'light text-dark' : 'success' }} ms-2">
                        {{ $statusStats['report_ready'] }}
                    </span>
                </a>
                
                <a href="?status=consultation_pending&sort={{ request('sort', 'newest') }}" 
                   class="btn btn-status {{ $status === 'consultation_pending' ? 'active' : '' }}">
                    <i class="bi bi-clock me-1"></i>
                    <span class="status-label">Ожидают</span>
                    <span class="badge bg-{{ $status === 'consultation_pending' ? 'light text-dark' : 'warning' }} ms-2">
                        {{ $statusStats['consultation_pending'] }}
                    </span>
                </a>
                
                <a href="?status=consultation_in_progress&sort={{ request('sort', 'newest') }}" 
                   class="btn btn-status {{ $status === 'consultation_in_progress' ? 'active' : '' }}">
                    <i class="bi bi-chat-dots me-1"></i>
                    <span class="status-label">Консультация</span>
                    <span class="badge bg-{{ $status === 'consultation_in_progress' ? 'light text-dark' : 'primary' }} ms-2">
                        {{ $statusStats['consultation_in_progress'] }}
                    </span>
                </a>
                
                <a href="?status=completed&sort={{ request('sort', 'newest') }}" 
                   class="btn btn-status {{ $status === 'completed' ? 'active' : '' }}">
                    <i class="bi bi-check-all me-1"></i>
                    <span class="status-label">Завершенные</span>
                    <span class="badge bg-{{ $status === 'completed' ? 'light text-dark' : 'dark' }} ms-2">
                        {{ $statusStats['completed'] }}
                    </span>
                </a>
                
                <a href="?status=archived&sort={{ request('sort', 'newest') }}" 
                   class="btn btn-status {{ $status === 'archived' ? 'active' : '' }}">
                    <i class="bi bi-archive me-1"></i>
                    <span class="status-label">Архив</span>
                    <span class="badge bg-{{ $status === 'archived' ? 'light text-dark' : 'light text-dark' }} ms-2">
                        {{ $statusStats['archived'] }}
                    </span>
                </a>
            </div>
        </div>
    </div>

    <!-- Основной контент -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">
                    @switch($status)
                        @case('all') Все диагностические случаи @break
                        @case('draft') Черновики диагностических случаев @break
                        @case('analyzing') Диагностика в процессе анализа @break
                        @case('report_ready') Готовы к консультации @break
                        @case('consultation_pending') Ожидают начала консультации @break
                        @case('consultation_in_progress') Консультации в процессе @break
                        @case('completed') Завершенные диагностики @break
                        @case('archived') Архив диагностических случаев @break
                        @default Все диагностические случаи
                    @endswitch
                </h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-sort-down me-1"></i> Сортировка
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item {{ request('sort') === 'newest' ? 'active' : '' }}" 
                               href="?status={{ $status }}&sort=newest">
                                <i class="bi bi-sort-down-alt me-2"></i> Сначала новые
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request('sort') === 'oldest' ? 'active' : '' }}" 
                               href="?status={{ $status }}&sort=oldest">
                                <i class="bi bi-sort-up-alt me-2"></i> Сначала старые
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request('sort') === 'brand' ? 'active' : '' }}" 
                               href="?status={{ $status }}&sort=brand">
                                <i class="bi bi-car-front me-2"></i> По марке авто
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="card-body">
            @if($cases->count() > 0)
                <!-- Карточки для мобильных, таблица для десктопа -->
                <div class="d-none d-lg-block">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 10%">ID</th>
                                    <th style="width: 15%">Автомобиль</th>
                                    <th style="width: 8%" class="text-center">Год</th>
                                    <th style="width: 10%" class="text-center">Пробег</th>
                                    <th style="width: 12%" class="text-center">Статус</th>
                                    <th style="width: 15%" class="text-center">Чат</th>
                                    <th style="width: 10%" class="text-center">Отчет</th>
                                    <th style="width: 10%" class="text-center">Создан</th>
                                    <th style="width: 10%" class="text-center">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cases as $case)
                                @php
                                    // Проверяем наличие чата
                                    $hasMessages = $case->consultationMessages && $case->consultationMessages->count() > 0;
                                    $lastMessage = $hasMessages ? $case->consultationMessages->first() : null;
                                    $hasReport = $case->analysis_result || in_array($case->status, ['report_ready', 'completed', 'consultation_in_progress']);
                                    $caseSymptoms = getCaseSymptoms($case->symptoms ?? []);
                                    
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
                                        'report_ready' => 'Готов',
                                        'consultation_pending' => 'Ожидает',
                                        'consultation_in_progress' => 'Консультация',
                                        'completed' => 'Завершен',
                                        'archived' => 'Архив'
                                    ];
                                    
                                    // Определяем иконку для статуса
                                    $statusIcons = [
                                        'draft' => 'pencil',
                                        'analyzing' => 'hourglass-split',
                                        'report_ready' => 'check-circle',
                                        'consultation_pending' => 'clock',
                                        'consultation_in_progress' => 'chat-dots',
                                        'completed' => 'check-all',
                                        'archived' => 'archive'
                                    ];
                                @endphp
                                <tr class="table-row-hover">
                                    <td>
                                        <div class="case-id">
                                            <strong class="text-primary">#{{ substr($case->id, 0, 8) }}</strong>
                                            @if($hasMessages)
                                                <div class="message-indicator" title="Есть сообщения в чате">
                                                    <i class="bi bi-chat-dots-fill text-success"></i>
                                                    <small class="text-muted">{{ $case->consultationMessages->count() }}</small>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="car-info">
                                            @if($case->brand)
                                                <div class="car-brand">
                                                    <i class="bi bi-car-front me-1 text-muted"></i>
                                                    <strong>{{ $case->brand->name }}</strong>
                                                </div>
                                                @if($case->model)
                                                    <div class="car-model text-muted small">
                                                        {{ $case->model->name }}
                                                    </div>
                                                @endif
                                            @else
                                                <span class="text-muted">Не указан</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($case->year)
                                            <span class="badge bg-light text-dark year-badge">
                                                {{ $case->year }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($case->mileage)
                                            <div class="mileage-info">
                                                <i class="bi bi-speedometer2 text-muted me-1"></i>
                                                <span class="badge bg-light text-dark">
                                                    {{ number_format($case->mileage, 0, ',', ' ') }} км
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $statusColors[$case->status] ?? 'secondary' }} status-badge">
                                            <i class="bi bi-{{ $statusIcons[$case->status] ?? 'circle' }} me-1"></i>
                                            {{ $statusLabels[$case->status] ?? $case->status }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <!-- БЛОК ЧАТА -->
                                        @if(in_array($case->status, ['consultation_in_progress', 'consultation_pending']))
                                            <div class="chat-action">
                                                <a href="{{ route('diagnostic.consultation.show', $case->id) }}" 
                                                   class="btn btn-primary btn-sm chat-btn">
                                                    <i class="bi bi-chat-left-text me-1"></i> Чат
                                                </a>
                                                @if($hasMessages && $lastMessage)
                                                    <div class="last-message-time small text-muted mt-1">
                                                        {{ $lastMessage->created_at->format('d.m H:i') }}
                                                    </div>
                                                @else
                                                    <div class="no-messages small text-muted mt-1">
                                                        Нет сообщений
                                                    </div>
                                                @endif
                                            </div>
                                        @elseif($case->status === 'report_ready')
                                            <a href="{{ route('consultation.order.form', ['case' => $case->id]) }}" 
                                               class="btn btn-success btn-sm order-btn">
                                                <i class="bi bi-chat-dots me-1"></i> Заказать
                                            </a>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($case->status === 'report_ready' || $case->status === 'completed' || 
                                            $case->status === 'consultation_in_progress' || $case->status === 'consultation_pending')
                                            <a href="{{ route('diagnostic.result', $case->id) }}" 
                                               class="btn btn-info btn-sm report-btn"
                                               target="_blank"
                                               title="Просмотреть отчет">
                                                <i class="bi bi-file-earmark-text me-1"></i> Отчет
                                            </a>
                                        @elseif($case->status === 'analyzing')
                                            <span class="badge bg-warning analyzing-badge" title="Отчет в процессе создания">
                                                <i class="bi bi-hourglass-split me-1"></i> В работе
                                            </span>
                                        @elseif($case->status === 'draft')
                                            <span class="badge bg-secondary draft-badge" title="Заполните данные для получения отчета">
                                                <i class="bi bi-pencil me-1"></i> Черновик
                                            </span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="date-info">
                                            <div class="created-date">{{ $case->created_at->format('d.m.Y') }}</div>
                                            <div class="created-time text-muted">{{ $case->created_at->format('H:i') }}</div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            <!-- Основные действия -->
                                            <div class="btn-group" role="group">
                                                @if($hasReport)
                                                    <a href="{{ route('diagnostic.result', $case->id) }}" 
                                                       class="btn btn-outline-info btn-sm" 
                                                       title="Просмотр отчета"
                                                       data-bs-toggle="tooltip">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                @endif
                                                
                                                @if(in_array($case->status, ['consultation_in_progress', 'consultation_pending']))
                                                    <a href="{{ route('diagnostic.consultation.show', $case->id) }}" 
                                                       class="btn btn-outline-primary btn-sm" 
                                                       title="Перейти в чат"
                                                       data-bs-toggle="tooltip">
                                                        <i class="bi bi-chat-left-text"></i>
                                                    </a>
                                                @endif
                                                
                                                @if($case->status === 'report_ready')
                                                    <a href="{{ route('consultation.order.form', ['case' => $case->id]) }}" 
                                                       class="btn btn-outline-success btn-sm" 
                                                       title="Заказать консультацию"
                                                       data-bs-toggle="tooltip">
                                                        <i class="bi bi-chat-dots"></i>
                                                    </a>
                                                @endif
                                            </div>
                                            
                                            <!-- Дополнительные действия в выпадающем меню -->
                                            <div class="dropdown mt-1">
                                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                                        type="button" 
                                                        data-bs-toggle="dropdown"
                                                        aria-expanded="false">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if(count($caseSymptoms) > 0)
                                                        <li>
                                                            <h6 class="dropdown-header">Симптомы:</h6>
                                                            @foreach(array_slice($caseSymptoms, 0, 3) as $symptom)
                                                                <li><span class="dropdown-item-text small">{{ $symptom }}</span></li>
                                                            @endforeach
                                                            @if(count($caseSymptoms) > 3)
                                                                <li><span class="dropdown-item-text small text-muted">... и еще {{ count($caseSymptoms) - 3 }}</span></li>
                                                            @endif
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                    @endif
                                                    <li>
                                                        <a class="dropdown-item" href="#">
                                                            <i class="bi bi-info-circle me-2"></i> Детали
                                                        </a>
                                                    </li>
                                                    @if($case->status === 'completed' && $case->status !== 'archived')
                                                        <li>
                                                            <form action="#" method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" 
                                                                        class="dropdown-item text-danger"
                                                                        onclick="return confirm('Перевести в архив?')">
                                                                    <i class="bi bi-archive me-2"></i> В архив
                                                                </button>
                                                            </form>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Мобильная версия (карточки) -->
                <div class="d-lg-none">
                    <div class="row">
                        @foreach($cases as $case)
                        @php
                            $hasMessages = $case->consultationMessages && $case->consultationMessages->count() > 0;
                            $hasReport = $case->analysis_result || in_array($case->status, ['report_ready', 'completed', 'consultation_in_progress']);
                            $caseSymptoms = getCaseSymptoms($case->symptoms ?? []);
                            
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
                                'report_ready' => 'Готов',
                                'consultation_pending' => 'Ожидает',
                                'consultation_in_progress' => 'Консультация',
                                'completed' => 'Завершен',
                                'archived' => 'Архив'
                            ];
                        @endphp
                        <div class="col-12 mb-3">
                            <div class="card case-card">
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Левая часть: информация -->
                                        <div class="col-8">
                                            <div class="case-header mb-2">
                                                <h6 class="mb-1">
                                                    <span class="text-primary">#{{ substr($case->id, 0, 8) }}</span>
                                                    <span class="badge bg-{{ $statusColors[$case->status] ?? 'secondary' }} ms-2">
                                                        {{ $statusLabels[$case->status] ?? $case->status }}
                                                    </span>
                                                </h6>
                                                <div class="car-info small mb-2">
                                                    @if($case->brand)
                                                        <div>
                                                            <i class="bi bi-car-front me-1"></i>
                                                            <strong>{{ $case->brand->name }}</strong>
                                                            @if($case->model)
                                                                <span class="text-muted">/ {{ $case->model->name }}</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                    @if($case->year)
                                                        <span class="me-2">{{ $case->year }} г.</span>
                                                    @endif
                                                    @if($case->mileage)
                                                        <span><i class="bi bi-speedometer2 me-1"></i>{{ number_format($case->mileage, 0, ',', ' ') }} км</span>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <!-- Дата создания -->
                                            <div class="case-date small text-muted">
                                                <i class="bi bi-calendar me-1"></i>
                                                {{ $case->created_at->format('d.m.Y H:i') }}
                                            </div>
                                            
                                            <!-- Симптомы (если есть) -->
                                            @if(count($caseSymptoms) > 0)
                                                <div class="case-symptoms mt-2">
                                                    <span class="badge bg-light text-dark">
                                                        {{ count($caseSymptoms) }} симптомов
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <!-- Правая часть: действия -->
                                        <div class="col-4">
                                            <div class="d-flex flex-column gap-2">
                                                <!-- Чат/Заказ -->
                                                @if(in_array($case->status, ['consultation_in_progress', 'consultation_pending']))
                                                    <a href="{{ route('diagnostic.consultation.show', $case->id) }}" 
                                                       class="btn btn-primary btn-sm w-100">
                                                        <i class="bi bi-chat-left-text"></i> Чат
                                                    </a>
                                                @elseif($case->status === 'report_ready')
                                                    <a href="{{ route('consultation.order.form', ['case' => $case->id]) }}" 
                                                       class="btn btn-success btn-sm w-100">
                                                        <i class="bi bi-chat-dots"></i> Заказать
                                                    </a>
                                                @endif
                                                
                                                <!-- Отчет -->
                                                @if($case->status === 'report_ready' || $case->status === 'completed' || 
                                                    $case->status === 'consultation_in_progress' || $case->status === 'consultation_pending')
                                                    <a href="{{ route('diagnostic.result', $case->id) }}" 
                                                       class="btn btn-info btn-sm w-100"
                                                       target="_blank">
                                                        <i class="bi bi-file-earmark-text"></i> Отчет
                                                    </a>
                                                @endif
                                                
                                                <!-- Просмотр отчета (если есть) -->
                                                @if($hasReport)
                                                    <a href="{{ route('diagnostic.result', $case->id) }}" 
                                                       class="btn btn-outline-info btn-sm w-100">
                                                        <i class="bi bi-eye"></i> Просмотр
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Пагинация -->
                <div class="mt-4">
                    {{ $cases->links(' vendor.pagination.simple-bootstrap-4') }}
                </div>
                
            @else
                <!-- Пустое состояние -->
                <div class="empty-state text-center py-5">
                    <div class="empty-icon mb-4">
                        @switch($status)
                            @case('draft')
                                <i class="bi bi-clipboard text-muted" style="font-size: 4rem;"></i>
                                <h4 class="text-muted mt-3">Черновиков нет</h4>
                                <p class="text-muted mb-4">Создайте новый диагностический случай</p>
                                @break
                            @case('report_ready')
                                <i class="bi bi-check-circle text-muted" style="font-size: 4rem;"></i>
                                <h4 class="text-muted mt-3">Нет готовых к консультации случаев</h4>
                                <p class="text-muted mb-4">Дождитесь завершения анализа текущих случаев</p>
                                @break
                            @case('consultation_in_progress')
                                <i class="bi bi-chat-dots text-muted" style="font-size: 4rem;"></i>
                                <h4 class="text-muted mt-3">Активных консультаций нет</h4>
                                <p class="text-muted mb-4">Закажите консультацию для готовых случаев</p>
                                @break
                            @default
                                <i class="bi bi-clipboard-x text-muted" style="font-size: 4rem;"></i>
                                <h4 class="text-muted mt-3">Диагностических случаев нет</h4>
                                <p class="text-muted mb-4">Создайте новый диагностический случай</p>
                        @endswitch
                    </div>
                    <a href="{{ route('diagnostic.start') }}" class="btn btn-primary btn-lg">
                        <i class="bi bi-plus-circle me-2"></i>Создать диагностику
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Стили для кнопок статусов */
    .btn-status {
        padding: 0.5rem 1rem;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        background: white;
        color: #495057;
        text-decoration: none;
        transition: all 0.2s;
        display: flex;
        align-items: center;
    }
    
    .btn-status:hover {
        background: #f8f9fa;
        border-color: #adb5bd;
        transform: translateY(-1px);
    }
    
    .btn-status.active {
        background: #0d6efd;
        color: white;
        border-color: #0d6efd;
        box-shadow: 0 2px 4px rgba(13, 110, 253, 0.2);
    }
    
    .status-label {
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    /* Стили для таблицы */
    .table-row-hover:hover {
        background-color: rgba(13, 110, 253, 0.02) !important;
    }
    
    .case-id {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    
    .message-indicator {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 0.75rem;
    }
    
    .car-info {
        display: flex;
        flex-direction: column;
    }
    
    .car-brand {
        display: flex;
        align-items: center;
    }
    
    .year-badge {
        font-size: 0.85rem;
        padding: 0.25rem 0.5rem;
    }
    
    .mileage-info {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    
    .status-badge {
        font-size: 0.8rem;
        padding: 0.35rem 0.65rem;
        display: inline-flex;
        align-items: center;
    }
    
    .chat-action {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .chat-btn, .order-btn, .report-btn {
        min-width: 80px;
        font-size: 0.85rem;
        padding: 0.25rem 0.75rem;
    }
    
    .chat-btn:hover, .order-btn:hover, .report-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .last-message-time, .no-messages {
        font-size: 0.75rem;
    }
    
    .analyzing-badge, .draft-badge {
        font-size: 0.8rem;
        padding: 0.35rem 0.65rem;
    }
    
    .date-info {
        display: flex;
        flex-direction: column;
    }
    
    .created-date {
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .created-time {
        font-size: 0.75rem;
    }
    
    .action-buttons {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }
    
    /* Стили для мобильных карточек */
    .case-card {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        transition: all 0.2s;
    }
    
    .case-card:hover {
        border-color: #0d6efd;
        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.1);
    }
    
    .case-header h6 {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .case-symptoms .badge {
        font-size: 0.75rem;
    }
    
    /* Адаптивность */
    @media (max-width: 768px) {
        .btn-status {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
        
        .status-label {
            font-size: 0.8rem;
        }
        
        .case-card .btn-sm {
            font-size: 0.8rem;
            padding: 0.2rem 0.5rem;
        }
    }
    
    @media (max-width: 576px) {
        .btn-status {
            padding: 0.3rem 0.6rem;
            font-size: 0.75rem;
        }
        
        .status-label {
            display: none;
        }
        
        .btn-status i {
            margin-right: 0 !important;
        }
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
    
    // Подсветка активного фильтра
    const activeFilter = document.querySelector('.btn-status.active');
    if (activeFilter) {
        activeFilter.style.transform = 'translateY(-2px)';
        activeFilter.style.boxShadow = '0 4px 8px rgba(13, 110, 253, 0.2)';
    }
    
    // Анимация при наведении на строки таблицы
    const tableRows = document.querySelectorAll('.table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(2px)';
            this.style.transition = 'all 0.2s';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
    
    // Анимация кнопок чата
    const chatButtons = document.querySelectorAll('.chat-btn, .order-btn');
    chatButtons.forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px) scale(1.05)';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
});
</script>
@endpush