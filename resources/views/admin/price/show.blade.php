@extends('layouts.app')

@section('title', 'Карточка запчасти: ' . ($priceItem->name ?? 'Не найдена'))

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8">
            <!-- Основная информация о запчасти -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-box-seam me-2"></i> Информация о запчасти
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Левая колонка - основная информация -->
                        <div class="col-md-6">
                            <div class="mb-4">
                                <h6 class="text-muted mb-2">Основная информация</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless">
                                        <tbody>
                                            <tr>
                                                <td class="text-muted" style="width: 40%;">Артикул (SKU):</td>
                                                <td>
                                                    <span class="badge bg-dark fs-6">{{ $priceItem->sku ?? '—' }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Название:</td>
                                                <td>
                                                    <h5 class="mb-0">{{ $priceItem->name ?? '—' }}</h5>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Каталожный бренд:</td>
                                                <td>
                                                    @if(!empty($priceItem->catalog_brand))
                                                        <span class="badge bg-info">{{ $priceItem->catalog_brand }}</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Привязанный бренд:</td>
                                                <td>
                                                    @if($priceItem->brand && $priceItem->brand->name)
                                                        <span class="badge bg-primary">
                                                            {{ $priceItem->brand->name }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Правая колонка - цена и количество -->
                        <div class="col-md-6">
                            <div class="mb-4">
                                <h6 class="text-muted mb-2">Склад и цена</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless">
                                        <tbody>
                                            <tr>
                                                <td class="text-muted" style="width: 40%;">Количество:</td>
                                                <td>
                                                    <span class="badge bg-{{ ($priceItem->quantity ?? 0) > 0 ? 'success' : 'secondary' }} fs-6">
                                                        {{ $priceItem->quantity ?? 0 }} {{ $priceItem->unit ?: 'шт' }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Цена:</td>
                                                <td>
                                                    <h3 class="text-success mb-0">
                                                        {{ number_format($priceItem->price ?? 0, 2, '.', ' ') }} ₽
                                                    </h3>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Дата добавления:</td>
                                                <td>
                                                    <span class="text-muted">
                                                        @if($priceItem->created_at)
                                                            {{ $priceItem->created_at->format('d.m.Y H:i') }}
                                                        @else
                                                            —
                                                        @endif
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Последнее обновление:</td>
                                                <td>
                                                    <span class="text-muted">
                                                        @if($priceItem->updated_at)
                                                            {{ $priceItem->updated_at->format('d.m.Y H:i') }}
                                                        @else
                                                            —
                                                        @endif
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Описание -->
                    @if(!empty($priceItem->description))
                        <div class="mt-4 pt-4 border-top">
                            <h6 class="text-muted mb-3">Описание</h6>
                            <div class="p-3 bg-light rounded">
                                {{ $priceItem->description }}
                            </div>
                        </div>
                    @endif
                    
                    <!-- Совместимость -->
                    @if($priceItem->compatibility && is_array($priceItem->compatibility) && count($priceItem->compatibility) > 0)
                        <div class="mt-4 pt-4 border-top">
                            <h6 class="text-muted mb-3">Совместимость</h6>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($priceItem->compatibility as $item)
                                    @if(!empty($item))
                                        <span class="badge bg-secondary">{{ $item }}</span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('admin.price.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i> Назад к списку
                            </a>
                        </div>
                        <div class="btn-group">
                            <button type="button" 
                                    class="btn btn-outline-warning match-symptoms-btn"
                                    data-id="{{ $priceItem->id ?? '' }}">
                                <i class="bi bi-link-45deg me-2"></i> Найти совпадения
                            </button>
                            @if($priceItem->id ?? false)
                                <form action="{{ route('admin.price.destroy', $priceItem) }}" 
                                      method="POST"
                                      onsubmit="return confirm('Удалить эту запчасть из прайс-листа?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger">
                                        <i class="bi bi-trash me-2"></i> Удалить
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Связанные симптомы диагностики -->
            @php
                $matchedSymptoms = $priceItem->matchedSymptoms ?? collect();
                $symptomsCount = $matchedSymptoms->count();
            @endphp
            
            @if($symptomsCount > 0)
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-link-45deg me-2"></i> Связанные симптомы диагностики
                            <span class="badge bg-light text-dark ms-2">{{ $symptomsCount }}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            @foreach($matchedSymptoms->sortByDesc('pivot.match_score') as $symptom)
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">
                                                @if($symptom->id ?? false)
                                                    <a href="{{ route('admin.diagnostic.symptoms.show', $symptom) }}" 
                                                       class="text-decoration-none">
                                                        {{ $symptom->name ?? 'Без названия' }}
                                                    </a>
                                                @else
                                                    {{ $symptom->name ?? 'Без названия' }}
                                                @endif
                                            </h6>
                                            @if(!empty($symptom->description))
                                                <p class="mb-1 text-muted small">{{ Str::limit($symptom->description, 150) }}</p>
                                            @endif
                                            <div class="mt-2">
                                                @php
                                                    $matchType = $symptom->pivot->match_type ?? 'weak';
                                                    $matchScore = $symptom->pivot->match_score ?? 0;
                                                    
                                                    $typeClasses = [
                                                        'exact' => ['label' => 'Точное совпадение', 'color' => 'success'],
                                                        'strong' => ['label' => 'Сильное совпадение', 'color' => 'info'],
                                                        'medium' => ['label' => 'Среднее совпадение', 'color' => 'warning'],
                                                        'weak' => ['label' => 'Слабое совпадение', 'color' => 'secondary']
                                                    ];
                                                    
                                                    $typeInfo = $typeClasses[$matchType] ?? $typeClasses['weak'];
                                                @endphp
                                                
                                                <span class="badge bg-{{ $typeInfo['color'] }}">
                                                    {{ $typeInfo['label'] }}
                                                </span>
                                                <span class="badge bg-light text-dark ms-2">
                                                    Сходство: {{ number_format($matchScore * 100, 1) }}%
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-link-45deg me-2"></i> Связанные симптомы диагностики
                        </h5>
                    </div>
                    <div class="card-body text-center py-5">
                        <i class="bi bi-unlink display-1 text-muted"></i>
                        <p class="mt-3 text-muted">Совпадения с симптомами диагностики не найдены</p>
                        @if($priceItem->id ?? false)
                            <button type="button" 
                                    class="btn btn-outline-primary match-symptoms-btn"
                                    data-id="{{ $priceItem->id }}">
                                <i class="bi bi-search me-2"></i> Найти совпадения
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Боковая панель - статистика и быстрые действия -->
        <div class="col-lg-4">
            <!-- Статистика совпадений -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-bar-chart me-2"></i> Статистика совпадений
                    </h6>
                </div>
                <div class="card-body">
                    @php
                        // Безопасная группировка по типу совпадения
                        $matchesByType = collect();
                        if ($priceItem->matchedSymptoms) {
                            $matchesByType = $priceItem->matchedSymptoms->groupBy('pivot.match_type');
                        }
                        
                        $matchTypes = [
                            'exact' => ['name' => 'Точные', 'color' => 'success', 'icon' => 'bi-check-circle'],
                            'strong' => ['name' => 'Сильные', 'color' => 'info', 'icon' => 'bi-check-square'],
                            'medium' => ['name' => 'Средние', 'color' => 'warning', 'icon' => 'bi-dash-circle'],
                            'weak' => ['name' => 'Слабые', 'color' => 'secondary', 'icon' => 'bi-dot']
                        ];
                        
                        // Счетчики по типам
                        $exactCount = $matchesByType->has('exact') ? $matchesByType->get('exact')->count() : 0;
                        $strongCount = $matchesByType->has('strong') ? $matchesByType->get('strong')->count() : 0;
                        $mediumCount = $matchesByType->has('medium') ? $matchesByType->get('medium')->count() : 0;
                        $weakCount = $matchesByType->has('weak') ? $matchesByType->get('weak')->count() : 0;
                        
                        $totalMatches = $exactCount + $strongCount + $mediumCount + $weakCount;
                        
                        // Проценты
                        $exactPercent = $totalMatches > 0 ? ($exactCount / $totalMatches * 100) : 0;
                        $strongPercent = $totalMatches > 0 ? ($strongCount / $totalMatches * 100) : 0;
                        $mediumPercent = $totalMatches > 0 ? ($mediumCount / $totalMatches * 100) : 0;
                        $weakPercent = $totalMatches > 0 ? ($weakCount / $totalMatches * 100) : 0;
                    @endphp
                    
                    @foreach($matchTypes as $typeKey => $typeInfo)
                        @php
                            $count = 0;
                            switch($typeKey) {
                                case 'exact': $count = $exactCount; break;
                                case 'strong': $count = $strongCount; break;
                                case 'medium': $count = $mediumCount; break;
                                case 'weak': $count = $weakCount; break;
                            }
                        @endphp
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <i class="{{ $typeInfo['icon'] }} me-2 text-{{ $typeInfo['color'] }}"></i>
                                <span class="text-muted">{{ $typeInfo['name'] }}:</span>
                            </div>
                            <span class="badge bg-{{ $typeInfo['color'] }}">
                                {{ $count }}
                            </span>
                        </div>
                    @endforeach
                    
                    @if($totalMatches > 0)
                        <div class="progress mt-3" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: {{ $exactPercent }}%" 
                                 title="Точные совпадения: {{ number_format($exactPercent, 1) }}%"></div>
                            <div class="progress-bar bg-info" style="width: {{ $strongPercent }}%" 
                                 title="Сильные совпадения: {{ number_format($strongPercent, 1) }}%"></div>
                            <div class="progress-bar bg-warning" style="width: {{ $mediumPercent }}%" 
                                 title="Средние совпадения: {{ number_format($mediumPercent, 1) }}%"></div>
                            <div class="progress-bar bg-secondary" style="width: {{ $weakPercent }}%" 
                                 title="Слабые совпадения: {{ number_format($weakPercent, 1) }}%"></div>
                        </div>
                    @endif
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">Всего совпадений: {{ $totalMatches }}</small>
                    </div>
                </div>
            </div>
            
            <!-- Быстрые действия -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-lightning-charge me-2"></i> Быстрые действия
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($priceItem->sku ?? false)
                            <a href="{{ route('admin.price.index', ['search' => $priceItem->sku]) }}" 
                               class="btn btn-outline-primary">
                                <i class="bi bi-search me-2"></i> Найти похожие по SKU
                            </a>
                        @endif
                        
                        @if($priceItem->name ?? false)
                            <a href="{{ route('admin.price.index', ['search' => $priceItem->name]) }}" 
                               class="btn btn-outline-secondary">
                                <i class="bi bi-search me-2"></i> Найти по названию
                            </a>
                        @endif
                        
                        @if($priceItem->brand_id ?? false)
                            <a href="{{ route('admin.price.index', ['brand_id' => $priceItem->brand_id]) }}" 
                               class="btn btn-outline-info">
                                <i class="bi bi-tag me-2"></i> Все запчасти бренда
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Информация о системе -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i> Информация
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">ID:</span>
                            <span class="fw-bold">{{ $priceItem->id ?? '—' }}</span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Добавил:</span>
                            <span>
                                @if($priceItem->created_by ?? false)
                                    ID: {{ $priceItem->created_by }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Обновил:</span>
                            <span>
                                @if($priceItem->updated_by ?? false)
                                    ID: {{ $priceItem->updated_by }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </span>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Статус:</span>
                            <span>
                                @if($priceItem->deleted_at ?? false)
                                    <span class="badge bg-danger">Удалена</span>
                                @else
                                    <span class="badge bg-success">Активна</span>
                                @endif
                            </span>
                        </div>
                    </div>
                    
                    <hr class="my-3">
                    
                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-lightbulb me-2"></i>
                        <strong>Совет:</strong> Для лучшего поиска совпадений убедитесь, что название запчасти содержит ключевые слова, которые могут встречаться в описании симптомов.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card-header h5 {
        display: flex;
        align-items: center;
    }
    
    .list-group-item:hover {
        background-color: #f8f9fa;
        transform: translateX(2px);
        transition: all 0.2s ease;
    }
    
    .badge {
        font-size: 0.85em;
        padding: 0.35em 0.65em;
    }
    
    .progress {
        border-radius: 10px;
    }
    
    .progress-bar {
        border-radius: 10px;
    }
    
    .table-borderless td {
        padding: 0.5rem 0;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработка кнопки поиска совпадений
    document.querySelectorAll('.match-symptoms-btn').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.dataset.id;
            
            if (!itemId) {
                showToast('Ошибка: ID запчасти не найден', 'error');
                return;
            }
            
            const button = this;
            
            // Сохраняем оригинальный текст
            const originalText = button.innerHTML;
            
            // Показываем спиннер
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Поиск...';
            
            fetch(`/admin/price/${itemId}/match-symptoms`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    // Обновляем страницу через 1.5 секунды
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast(data.message || 'Ошибка при поиске совпадений', 'error');
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Ошибка при поиске совпадений: ' + error.message, 'error');
                button.disabled = false;
                button.innerHTML = originalText;
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