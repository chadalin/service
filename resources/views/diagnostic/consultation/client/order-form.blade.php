<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои консультации - Диагностика</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        h1 {
            color: #333;
            margin: 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #545b62;
        }
        .status-filter {
            margin-bottom: 20px;
        }
        .status-filter a {
            margin-right: 10px;
            padding: 5px 15px;
            background: #e9ecef;
            border-radius: 20px;
            text-decoration: none;
            color: #495057;
        }
        .status-filter a.active {
            background: #007bff;
            color: white;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background: #ffc107; color: #212529; }
        .status-scheduled { background: #17a2b8; color: white; }
        .status-in_progress { background: #007bff; color: white; }
        .status-completed { background: #28a745; color: white; }
        .status-cancelled { background: #dc3545; color: white; }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .pagination {
            margin-top: 30px;
            text-align: center;
        }
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 2px;
            background: #e9ecef;
            border-radius: 4px;
            text-decoration: none;
            color: #007bff;
        }
        .pagination .active {
            background: #007bff;
            color: white;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .actions {
            display: flex;
            gap: 8px;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Мои диагностические консультации</h1>
            <div>
                <!-- Проверяем существование маршрута -->
                @if(Route::has('diagnostic.consultation.order-form'))
                    <a href="{{ route('diagnostic.consultation.order-form') }}" class="btn">
                        📝 Заказать консультацию
                    </a>
                @elseif(Route::has('diagnostic.show-step3'))
                    <a href="{{ route('diagnostic.show-step3') }}" class="btn">
                        📝 Создать диагностический случай
                    </a>
                @else
                    <a href="{{ url('/diagnostic/step1') }}" class="btn">
                        🚗 Начать диагностику
                    </a>
                @endif
            </div>
        </div>

        <!-- Сообщения об успехе -->
        @if(session('success'))
            <div class="alert alert-success">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                ❌ {{ session('error') }}
            </div>
        @endif

        <!-- Фильтр по статусам -->
        <div class="status-filter">
            <strong>Фильтр по статусу:</strong>
            <br><br>
            <a href="?status=all" class="{{ $status == 'all' ? 'active' : '' }}">Все</a>
            <a href="?status=pending" class="{{ $status == 'pending' ? 'active' : '' }}">Ожидание</a>
            <a href="?status=scheduled" class="{{ $status == 'scheduled' ? 'active' : '' }}">Запланирована</a>
            <a href="?status=in_progress" class="{{ $status == 'in_progress' ? 'active' : '' }}">В процессе</a>
            <a href="?status=completed" class="{{ $status == 'completed' ? 'active' : '' }}">Завершена</a>
            <a href="?status=cancelled" class="{{ $status == 'cancelled' ? 'active' : '' }}">Отменена</a>
        </div>

        <!-- Список консультаций -->
        @if($consultations->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Тип консультации</th>
                        <th>Статус</th>
                        <th>Цена</th>
                        <th>Дата создания</th>
                        <th>Дата консультации</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($consultations as $consultation)
                    <tr>
                        <td>#{{ $consultation->id }}</td>
                        <td>
                            @switch($consultation->type)
                                @case('basic') Базовая @break
                                @case('premium') Премиум @break
                                @case('expert') Экспертная @break
                                @default {{ $consultation->type }}
                            @endswitch
                        </td>
                        <td>
                            @php
                                $statusClass = 'status-' . str_replace(' ', '_', $consultation->status);
                            @endphp
                            <span class="status-badge {{ $statusClass }}">
                                @switch($consultation->status)
                                    @case('pending') ⏳ Ожидание @break
                                    @case('scheduled') 📅 Запланирована @break
                                    @case('in_progress') 🔄 В процессе @break
                                    @case('completed') ✅ Завершена @break
                                    @case('cancelled') ❌ Отменена @break
                                    @default {{ $consultation->status }}
                                @endswitch
                            </span>
                        </td>
                        <td>
                            @if($consultation->price)
                                {{ number_format($consultation->price, 2) }} руб.
                            @else
                                <em>Не указана</em>
                            @endif
                        </td>
                        <td>{{ $consultation->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            @if($consultation->scheduled_at)
                                {{ \Carbon\Carbon::parse($consultation->scheduled_at)->format('d.m.Y H:i') }}
                            @else
                                <em>Не назначена</em>
                            @endif
                        </td>
                        <td class="actions">
                            <!-- Проверяем существование маршрута -->
                            @if(Route::has('diagnostic.consultation.show-client'))
                                <a href="{{ route('diagnostic.consultation.show-client', $consultation) }}" class="btn btn-sm" title="Просмотр">
                                    👁️ Просмотр
                                </a>
                            @else
                                <a href="{{ url('/diagnostic/consultation/' . $consultation->id) }}" class="btn btn-sm" title="Просмотр">
                                    👁️ Просмотр
                                </a>
                            @endif
                            
                            @if($consultation->status == 'pending' || $consultation->status == 'scheduled')
                                @if(Route::has('diagnostic.consultation.cancel'))
                                    <form action="{{ route('diagnostic.consultation.cancel', $consultation) }}" method="POST" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-secondary" title="Отменить" onclick="return confirm('Вы уверены, что хотите отменить консультацию?')">
                                            ❌ Отменить
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Пагинация -->
            <div class="pagination">
                {{ $consultations->links('vendor.pagination.simple-bootstrap-4') }}
            </div>
        @else
            <div class="empty-state">
                <h3>📭 Консультации не найдены</h3>
                <p>У вас пока нет диагностических консультаций.</p>
                <br>
                <!-- Альтернативные ссылки -->
                @if(Route::has('diagnostic.step1'))
                    <a href="{{ route('diagnostic.step1') }}" class="btn">
                        🚗 Начать диагностику
                    </a>
                @elseif(Route::has('diagnostic.index'))
                    <a href="{{ route('diagnostic.index') }}" class="btn">
                        🔧 Диагностика
                    </a>
                @else
                    <a href="{{ url('/diagnostic') }}" class="btn">
                        🛠️ Создать диагностический случай
                    </a>
                @endif
            </div>
        @endif

        <!-- Информация о системе -->
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; color: #6c757d; font-size: 12px;">
            <p>Всего консультаций: <strong>{{ $consultations->total() }}</strong></p>
            <p>Текущий фильтр: <strong>{{ $status == 'all' ? 'Все статусы' : ucfirst($status) }}</strong></p>
        </div>
    </div>

    <script>
        // Простой JavaScript для улучшения UX
        document.addEventListener('DOMContentLoaded', function() {
            // Подтверждение отмены консультации
            const cancelForms = document.querySelectorAll('form[action*="cancel"]');
            cancelForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Вы уверены, что хотите отменить эту консультацию?')) {
                        e.preventDefault();
                    }
                });
            });

            // Автоматическое скрытие алертов через 5 секунд
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                });
            }, 5000);
        });
    </script>
</body>
</html>