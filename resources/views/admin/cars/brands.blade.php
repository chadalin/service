@extends('layouts.app')

@section('title', 'Бренды автомобилей')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
    <h1 class="h2">Бренды автомобилей</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('admin.cars.import') }}" class="btn btn-primary me-2">Импорт данных</a>
        <a href="{{ route('admin.cars.models') }}" class="btn btn-outline-secondary">Все модели</a>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Кириллица</th>
                <th>Страна</th>
                <th>Годы</th>
                <th>Популярный</th>
                <th>Моделей</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach($brands as $brand)
            <tr>
                <td>{{ $brand->id }}</td>
                <td>
                    <strong>{{ $brand->name }}</strong>
                </td>
                <td>{{ $brand->name_cyrillic }}</td>
                <td>{{ $brand->country }}</td>
                <td>{{ $brand->year_from }} - {{ $brand->year_to }}</td>
                <td>
                    @if($brand->is_popular)
                        <span class="badge bg-success">Да</span>
                    @else
                        <span class="badge bg-secondary">Нет</span>
                    @endif
                </td>
                <td>{{ $brand->car_models_count }}</td>
                <td>
                    <a href="{{ route('admin.cars.models', ['brand_id' => $brand->id]) }}" 
                       class="btn btn-sm btn-outline-primary">
                        Модели
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    {{ $brands->links() }}
</div>
@endsection