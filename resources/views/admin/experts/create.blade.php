@extends('layouts.diagnostic')

@section('title', 'Профиль эксперта: ' . $expert->name)

@section('content')
<div class="container-fluid">
    <!-- Шапка профиля -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            @if($expert->expert_avatar)
                                <img src="{{ asset('storage/' . $expert->expert_avatar) }}" 
                                     alt="{{ $expert->name }}" 
                                     class="rounded-circle" 
                                     width="100" 
                                     height="100">
                            @else
                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto" 
                                     style="width: 100px; height: 100px;">
                                    <i class="bi bi-person fs-1"></i>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h3 class="mb-1">{{ $expert->name }}</h3>
                            <p class="text-muted mb-2">{{ $expert->email }}</p>
                            <div class="d-flex flex-wrap gap-2">
                                @if($expert->expert_specialization)
                                    <span class="badge bg-primary">{{ $expert->expert_specialization }}</span>
                                @else
                                    <span class="badge bg-secondary">Специализация не указана</span>
                                @endif
                                
                                @if($expert->expert_level)
                                    <span class="badge bg-info">{{ $expert->expert_level }}</span>
                                @endif
                                
                                @if($expert->status === 'active')
                                    <span class="badge bg-success">Активен</span>
                                @else
                                    <span class="badge bg-secondary">Неактивен</span>
                                @endif
                                
                                @if($expert->expert_is_available)
                                    <span class="badge bg-success"><i class="bi bi-circle-fill"></i> Доступен</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-circle-fill"></i> Недоступен</span>
                                @endif
                                
                                @if($expert->is_expert)
                                    <span class="badge bg-primary">Эксперт</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-grid gap-2">
                                <a href="{{ route('admin.experts.edit', $expert->id) }}" 
                                   class="btn btn-primary">
                                    <i class="bi bi-pencil me-2"></i>Редактировать
                                </a>
                                <a href="{{ route('admin.consultations.index', ['expert' => $expert->id]) }}" 
                                   class="btn btn-outline-info">
                                    <i class="bi bi-chat-dots me-2"></i>Консультации эксперта
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h5 class="card-title">Всего консультаций</h5>
                    <h2 class="text-primary">{{ $stats['total'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h5 class="card-title">Завершено</h5>
                    <h2 class="text-success">{{ $stats['completed'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h5 class="card-title">Средний рейтинг</h5>
                    <h2 class="text-warning">
                        @if(isset($stats['avg_rating']) && $stats['avg_rating'])
                            {{ number_format($stats['avg_rating'], 1) }}
                        @else
                            Н/Д
                        @endif
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h5 class="card-title">Общая выручка</h5>
                    <h2 class="text-info">
                        @if(isset($stats['total_revenue']) && $stats['total_revenue'])
                            {{ number_format($stats['total_revenue'], 0, '', ' ') }} ₽
                        @else
                            0 ₽
                        @endif
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Информация об эксперте -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Информация об эксперте</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Телефон:</th>
                                    <td>{{ $expert->phone ?? 'Не указан' }}</td>
                                </tr>
                                <tr>
                                    <th>Опыт работы:</th>
                                    <td>{{ $expert->expert_experience_years ?? 0 }} лет</td>
                                </tr>
                                <tr>
                                    <th>Ставка в час:</th>
                                    <td>
                                        @if($expert->expert_hourly_rate)
                                            {{ number_format($expert->expert_hourly_rate, 0, '', ' ') }} ₽/час
                                        @else
                                            Не указана
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Макс. консультаций в день:</th>
                                    <td>{{ $expert->expert_max_consultations ?? 'Не ограничено' }}</td>
                                </tr>
                                <tr>
                                    <th>Компания:</th>
                                    <td>{{ $expert->company_name ?? 'Не указана' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">График работы:</th>
                                    <td>
                                        @if($expert->expert_working_hours_start && $expert->expert_working_hours_end)
                                            {{ $expert->expert_working_hours_start }} - {{ $expert->expert_working_hours_end }}
                                        @else
                                            Не установлен
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Дни работы:</th>
                                    <td>
                                        @if($expert->expert_available_days)
                                            @php
                                                $daysMap = [
                                                    1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 
                                                    4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'
                                                ];
                                                // Предполагаем, что expert_available_days - это JSON массив
                                                if (is_string($expert->expert_available_days)) {
                                                    $availableDays = json_decode($expert->expert_available_days, true);
                                                } else {
                                                    $availableDays = $expert->expert_available_days;
                                                }
                                                $availableDays = array_map(function($day) use ($daysMap) {
                                                    return $daysMap[$day] ?? $day;
                                                }, $availableDays ?? []);
                                            @endphp
                                            {{ implode(', ', $availableDays) }}
                                        @else
                                            Не установлены
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Перерыв:</th>
                                    <td>
                                        @if($expert->expert_break_start && $expert->expert_break_end)
                                            {{ $expert->expert_break_start }} - {{ $expert->expert_break_end }}
                                        @else
                                            Не установлен
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Роль:</th>
                                    <td>
                                        <span class="badge bg-primary">{{ $expert->role }}</span>
                                        @if($expert->is_expert)
                                            <span class="badge bg-info ms-1">Эксперт</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Дата регистрации:</th>
                                    <td>{{ $expert->created_at->format('d.m.Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Дополнительная информация -->
                    @if($expert->expert_description)
                    <div class="mt-4">
                        <h6>Обо мне:</h6>
                        <p>{{ $expert->expert_description }}</p>
                    </div>
                    @endif
                    
                    @if($expert->expert_qualifications)
                    <div class="mt-3">
                        <h6>Квалификации и сертификаты:</h6>
                        <p>{{ $expert->expert_qualifications }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Дополнительная статистика -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Активность</h5>
                </div>
                <div class="card-body">
                    @if(isset($stats['pending']) || isset($stats['in_progress']))
                    <table class="table table-sm">
                        @if(isset($stats['pending']) && $stats['pending'] > 0)
                        <tr>
                            <td>Ожидают назначения:</td>
                            <td class="text-end">
                                <span class="badge bg-warning">{{ $stats['pending'] }}</span>
                            </td>
                        </tr>
                        @endif
                        
                        @if(isset($stats['in_progress']) && $stats['in_progress'] > 0)
                        <tr>
                            <td>В процессе:</td>
                            <td class="text-end">
                                <span class="badge bg-info">{{ $stats['in_progress'] }}</span>
                            </td>
                        </tr>
                        @endif
                        
                        @if(isset($stats['scheduled']) && $stats['scheduled'] > 0)
                        <tr>
                            <td>Запланированы:</td>
                            <td class="text-end">
                                <span class="badge bg-primary">{{ $stats['scheduled'] }}</span>
                            </td>
                        </tr>
                        @endif
                        
                        @if(isset($stats['cancelled']) && $stats['cancelled'] > 0)
                        <tr>
                            <td>Отменены:</td>
                            <td class="text-end">
                                <span class="badge bg-danger">{{ $stats['cancelled'] }}</span>
                            </td>
                        </tr>
                        @endif
                    </table>
                    @else
                    <p class="text-muted mb-0">Статистика активности пока недоступна</p>
                    @endif
                    
                    @if($expert->expert_working_hours_start && $expert->expert_working_hours_end)
                    <div class="mt-4">
                        <h6>Текущий статус:</h6>
                        @php
                            $now = now();
                            $currentTime = $now->format('H:i');
                            $currentDay = $now->dayOfWeekIso; // 1-7, где 1=понедельник
                            
                            // Проверяем, рабочий ли день
                            $isWorkingDay = true;
                            if ($expert->expert_available_days) {
                                if (is_string($expert->expert_available_days)) {
                                    $availableDays = json_decode($expert->expert_available_days, true);
                                } else {
                                    $availableDays = $expert->expert_available_days;
                                }
                                $isWorkingDay = in_array($currentDay, $availableDays ?? []);
                            }
                            
                            // Проверяем, рабочее ли время
                            $isWorkingTime = $currentTime >= $expert->expert_working_hours_start && 
                                           $currentTime <= $expert->expert_working_hours_end;
                            
                            // Проверяем, не перерыв ли
                            $isBreakTime = false;
                            if ($expert->expert_break_start && $expert->expert_break_end) {
                                $isBreakTime = $currentTime >= $expert->expert_break_start && 
                                             $currentTime <= $expert->expert_break_end;
                            }
                        @endphp
                        
                        <div class="d-flex align-items-center">
                            @if($expert->expert_is_available && $isWorkingDay && $isWorkingTime && !$isBreakTime)
                                <span class="badge bg-success me-2"><i class="bi bi-circle-fill"></i></span>
                                <span>Доступен для консультаций</span>
                            @elseif($isBreakTime)
                                <span class="badge bg-warning me-2"><i class="bi bi-circle-fill"></i></span>
                                <span>Перерыв ({{ $expert->expert_break_start }} - {{ $expert->expert_break_end }})</span>
                            @elseif(!$isWorkingTime && $isWorkingDay)
                                <span class="badge bg-secondary me-2"><i class="bi bi-circle-fill"></i></span>
                                <span>Не рабочее время (график: {{ $expert->expert_working_hours_start }} - {{ $expert->expert_working_hours_end }})</span>
                            @elseif(!$isWorkingDay)
                                <span class="badge bg-secondary me-2"><i class="bi bi-circle-fill"></i></span>
                                <span>Выходной</span>
                            @elseif(!$expert->expert_is_available)
                                <span class="badge bg-danger me-2"><i class="bi bi-circle-fill"></i></span>
                                <span>Недоступен</span>
                            @endif
                        </div>
                        
                        <small class="text-muted mt-2 d-block">
                            @if($isWorkingDay && $expert->expert_available_days)
                                @php
                                    $availableDays = is_string($expert->expert_available_days) 
                                        ? json_decode($expert->expert_available_days, true) 
                                        : $expert->expert_available_days;
                                    $dayNames = array_map(function($day) use ($daysMap) {
                                        return $daysMap[$day] ?? $day;
                                    }, $availableDays ?? []);
                                @endphp
                                Рабочие дни: {{ implode(', ', $dayNames) }}
                            @endif
                        </small>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Быстрые действия -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Действия</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.consultations.index', ['expert' => $expert->id, 'status' => 'pending']) }}" 
                           class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-clock me-1"></i> Ожидающие назначения
                        </a>
                        <a href="{{ route('admin.consultations.index', ['expert' => $expert->id, 'status' => 'in_progress']) }}" 
                           class="btn btn-outline-info btn-sm">
                            <i class="bi bi-gear me-1"></i> Текущие консультации
                        </a>
                        <a href="{{ route('diagnostic.consultation.expert.dashboard') }}" 
                           class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-speedometer2 me-1"></i> Панель эксперта
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Стили для статусов -->
<style>
    .badge {
        font-size: 0.75rem;
        padding: 0.35em 0.65em;
    }
    
    .card {
        border-radius: 10px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        font-weight: 600;
    }
    
    .table-sm th {
        font-weight: 500;
        color: #6c757d;
    }
</style>

<!-- Скрипт для обновления статуса в реальном времени -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Функция для форматирования времени
    function formatTime(date) {
        return date.toLocaleTimeString('ru-RU', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    }
    
    // Функция для проверки доступности эксперта
    function checkExpertAvailability() {
        const now = new Date();
        const currentTime = formatTime(now);
        const currentDay = now.getDay(); // 0-6, где 0=воскресенье
        const daysMap = {0: 7, 1: 1, 2: 2, 3: 3, 4: 4, 5: 5, 6: 6}; // Преобразование в 1-7
        
        // Здесь можно добавить AJAX запрос для получения актуального статуса эксперта
        // или использовать данные, переданные из контроллера
        console.log('Текущее время:', currentTime, 'День недели:', currentDay);
        
        // Обновляем время на странице каждую минуту
        const timeElements = document.querySelectorAll('.current-time');
        timeElements.forEach(el => {
            el.textContent = formatTime(now);
        });
    }
    
    // Проверяем доступность каждую минуту
    setInterval(checkExpertAvailability, 60000);
    
    // Первоначальная проверка
    checkExpertAvailability();
});
</script>
@endsection