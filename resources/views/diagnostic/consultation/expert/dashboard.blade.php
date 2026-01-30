@extends('layouts.app')

@section('title', 'Панель эксперта')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-chat-dots me-2"></i> Панель эксперта
                    </h5>
                </div>
                <div class="card-body text-center py-5">
                    <i class="bi bi-tools display-1 text-muted"></i>
                    <h4 class="mt-4 text-muted">Панель эксперта в разработке</h4>
                    <p class="text-muted">
                        Этот раздел находится в разработке и скоро будет доступен.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('admin.consultations.index') }}" class="btn btn-primary">
                            <i class="bi bi-chat-square-dots me-2"></i> Перейти к консультациям
                        </a>
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary ms-2">
                            <i class="bi bi-arrow-left me-2"></i> Назад в дашборд
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection