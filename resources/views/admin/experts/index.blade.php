@extends('layouts.app')

@section('title', 'Управление экспертами')

@php
    use App\Models\User;
@endphp

@section('content')
<div class="container-fluid">
    <!-- Заголовок и кнопки -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Эксперты</h1>
                    <p class="text-muted mb-0">Управление экспертами системы</p>
                </div>
                <div>
                    <a href="{{ route('admin.experts.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-2"></i> Добавить эксперта
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтры и поиск -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.experts.index') }}" class="row g-3">
                        <div class="col-md-6">
                            <label for="search" class="form-label">Поиск</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="search" 
                                   name="search" 
                                   value="{{ request('search') }}"
                                   placeholder="Имя, email или компания">
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Статус</label>
                            <select class="form-select" id="status" name="status">
                                <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>Все</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Активные</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Неактивные</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search me-2"></i> Найти
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Всего экспертов</h6>
                            <h3 class="mb-0">{{ $experts->total() }}</h3>
                        </div>
                        <div class="bg-primary rounded-circle p-3">
                            <i class="bi bi-people text-white fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Активные</h6>
                            <h3 class="mb-0">{{ $experts->where('status', 'active')->count() }}</h3>
                        </div>
                        <div class="bg-success rounded-circle p-3">
                            <i class="bi bi-check-circle text-white fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Всего консультаций</h6>
                            <h3 class="mb-0">{{ $experts->sum('total_consultations') }}</h3>
                        </div>
                        <div class="bg-info rounded-circle p-3">
                            <i class="bi bi-chat-dots text-white fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Средний рейтинг</h6>
                            <h3 class="mb-0">
                                @php
                                    $avgRating = $experts->avg('consultations_avg_rating');
                                @endphp
                                {{ $avgRating ? number_format($avgRating, 1) : '0.0' }}
                            </h3>
                        </div>
                        <div class="bg-warning rounded-circle p-3">
                            <i class="bi bi-star text-white fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Список экспертов -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if($experts->isEmpty())
                        <div class="text-center py-5">
                            <i class="bi bi-people display-1 text-muted"></i>
                            <h4 class="mt-4">Эксперты не найдены</h4>
                            <p class="text-muted">Добавьте первого эксперта</p>
                            <a href="{{ route('admin.experts.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-lg me-2"></i> Добавить эксперта
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Эксперт</th>
                                        <th>Контакт</th>
                                        <th>Статистика</th>
                                        <th>Статус</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($experts as $expert)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar me-3">
                                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                            {{ strtoupper(substr($expert->name, 0, 1)) }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1">{{ $expert->name }}</h6>
                                                        @if($expert->company_name)
                                                            <small class="text-muted">{{ $expert->company_name }}</small>
                                                        @endif
                                                        @if($expert->expert_data['experience_years'] ?? false)
                                                            <div class="text-muted">
                                                                <i class="bi bi-clock-history me-1"></i>
                                                                {{ $expert->expert_data['experience_years'] }} лет опыта
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="mb-1">
                                                    <i class="bi bi-envelope me-2 text-muted"></i>
                                                    {{ $expert->email }}
                                                </div>
                                                @if($expert->phone)
                                                    <div>
                                                        <i class="bi bi-telephone me-2 text-muted"></i>
                                                        {{ $expert->phone }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="mb-1">
                                                    <span class="badge bg-info rounded-pill">
                                                        {{ $expert->total_consultations }} конс.
                                                    </span>
                                                    <span class="badge bg-success rounded-pill ms-1">
                                                        {{ $expert->completed_consultations }} зав.
                                                    </span>
                                                    <span class="badge bg-warning rounded-pill ms-1">
                                                        {{ $expert->in_progress_consultations }} в раб.
                                                    </span>
                                                </div>
                                                <div class="mb-1">
                                                    @if($expert->consultations_avg_rating)
                                                        <span class="badge bg-primary">
                                                            Рейтинг: {{ number_format($expert->consultations_avg_rating, 1) }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary">Нет рейтинга</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($expert->status === 'active')
                                                    <span class="badge bg-success">Активен</span>
                                                @else
                                                    <span class="badge bg-danger">Неактивен</span>
                                                @endif
                                                <br>
                                                <small class="text-muted">
                                                    {{ $expert->created_at->format('d.m.Y') }}
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.experts.show', $expert->id) }}" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Просмотр">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.experts.edit', $expert->id) }}" 
                                                       class="btn btn-sm btn-outline-secondary" 
                                                       title="Редактировать">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form action="{{ route('admin.experts.toggle-status', $expert->id) }}" 
                                                          method="POST" 
                                                          class="d-inline">
                                                        @csrf
                                                        <button type="submit" 
                                                                class="btn btn-sm btn-outline-warning" 
                                                                title="{{ $expert->status === 'active' ? 'Деактивировать' : 'Активировать' }}">
                                                            <i class="bi bi-power"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.experts.destroy', $expert->id) }}" 
                                                          method="POST" 
                                                          class="d-inline"
                                                          onsubmit="return confirm('Вы уверены, что хотите удалить этого эксперта?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="btn btn-sm btn-outline-danger" 
                                                                title="Удалить">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Пагинация -->
                        <div class="mt-4">
                            {{ $experts->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .avatar {
        flex-shrink: 0;
    }
    .table td {
        vertical-align: middle;
    }
    .badge {
        font-size: 0.85em;
    }
</style>
@endpush