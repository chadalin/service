@extends('layouts.app')

@section('title', 'Импорт симптомов и правил')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-upload me-2"></i> 
                        @if(request()->has('step') && request('step') == '2')
                            Шаг 2: Загрузка CSV файла
                        @else
                            Импорт симптомов и правил диагностики
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @if(request()->has('step') && request('step') == '2')
                        <!-- Шаг 2: Загрузка файла для выбранного автомобиля -->
                        @include('admin.diagnostic.partials.import-step2')
                    @else
                        <!-- Шаг 1: Выбор метода импорта -->
                        @include('admin.diagnostic.partials.import-step1')
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection