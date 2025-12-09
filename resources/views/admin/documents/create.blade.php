@extends('layouts.app')

@section('title', 'Загрузка документа')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Загрузка нового документа</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.documents.store') }}" enctype="multipart/form-data" id="documentForm">
                    @csrf
                    <div class="mb-3">
                        <label for="title" class="form-label">Название документа</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" 
                               id="title" name="title" value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="brand_id" class="form-label">Бренд автомобиля</label>
                                <select class="form-select @error('brand_id') is-invalid @enderror" 
                                        id="brand_id" name="brand_id" required onchange="loadModels()">
                                    <option value="">Выберите бренд</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                                            {{ $brand->name }} ({{ $brand->name_cyrillic }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('brand_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="car_model_id" class="form-label">Модель автомобиля</label>
                                <select class="form-select @error('car_model_id') is-invalid @enderror" 
                                        id="car_model_id" name="car_model_id" required>
                                    <option value="">Сначала выберите бренд</option>
                                </select>
                                @error('car_model_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Категория ремонта</label>
                                <select class="form-select @error('category_id') is-invalid @enderror" 
                                        id="category_id" name="category_id" required>
                                    <option value="">Выберите категорию</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="document" class="form-label">Файл документа</label>
                                <input type="file" class="form-control @error('document') is-invalid @enderror" 
                                       id="document" name="document" accept=".pdf,.doc,.docx,.txt" required>
                                <div class="form-text">Поддерживаемые форматы: PDF, DOC, DOCX, TXT. Макс. размер: 10MB</div>
                                @error('document')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Загрузить документ</button>
                    <a href="{{ route('admin.documents.index') }}" class="btn btn-secondary">Отмена</a>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Простой JavaScript прямо в шаблоне -->
<script>
// Данные моделей (все загружаем сразу)
const modelsData = {!! json_encode($brands->mapWithKeys(function($brand) {
    return [$brand->id => $brand->carModels];
})) !!};

console.log('Models data loaded:', modelsData);

function loadModels() {
    const brandSelect = document.getElementById('brand_id');
    const modelSelect = document.getElementById('car_model_id');
    const brandId = brandSelect.value;
    
    console.log('Brand selected:', brandId);
    
    if (brandId && modelsData[brandId]) {
        modelSelect.disabled = false;
        modelSelect.innerHTML = '<option value="">Выберите модель</option>';
        
        const models = modelsData[brandId];
        console.log('Models for brand:', models);
        
        models.forEach(model => {
            const years = model.year_from && model.year_to ? 
                ` (${model.year_from}-${model.year_to})` : '';
            const displayName = model.name_cyrillic ? 
                `${model.name} / ${model.name_cyrillic}${years}` : 
                `${model.name}${years}`;
                
            modelSelect.innerHTML += 
                `<option value="${model.id}">${displayName}</option>`;
        });
    } else {
        modelSelect.disabled = true;
        modelSelect.innerHTML = '<option value="">Сначала выберите бренд</option>';
    }
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    console.log('Document loaded');
    
    // Если есть старое значение brand_id
    const oldBrandId = "{{ old('brand_id') }}";
    if (oldBrandId) {
        console.log('Old brand ID:', oldBrandId);
        setTimeout(() => {
            document.getElementById('brand_id').value = oldBrandId;
            loadModels();
            
            // Устанавливаем старое значение модели если есть
            const oldModelId = "{{ old('car_model_id') }}";
            if (oldModelId) {
                setTimeout(() => {
                    document.getElementById('car_model_id').value = oldModelId;
                }, 100);
            }
        }, 100);
    }
});
</script>
@endsection