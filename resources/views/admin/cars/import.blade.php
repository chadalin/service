@extends('layouts.app')

@section('title', 'Импорт данных автомобилей')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Импорт данных автомобилей</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6>Информация об импорте:</h6>
                    <ul class="mb-0">
                        <li>Данные будут загружены из открытой базы автомобилей</li>
                        <li>Импортируется информация о брендах и моделях</li>
                        <li>Процесс может занять несколько минут</li>
                    </ul>
                </div>

                <form method="POST" action="{{ route('admin.cars.import') }}" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label">URL CSV файла</label>
                        <input type="url" class="form-control" name="csv_url" 
                               value="https://raw.githubusercontent.com/blanzh/carsBase/master/cars.csv"
                               placeholder="https://example.com/cars.csv">
                        <div class="form-text">Оставьте значение по умолчанию или укажите свой URL</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Или загрузите CSV файл</label>
                        <input type="file" class="form-control" name="csv_file" accept=".csv,.txt">
                        <div class="form-text">Формат: ID_MARK,Марка,Марка кириллица,Популярная марка,Страна,Год марки от,Год марки до,MODEL_ID,Модель,Модель кириллица,Класс,Год модели от,Год модели до</div>
                    </div>

                    <button type="submit" class="btn btn-primary">Запустить импорт</button>
                    <a href="{{ route('admin.cars.brands') }}" class="btn btn-secondary">Отмена</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection