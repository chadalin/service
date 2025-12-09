@extends('layouts.app')

@section('title', 'Дашборд')

@section('content')
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title">Пользователи</h5>
                <h2 class="card-text">{{ $stats['users_count'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title">Бренды</h5>
                <h2 class="card-text">{{ $stats['brands_count'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h5 class="card-title">Документы</h5>
                <h2 class="card-text">{{ $stats['documents_count'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h5 class="card-title">Поиски</h5>
                <h2 class="card-text">{{ $stats['searches_count'] }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Быстрые действия</h5>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.documents.create') }}" class="btn btn-primary me-2">
                    📎 Загрузить документ
                </a>
                <a href="{{ route('admin.documents.index') }}" class="btn btn-outline-secondary">
                    📋 Все документы
                </a>
            </div>
        </div>
    </div>
</div>
@endsection