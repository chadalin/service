@extends('layouts.app')

@section('title', 'Обработка документов')

@push('styles')
<style>
    .status-badge {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
    }
    
    .status-uploaded { background-color: #6c757d; color: white; }
    .status-processing { background-color: #ffc107; color: black; }
    .status-parsed { background-color: #17a2b8; color: white; }
    .status-indexed { background-color: #28a745; color: white; }
    .status-processed { background-color: #007bff; color: white; }
    .status-parse_error { background-color: #dc3545; color: white; }
    .status-index_error { background-color: #dc3545; color: white; }
    
    .action-btn {
        margin: 2px;
        font-size: 0.8rem;
    }
    
    .stats-card {
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 1rem;
    }
    
    .progress-thin {
        height: 4px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header">
                <h1 class="h2 mb-0">
                    <i class="bi bi-gear me-2"></i>Обработка документов
                </h1>
                <p class="text-muted mb-0">Парсинг и индексация загруженных файлов</p>
            </div>
        </div>
    </div>

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Всего документов</h6>
                            <h3 class="mb-0">{{ $stats['total'] }}</h3>
                        </div>
                        <i class="bi bi-files display-4 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Для парсинга</h6>
                            <h3 class="mb-0">{{ $forParsing }}</h3>
                        </div>
                        <i class="bi bi-file-earmark-text display-4 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Для индексации</h6>
                            <h3 class="mb-0">{{ $forIndexing }}</h3>
                        </div>
                        <i class="bi bi-search display-4 text-info"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Обработано</h6>
                            <h3 class="mb-0">{{ $stats['processed'] }}</h3>
                        </div>
                        <i class="bi bi-check-circle display-4 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Массовые действия -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Массовые действия</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-grid gap-2">
                                <button class="btn btn-warning" id="parseAllBtn">
                                    <i class="bi bi-file-earmark-text me-2"></i>
                                    Распарсить все ({{ $forParsing }})
                                </button>
                                <small class="text-muted">Извлечет текст из PDF/DOC файлов</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-grid gap-2">
                                <button class="btn btn-info" id="indexAllBtn">
                                    <i class="bi bi-search me-2"></i>
                                    Индексировать все ({{ $forIndexing }})
                                </button>
                                <small class="text-muted">Определит категории и создаст поисковый индекс</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Таблица документов -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Документы</h5>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-secondary" id="refreshBtn">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="documentsTable">
                            <thead>
                                <tr>
                                    <th width="50">ID</th>
                                    <th>Название</th>
                                    <th>Марка/Модель</th>
                                    <th>Тип файла</th>
                                    <th>Статус</th>
                                    <th>Парсинг</th>
                                    <th>Индекс</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($documents as $document)
                                <tr data-id="{{ $document->id }}">
                                    <td>{{ $document->id }}</td>
                                    <td>
                                        <strong>{{ $document->title }}</strong><br>
                                        <small class="text-muted">{{ $document->original_filename }}</small>
                                    </td>
                                    <td>
                                        @if($document->carModel)
                                            {{ $document->carModel->brand->name ?? '' }} 
                                            {{ $document->carModel->name ?? '' }}
                                        @else
                                            <span class="text-muted">Не указана</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ strtoupper($document->file_type) }}</span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-{{ $document->status }}">
                                            @if($document->status == 'uploaded')
                                                Загружен
                                            @elseif($document->status == 'processing')
                                                В обработке
                                            @elseif($document->status == 'parsed')
                                                Распарсен
                                            @elseif($document->status == 'indexed')
                                                Проиндексирован
                                            @elseif($document->status == 'processed')
                                                Обработан
                                            @elseif($document->status == 'parse_error')
                                                Ошибка парсинга
                                            @elseif($document->status == 'index_error')
                                                Ошибка индексации
                                            @else
                                                {{ $document->status }}
                                            @endif
                                        </span>
                                    </td>
                                    <td>
                                        @if($document->is_parsed)
                                            <span class="badge bg-success">✅</span>
                                            @if($document->parsing_quality)
                                                <small>{{ round($document->parsing_quality * 100) }}%</small>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary">❌</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($document->search_indexed)
                                            <span class="badge bg-success">✅</span>
                                            @if($document->detected_section)
                                                <small>{{ $document->detected_section }}</small>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary">❌</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            @if(!$document->is_parsed)
                                            <button class="btn btn-outline-warning action-btn parse-btn" 
                                                    title="Распарсить"
                                                    data-id="{{ $document->id }}">
                                                <i class="bi bi-file-earmark-text"></i>
                                            </button>
                                            @endif
                                            
                                            @if($document->is_parsed && !$document->search_indexed)
                                            <button class="btn btn-outline-info action-btn index-btn" 
                                                    title="Индексировать"
                                                    data-id="{{ $document->id }}">
                                                <i class="bi bi-search"></i>
                                            </button>
                                            @endif
                                            
                                            @if($document->is_parsed && $document->search_indexed)
                                            <button class="btn btn-outline-success" disabled title="Обработан">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                            @endif
                                            
                                            <button class="btn btn-outline-primary action-btn process-btn" 
                                                    title="Полная обработка"
                                                    data-id="{{ $document->id }}">
                                                <i class="bi bi-play-circle"></i>
                                            </button>
                                            
                                            <button class="btn btn-outline-danger action-btn reset-btn" 
                                                    title="Сбросить статус"
                                                    data-id="{{ $document->id }}">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    {{ $documents->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для прогресса -->
<div class="modal fade" id="progressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Обработка документов</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="progressMessage" class="mb-2">Подготовка...</div>
                <div class="progress mb-3">
                    <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                </div>
                <div id="progressDetails" class="small text-muted"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Подключение jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    console.log('Document Processing Page Loaded');
    
    let processing = false;
    const progressModal = new bootstrap.Modal(document.getElementById('progressModal'));
    
    // Настройка CSRF для всех AJAX запросов
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    // Проверяем CSRF токен
    console.log('CSRF Token:', $('meta[name="csrf-token"]').attr('content'));
    
    // Функция для обновления таблицы
    function refreshTable() {
        console.log('Refreshing table...');
        window.location.reload();
    }
    
    // Функция для показа прогресса
    function showProgress(message, percent) {
        console.log('Progress:', message, percent + '%');
        $('#progressMessage').text(message);
        $('#progressBar').css('width', percent + '%');
        progressModal.show();
    }
    
    // Функция для обработки одного документа
    function processDocument(action, id) {
        console.log('Processing document:', action, id);
        
        if (processing) {
            console.log('Already processing, skipping');
            return;
        }
        
        processing = true;
        
        // Определяем URL для запроса
        const baseUrl = '{{ url("/admin/documents-processing") }}';
        let url = '';
        let method = 'POST';
        
        switch(action) {
            case 'parse':
                url = baseUrl + '/parse/' + id;
                break;
            case 'index':
                url = baseUrl + '/index/' + id;
                break;
            case 'process':
                url = baseUrl + '/process/' + id;
                break;
            case 'reset':
                url = baseUrl + '/reset/' + id;
                break;
        }
        
        console.log('Request URL:', url);
        
        // Сообщения для пользователя
        const messages = {
            'parse': 'Парсинг документа ID: ' + id,
            'index': 'Индексация документа ID: ' + id,
            'process': 'Полная обработка документа ID: ' + id,
            'reset': 'Сброс статуса документа ID: ' + id
        };
        
        showProgress(messages[action], 30);
        
        // Отправляем AJAX запрос
        $.ajax({
            url: url,
            method: method,
            dataType: 'json',
            success: function(response) {
                console.log('Success response:', response);
                
                if (response.success) {
                    showProgress('Успешно! ' + (response.message || 'Документ обработан'), 100);
                    
                    setTimeout(function() {
                        progressModal.hide();
                        processing = false;
                        showToast(response.message || 'Операция выполнена успешно', 'success');
                        
                        // Обновляем таблицу через 1 секунду
                        setTimeout(refreshTable, 1000);
                    }, 1500);
                } else {
                    showProgress('Ошибка: ' + response.message, 100);
                    
                    setTimeout(function() {
                        progressModal.hide();
                        processing = false;
                        showToast(response.message || 'Произошла ошибка', 'danger');
                    }, 2000);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.status, xhr.statusText, error);
                console.error('Response:', xhr.responseText);
                
                showProgress('Ошибка сервера: ' + xhr.status, 100);
                
                setTimeout(function() {
                    progressModal.hide();
                    processing = false;
                    showToast('Ошибка сервера: ' + xhr.status + ' ' + xhr.statusText, 'danger');
                }, 2000);
            }
        });
    }
    
    // Функция для массовой обработки
    function processMultiple(action) {
        console.log('Process multiple:', action);
        
        if (processing) {
            console.log('Already processing, skipping');
            return;
        }
        
        processing = true;
        
        const baseUrl = '{{ url("/admin/documents-processing") }}';
        let url = '';
        
        switch(action) {
            case 'parse':
                url = baseUrl + '/parse-multiple';
                break;
            case 'index':
                url = baseUrl + '/index-multiple';
                break;
        }
        
        console.log('Multiple request URL:', url);
        
        const messages = {
            'parse': 'Массовый парсинг документов...',
            'index': 'Массовая индексация документов...'
        };
        
        showProgress(messages[action], 10);
        
        $.ajax({
            url: url,
            method: 'POST',
            dataType: 'json',
            data: {
                document_ids: []
            },
            success: function(response) {
                console.log('Multiple success response:', response);
                
                if (response.success) {
                    // Анимируем прогресс
                    let progress = 10;
                    const interval = setInterval(function() {
                        progress += 5;
                        const successCount = response.success_count || 0;
                        const totalCount = response.total || 0;
                        showProgress(
                            messages[action] + ' (' + successCount + '/' + totalCount + ')',
                            progress
                        );
                        
                        if (progress >= 90) {
                            clearInterval(interval);
                            showProgress('Завершено! Обработано: ' + successCount + ' из ' + totalCount, 100);
                            
                            setTimeout(function() {
                                progressModal.hide();
                                processing = false;
                                
                                const errorCount = response.error_count || 0;
                                const toastType = errorCount > 0 ? 'warning' : 'success';
                                const toastMessage = 'Обработано: ' + successCount + ' из ' + totalCount + 
                                                    (errorCount > 0 ? ' (ошибок: ' + errorCount + ')' : '');
                                
                                showToast(toastMessage, toastType);
                                
                                // Обновляем таблицу через 2 секунды
                                setTimeout(refreshTable, 2000);
                            }, 1500);
                        }
                    }, 300);
                } else {
                    showProgress('Ошибка: ' + response.message, 100);
                    
                    setTimeout(function() {
                        progressModal.hide();
                        processing = false;
                        showToast(response.message || 'Произошла ошибка', 'danger');
                    }, 2000);
                }
            },
            error: function(xhr, status, error) {
                console.error('Multiple AJAX Error:', xhr.status, xhr.statusText, error);
                
                showProgress('Ошибка сервера: ' + xhr.status, 100);
                
                setTimeout(function() {
                    progressModal.hide();
                    processing = false;
                    showToast('Ошибка сервера: ' + xhr.status + ' ' + xhr.statusText, 'danger');
                }, 2000);
            }
        });
    }
    
    // Обработчики для кнопок одиночной обработки
    $(document).on('click', '.parse-btn', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        console.log('Parse button clicked for ID:', id);
        
        if (confirm('Выполнить парсинг документа ID ' + id + '?')) {
            processDocument('parse', id);
        }
    });
    
    $(document).on('click', '.index-btn', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        console.log('Index button clicked for ID:', id);
        
        if (confirm('Выполнить индексацию документа ID ' + id + '?')) {
            processDocument('index', id);
        }
    });
    
    $(document).on('click', '.process-btn', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        console.log('Process button clicked for ID:', id);
        
        if (confirm('Выполнить полную обработку документа ID ' + id + '?')) {
            processDocument('process', id);
        }
    });
    
    $(document).on('click', '.reset-btn', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        console.log('Reset button clicked for ID:', id);
        
        if (confirm('Сбросить статус документа ID ' + id + '?\n\nВнимание: все данные парсинга и индексации будут удалены!')) {
            processDocument('reset', id);
        }
    });
    
    // Обработчики для массовых кнопок
    $('#parseAllBtn').on('click', function(e) {
        e.preventDefault();
        console.log('Parse All button clicked');
        
        if (confirm('Выполнить массовый парсинг всех документов?\n\nКоличество документов: {{ $forParsing }}')) {
            processMultiple('parse');
        }
    });
    
    $('#indexAllBtn').on('click', function(e) {
        e.preventDefault();
        console.log('Index All button clicked');
        
        if (confirm('Выполнить массовую индексацию всех документов?\n\nКоличество документов: {{ $forIndexing }}')) {
            processMultiple('index');
        }
    });
    
    // Кнопка обновления
    $('#refreshBtn').on('click', function(e) {
        e.preventDefault();
        console.log('Refresh button clicked');
        refreshTable();
    });
    
    // Функция для показа уведомлений
    function showToast(message, type = 'info') {
        // Создаем уникальный ID для тоста
        const toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        
        // Создаем HTML тоста
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        // Проверяем, есть ли контейнер для тостов
        let container = $('.toast-container');
        if (!container.length) {
            container = $('<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
            $('body').append(container);
        }
        
        // Добавляем тост в контейнер
        container.append(toastHtml);
        
        // Показываем тост
        const toastElement = document.getElementById(toastId);
        if (toastElement) {
            const toast = new bootstrap.Toast(toastElement, {
                autohide: true,
                delay: 5000
            });
            toast.show();
            
            // Удаляем тост после скрытия
            toastElement.addEventListener('hidden.bs.toast', function() {
                $(this).remove();
            });
        }
    }
    
    // Автоматическое обновление таблицы каждые 30 секунд
    setInterval(function() {
        if (!processing) {
            console.log('Auto-refresh triggered');
            refreshTable();
        }
    }, 30000);
    
    console.log('JavaScript initialized successfully');
    console.log('Parse buttons found:', $('.parse-btn').length);
    console.log('Index buttons found:', $('.index-btn').length);
    console.log('Process buttons found:', $('.process-btn').length);
    console.log('Reset buttons found:', $('.reset-btn').length);
});
</script>
@endpush