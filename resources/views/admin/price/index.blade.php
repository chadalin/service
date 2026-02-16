@extends('layouts.app')

@section('title', 'Прайс-лист')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-currency-dollar me-2"></i> Прайс-лист
                    </h5>
                    <div>
                        <a href="{{ route('admin.price.import.select') }}" class="btn btn-light btn-sm">
                            <i class="bi bi-upload me-1"></i> Импорт
                        </a>
                        <a href="{{ route('admin.price.import.template') }}" class="btn btn-outline-light btn-sm ms-2">
                            <i class="bi bi-download me-1"></i> Шаблон
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Фильтры -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" action="{{ route('admin.price.index') }}">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="brand_id" class="form-label">Бренд</label>
                                        <select name="brand_id" id="brand_id" class="form-select">
                                            <option value="">Все бренды</option>
                                            @foreach($brands as $brand)
                                                <option value="{{ $brand->id }}" 
                                                        {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                                                    {{ $brand->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label for="search" class="form-label">Поиск</label>
                                        <input type="text" 
                                               name="search" 
                                               id="search" 
                                               class="form-control" 
                                               placeholder="Поиск по SKU, названию, описанию..."
                                               value="{{ request('search') }}">
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <div class="d-grid gap-2 w-100">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-search me-1"></i> Найти
                                            </button>
                                            <a href="{{ route('admin.price.index') }}" class="btn btn-outline-secondary">
                                                Сбросить
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Результаты -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'sku', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}">
                                            SKU
                                            @if(request('sort') == 'sku')
                                                <i class="bi bi-arrow-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Название</th>
                                    <th>Бренд</th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'quantity', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}">
                                            Кол-во
                                            @if(request('sort') == 'quantity')
                                                <i class="bi bi-arrow-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'price', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}">
                                            Цена
                                            @if(request('sort') == 'price')
                                                <i class="bi bi-arrow-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Совпадения</th>
                                    <th>Дата добавления</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($priceItems as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->sku }}</strong>
                                            @if($item->catalog_brand)
                                                <br>
                                                <small class="text-muted">{{ $item->catalog_brand }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-bold">{{ Str::limit($item->name, 50) }}</div>
                                            @if($item->description)
                                                <div class="small text-muted">
                                                    {{ Str::limit($item->description, 30) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->brand)
                                                {{ $item->brand->name }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $item->quantity > 0 ? 'success' : 'secondary' }}">
                                                {{ $item->quantity }} {{ $item->unit ?? 'шт' }}
                                            </span>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($item->price, 2, '.', ' ') }} ₽</strong>
                                        </td>
                                        <td>
                                            @if($item->matchedSymptoms->count() > 0)
                                                <span class="badge bg-info" 
                                                      data-bs-toggle="tooltip" 
                                                      title="{{ $item->matchedSymptoms->count() }} совпадений">
                                                    <i class="bi bi-link-45deg me-1"></i>
                                                    {{ $item->matchedSymptoms->count() }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">Нет</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $item->created_at->format('d.m.Y') }}
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.price.show', $item) }}" 
                                                   class="btn btn-outline-info"
                                                   title="Просмотр">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-outline-warning match-symptoms-btn"
                                                        data-id="{{ $item->id }}"
                                                        title="Найти совпадения">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                                <form action="{{ route('admin.price.destroy', $item) }}" 
                                                      method="POST"
                                                      onsubmit="return confirm('Удалить этот товар?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-outline-danger"
                                                            title="Удалить">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="bi bi-inbox display-1 text-muted"></i>
                                            <p class="mt-3 text-muted">Прайс-лист пуст</p>
                                            <a href="{{ route('admin.price.import.select') }}" class="btn btn-primary">
                                                <i class="bi bi-upload me-2"></i> Импортировать прайс-лист
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                     @if(session('import_results'))
    <div class="alert alert-success">
        <h4>Импорт завершен:</h4>
        <ul>
            <li>Обработано: {{ session('import_results')['items_processed'] }}</li>
            <li>Создано новых: {{ session('import_results')['items_created'] }}</li>
            <li>Обновлено: {{ session('import_results')['items_updated'] }}</li>
            @if(isset(session('import_results')['detailed_stats']))
                <li>Детали обновлений:
                    <ul>
                        <li>Обновлено цен: {{ session('import_results')['detailed_stats']['price_updated'] }}</li>
                        <li>Обновлено количеств: {{ session('import_results')['detailed_stats']['quantity_updated'] }}</li>
                        <li>Обновлено описаний: {{ session('import_results')['detailed_stats']['description_updated'] }}</li>
                        <li>Обновлено единиц: {{ session('import_results')['detailed_stats']['unit_updated'] }}</li>
                        <li>Обновлено брендов: {{ session('import_results')['detailed_stats']['catalog_brand_updated'] }}</li>
                        <li>Без изменений: {{ session('import_results')['detailed_stats']['no_changes'] }}</li>
                    </ul>
                </li>
            @endif
            <li>Пропущено: {{ session('import_results')['items_skipped'] }}</li>
            @if(session('import_results')['symptoms_matched'] > 0)
                <li>Сопоставлено с симптомами: {{ session('import_results')['symptoms_matched'] }}</li>
            @endif
        </ul>
        
        @if(!empty(session('import_results')['errors']))
            <div class="alert alert-warning">
                <h5>Ошибки ({{ count(session('import_results')['errors']) }}):</h5>
                <ul>
                    @foreach(session('import_results')['errors'] as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
                    <!-- Пагинация -->
                    @if($priceItems->hasPages())
                        <div class="mt-4">
                            {{ $priceItems->withQueryString()->links() }}
                        </div>
                    @endif
                    
                    <!-- Статистика -->
                    <div class="mt-4">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Статистика:</strong>
                            Всего позиций: {{ $priceItems->total() }} | 
                            С совпадениями: {{ \App\Models\PriceItem::has('matchedSymptoms')->count() }} | 
                            В наличии: {{ \App\Models\PriceItem::where('quantity', '>', 0)->count() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Обработка кнопки поиска совпадений
    document.querySelectorAll('.match-symptoms-btn').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.dataset.id;
            const button = this;
            
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            
            fetch(`/admin/price/${itemId}/match-symptoms`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    // Обновляем страницу через 2 секунды
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showToast(data.message, 'error');
                    button.disabled = false;
                    button.innerHTML = '<i class="bi bi-arrow-repeat"></i>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Ошибка при поиске совпадений', 'error');
                button.disabled = false;
                button.innerHTML = '<i class="bi bi-arrow-repeat"></i>';
            });
        });
    });
    
    function showToast(message, type = 'info') {
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-bg-${type}" 
                 role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                            data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 3000
        });
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', function() {
            this.remove();
        });
    }
});
</script>
@endpush