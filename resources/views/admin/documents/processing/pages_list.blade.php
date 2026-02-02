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
        <div class="col-md-4">
            <div class="stat-card">
                <h2 class="text-primary">{{ $pages->total() }}</h2>
                <small>Всего страниц</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                @php
                    $pagesWithImages = $pages->where('has_images', true)->count();
                @endphp
                <h2 class="text-success">{{ $pagesWithImages }}</h2>
                <small>Страниц с изображениями</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <h2 class="text-info">{{ number_format($pages->sum('word_count')) }}</h2>
                <small>Всего слов</small>
            </div>
        </div>
    </div>

    <!-- Поиск и фильтры -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchPages" placeholder="Поиск по тексту..." onkeyup="searchPages()">
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="filterHasImages" onchange="filterPages()">
                        <option value="all">Все страницы</option>
                        <option value="with_images">Только с изображениями</option>
                        <option value="without_images">Без изображений</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="sortPages" onchange="sortPages()">
                        <option value="page_number_asc">По номеру ↑</option>
                        <option value="page_number_desc">По номеру ↓</option>
                        <option value="word_count_asc">По словам ↑</option>
                        <option value="word_count_desc">По словам ↓</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" onclick="resetFilters()">
                        <i class="bi bi-arrow-clockwise"></i> Сброс
                    </button>
                </div>
            </div>
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
                                <th width="150">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pages as $page)
                            <tr class="page-row" 
                                data-page-number="{{ $page->page_number }}"
                                data-has-images="{{ $page->has_images ? 'yes' : 'no' }}"
                                data-word-count="{{ $page->word_count }}"
                                data-text="{{ strtolower($page->content_text ?? '') }}">
                                <td>
                                    <span class="badge bg-secondary">#{{ $page->page_number }}</span>
                                </td>
                                <td>
                                    <strong>Страница {{ $page->page_number }}</strong>
                                    @if($page->section_title)
                                        <div class="text-muted small">{{ $page->section_title }}</div>
                                    @endif
                                    <div class="text-truncate small mt-1" style="max-width: 300px;">
                                        {{ Str::limit(strip_tags($page->content_text ?? ''), 100) }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ number_format($page->word_count) }}</span>
                                </td>
                                <td>
                                    <small>{{ number_format($page->character_count) }}</small>
                                </td>
                                <td>
                                    @if($page->has_images)
                                        <span class="badge bg-success">
                                            <i class="bi bi-image"></i> Есть
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">Нет</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-{{ $page->parsing_quality > 0.7 ? 'success' : ($page->parsing_quality > 0.4 ? 'warning' : 'danger') }}" 
                                             style="width: {{ $page->parsing_quality * 100 }}%">
                                        </div>
                                    </div>
                                    <small class="d-block text-center">{{ round($page->parsing_quality * 100) }}%</small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.documents.processing.page.show', ['id' => $document->id, 'pageId' => $page->id]) }}" 
                                           class="btn btn-primary" title="Просмотр">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.documents.processing.page.raw', ['id' => $document->id, 'pageId' => $page->id]) }}" 
                                           target="_blank" class="btn btn-info" title="Исходный текст">
                                            <i class="bi bi-code"></i>
                                        </a>
                                        @if($page->has_images)
                                        <a href="{{ route('admin.documents.processing.view-images', $document->id) }}?page={{ $page->page_number }}" 
                                           class="btn btn-warning" title="Изображения">
                                            <i class="bi bi-images"></i>
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
                <div class="d-flex justify-content-center mt-3">
                    {{ $pages->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-file-text text-muted" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">Страницы не найдены</h4>
                    <p class="text-muted">Документ еще не был распарсен или страницы были удалены.</p>
                    <a href="{{ route('admin.documents.processing.advanced', $document->id) }}" class="btn btn-primary">
                        <i class="bi bi-arrow-left"></i> Вернуться к обработке
                    </a>
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
</style>

<script>
function searchPages() {
    const searchText = document.getElementById('searchPages').value.toLowerCase();
    const rows = document.querySelectorAll('.page-row');
    
    rows.forEach(row => {
        const rowText = row.getAttribute('data-text');
        if (rowText.includes(searchText) || searchText === '') {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function filterPages() {
    const filterValue = document.getElementById('filterHasImages').value;
    const rows = document.querySelectorAll('.page-row');
    
    rows.forEach(row => {
        const hasImages = row.getAttribute('data-has-images');
        
        if (filterValue === 'all' ||
            (filterValue === 'with_images' && hasImages === 'yes') ||
            (filterValue === 'without_images' && hasImages === 'no')) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function sortPages() {
    const sortValue = document.getElementById('sortPages').value;
    const table = document.getElementById('pagesTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('.page-row'));
    
    rows.sort((a, b) => {
        const aNum = parseInt(a.getAttribute('data-page-number'));
        const bNum = parseInt(b.getAttribute('data-page-number'));
        const aWords = parseInt(a.getAttribute('data-word-count'));
        const bWords = parseInt(b.getAttribute('data-word-count'));
        
        switch(sortValue) {
            case 'page_number_asc':
                return aNum - bNum;
            case 'page_number_desc':
                return bNum - aNum;
            case 'word_count_asc':
                return aWords - bWords;
            case 'word_count_desc':
                return bWords - aWords;
            default:
                return aNum - bNum;
        }
    });
    
    // Очищаем и добавляем отсортированные строки
    rows.forEach(row => tbody.appendChild(row));
}

function resetFilters() {
    document.getElementById('searchPages').value = '';
    document.getElementById('filterHasImages').value = 'all';
    document.getElementById('sortPages').value = 'page_number_asc';
    
    const rows = document.querySelectorAll('.page-row');
    rows.forEach(row => row.style.display = '');
    
    // Возвращаем исходную сортировку
    sortPages();
}

// Клик по строке ведет на страницу просмотра
document.querySelectorAll('.page-row').forEach(row => {
    row.addEventListener('click', function(e) {
        // Не переходим если кликнули на кнопку
        if (!e.target.closest('.btn-group')) {
            const pageId = this.querySelector('a.btn-primary').href;
            window.location.href = pageId;
        }
    });
});

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    sortPages();
});
</script>
@endsection