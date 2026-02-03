@extends('layouts.processing')

@section('title', 'Страницы документа: ' . ($document->title ?? ''))
@section('page_title', 'Страницы документа')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.documents.processing.index') }}">Обработка</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.documents.processing.advanced', $document->id) }}">Обработка документа</a></li>
    <li class="breadcrumb-item active">Страницы</li>
@endsection

@section('content')
<div class="container">
    <!-- Заголовок -->
    <div class="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-2"><i class="bi bi-file-text"></i> Страницы документа</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.documents.processing.index') }}" class="text-white">Обработка</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.documents.processing.advanced', $document->id) }}" class="text-white">{{ Str::limit($document->title, 20) }}</a></li>
                        <li class="breadcrumb-item active text-white">Страницы</li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ route('admin.documents.processing.advanced', $document->id) }}" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left"></i> Назад
                </a>
            </div>
        </div>
    </div>

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <h2 class="text-primary">{{ $pages->total() }}</h2>
                <small>Всего страниц</small>
            </div>
        </div>
        <div class="col-md-3">
            @php
                $pagesWithImages = $pages->where('has_images', true)->count();
            @endphp
            <div class="stat-card">
                <h2 class="text-success">{{ $pagesWithImages }}</h2>
                <small>Страниц с изображениями</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h2 class="text-info">{{ number_format($pages->sum('word_count')) }}</h2>
                <small>Всего слов</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h2 class="text-warning">{{ round($pages->avg('parsing_quality') * 100, 1) }}%</h2>
                <small>Среднее качество</small>
            </div>
        </div>
    </div>

    <!-- Поиск и фильтры -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.documents.processing.pages.list', $document->id) }}" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Поиск по тексту</label>
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control" 
                                   name="search" 
                                   value="{{ request('search') }}"
                                   placeholder="Введите текст для поиска...">
                            @if(request('search'))
                                <a href="{{ route('admin.documents.processing.pages.list', $document->id) }}" 
                                   class="btn btn-outline-secondary" type="button">
                                    <i class="bi bi-x"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Страница</label>
                        <select class="form-select" name="page_filter" onchange="this.form.submit()">
                            <option value="">Все страницы</option>
                            @foreach($pages->pluck('page_number')->unique() as $pageNum)
                                <option value="{{ $pageNum }}" {{ request('page_filter') == $pageNum ? 'selected' : '' }}>
                                    Страница {{ $pageNum }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Изображения</label>
                        <select class="form-select" name="has_images" onchange="this.form.submit()">
                            <option value="">Все страницы</option>
                            <option value="yes" {{ request('has_images') == 'yes' ? 'selected' : '' }}>С изображениями</option>
                            <option value="no" {{ request('has_images') == 'no' ? 'selected' : '' }}>Без изображений</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Применить
                            </button>
                            <a href="{{ route('admin.documents.processing.pages.list', $document->id) }}" 
                               class="btn btn-secondary">
                                <i class="bi bi-arrow-clockwise"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Таблица страниц -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-list"></i> Список страниц</h5>
        </div>
        <div class="card-body">
            @if($pages->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover" id="pagesTable">
                        <thead>
                            <tr>
                                <th width="80">№</th>
                                <th>Заголовок</th>
                                <th width="120">Слова</th>
                                <th width="120">Символы</th>
                                <th width="100">Изображения</th>
                                <th width="100">Качество</th>
                                <th width="180">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pages as $page)
                            @php
                                $hasImages = $page->has_images;
                                $imageCount = $imagesByPage[$page->page_number]['count'] ?? 0;
                            @endphp
                            <tr class="page-row" 
                                data-page-number="{{ $page->page_number }}"
                                data-has-images="{{ $hasImages ? 'yes' : 'no' }}"
                                data-word-count="{{ $page->word_count }}"
                                data-text="{{ strtolower($page->content_text ?? '') }}">
                                <td>
                                    <span class="badge bg-secondary">#{{ $page->page_number }}</span>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong>Страница {{ $page->page_number }}</strong>
                                            @if($page->section_title)
                                                <div class="text-muted small mt-1">
                                                    <i class="bi bi-tag"></i> {{ $page->section_title }}
                                                </div>
                                            @endif
                                            <div class="text-truncate small mt-1" style="max-width: 400px;">
                                                {{ Str::limit(strip_tags($page->content_text ?? ''), 120) }}
                                            </div>
                                        </div>
                                        @if($hasImages)
                                            <span class="badge bg-warning ms-2">
                                                <i class="bi bi-images"></i> {{ $imageCount }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ number_format($page->word_count) }}</span>
                                </td>
                                <td>
                                    <small>{{ number_format($page->character_count) }}</small>
                                </td>
                                <td>
                                    @if($hasImages)
                                        @if($imageCount > 0)
                                            <a href="{{ route('admin.documents.processing.view-images', $document->id) }}?page={{ $page->page_number }}" 
                                               class="badge bg-success text-decoration-none">
                                                <i class="bi bi-image"></i> {{ $imageCount }}
                                            </a>
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="bi bi-exclamation-triangle"></i> Есть
                                            </span>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary">Нет</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                            <div class="progress-bar bg-{{ $page->parsing_quality > 0.7 ? 'success' : ($page->parsing_quality > 0.4 ? 'warning' : 'danger') }}" 
                                                 style="width: {{ $page->parsing_quality * 100 }}%">
                                            </div>
                                        </div>
                                        <span class="small">{{ round($page->parsing_quality * 100) }}%</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.documents.processing.page.show', ['id' => $document->id, 'pageId' => $page->id]) }}" 
                                           class="btn btn-primary" title="Просмотр страницы">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.documents.processing.page.raw', ['id' => $document->id, 'pageId' => $page->id]) }}" 
                                           target="_blank" class="btn btn-info" title="Исходный текст">
                                            <i class="bi bi-code"></i>
                                        </a>
                                        @if($hasImages)
                                        <a href="{{ route('admin.documents.processing.view-images', $document->id) }}?page={{ $page->page_number }}" 
                                           class="btn btn-warning" title="Просмотр изображений">
                                            <i class="bi bi-images"></i>
                                        </a>
                                        @endif
                                        <button type="button" class="btn btn-secondary" 
                                                onclick="copyPageText({{ $page->id }})" 
                                                title="Копировать текст">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Пагинация -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">
                        Показано {{ $pages->firstItem() }} - {{ $pages->lastItem() }} из {{ $pages->total() }} страниц
                    </div>
                    <div>
                        {{ $pages->appends(request()->query())->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-file-text text-muted" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">Страницы не найдены</h4>
                    <p class="text-muted">
                        @if(request()->hasAny(['search', 'has_images', 'page_filter']))
                            Попробуйте изменить параметры поиска.
                        @else
                            Документ еще не был распарсен или страницы были удалены.
                        @endif
                    </p>
                    <div class="mt-3">
                        <a href="{{ route('admin.documents.processing.advanced', $document->id) }}" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Вернуться к обработке
                        </a>
                        @if(request()->hasAny(['search', 'has_images', 'page_filter']))
                            <a href="{{ route('admin.documents.processing.pages.list', $document->id) }}" 
                               class="btn btn-secondary">
                                Сбросить фильтры
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.page-row:hover {
    background-color: #f8f9fa;
    cursor: pointer;
}

.stat-card {
    text-align: center;
    padding: 15px;
    border: 1px solid #dee2e6;
    border-radius: 10px;
    background: white;
    margin-bottom: 15px;
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.stat-card h2 {
    font-weight: 700;
    margin-bottom: 5px;
    font-size: 1.8rem;
}
</style>

<script>
// Клик по строке ведет на страницу просмотра
document.querySelectorAll('.page-row').forEach(row => {
    row.addEventListener('click', function(e) {
        // Не переходим если кликнули на кнопку или ссылку
        if (!e.target.closest('.btn-group') && !e.target.closest('a')) {
            const pageLink = this.querySelector('a.btn-primary');
            if (pageLink) {
                window.location.href = pageLink.href;
            }
        }
    });
});

// Копирование текста страницы
function copyPageText(pageId) {
    const row = document.querySelector(`tr[data-page-id="${pageId}"]`);
    if (!row) return;
    
    const text = row.querySelector('.page-text-preview')?.textContent || '';
    
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Текст скопирован в буфер обмена', 'success');
        }).catch(err => {
            console.error('Ошибка копирования:', err);
            showToast('Не удалось скопировать текст', 'error');
        });
    } else {
        // Fallback для старых браузеров
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showToast('Текст скопирован в буфер обмена', 'success');
    }
}

// Всплывающее уведомление
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type} border-0`;
    toast.style.position = 'fixed';
    toast.style.bottom = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '9999';
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        document.body.removeChild(toast);
    });
}

// Поиск по таблице (клиентский)
document.getElementById('searchPages')?.addEventListener('input', function(e) {
    const searchText = this.value.toLowerCase();
    const rows = document.querySelectorAll('.page-row');
    
    rows.forEach(row => {
        const rowText = row.getAttribute('data-text');
        if (rowText.includes(searchText) || searchText === '') {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Сортировка таблицы
document.getElementById('sortPages')?.addEventListener('change', function() {
    const tbody = document.querySelector('#pagesTable tbody');
    const rows = Array.from(tbody.querySelectorAll('.page-row'));
    
    rows.sort((a, b) => {
        const aNum = parseInt(a.getAttribute('data-page-number'));
        const bNum = parseInt(b.getAttribute('data-page-number'));
        const aWords = parseInt(a.getAttribute('data-word-count'));
        const bWords = parseInt(b.getAttribute('data-word-count'));
        
        switch(this.value) {
            case 'page_number_asc': return aNum - bNum;
            case 'page_number_desc': return bNum - aNum;
            case 'word_count_asc': return aWords - bWords;
            case 'word_count_desc': return bWords - aWords;
            default: return aNum - bNum;
        }
    });
    
    // Очищаем и добавляем отсортированные строки
    rows.forEach(row => tbody.appendChild(row));
});

// Фильтрация по наличию изображений
document.getElementById('filterHasImages')?.addEventListener('change', function() {
    const rows = document.querySelectorAll('.page-row');
    
    rows.forEach(row => {
        const hasImages = row.getAttribute('data-has-images');
        
        if (this.value === 'all' ||
            (this.value === 'with_images' && hasImages === 'yes') ||
            (this.value === 'without_images' && hasImages === 'no')) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Сброс всех фильтров
function resetFilters() {
    // Сброс полей формы
    document.querySelectorAll('#filterForm input, #filterForm select').forEach(element => {
        if (element.type === 'text') element.value = '';
        if (element.tagName === 'SELECT') element.selectedIndex = 0;
    });
    
    // Перезагрузка страницы без параметров
    window.location.href = window.location.pathname;
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    // Подсветка строки при наведении
    document.querySelectorAll('.page-row').forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8f9fa';
        });
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
    
    // Автофокус на поле поиска
    if (document.querySelector('input[name="search"]') && !document.querySelector('input[name="search"]').value) {
        document.querySelector('input[name="search"]').focus();
    }
});
</script>
@endsection