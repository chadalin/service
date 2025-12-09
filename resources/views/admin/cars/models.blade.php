@extends('layouts.app')

@section('title', 'Модели автомобилей')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
    <h1 class="h2">Модели автомобилей</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('admin.cars.brands') }}" class="btn btn-outline-secondary me-2">Все бренды</a>
        <a href="{{ route('admin.cars.import') }}" class="btn btn-primary">Импорт данных</a>
    </div>
</div>

<!-- Фильтр по бренду -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <label for="brand_id" class="form-label">Фильтр по бренду</label>
                <select name="brand_id" id="brand_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Все бренды</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" {{ $brandId == $brand->id ? 'selected' : '' }}>
                            {{ $brand->name }} ({{ $brand->name_cyrillic }})
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>ID</th>
                <th>Бренд</th>
                <th>Модель</th>
                <th>Кириллица</th>
                <th>Класс</th>
                <th>Годы</th>
            </tr>
        </thead>
        <tbody>
            @foreach($models as $model)
            <tr>
                <td>{{ $model->model_id }}</td>
                <td>
                    <strong>{{ $model->brand->name }}</strong>
                </td>
                <td>{{ $model->name }}</td>
                <td>{{ $model->name_cyrillic }}</td>
                <td>{{ $model->class }}</td>
                <td>{{ $model->year_from }} - {{ $model->year_to }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    {{ $models->links() }}
</div>
@endsection