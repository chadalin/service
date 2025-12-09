@extends('layouts.app')

@section('title', 'Добавить категорию')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Добавить новую категорию</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.categories.store') }}">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Название категории</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Описание</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Родительская категория</label>
                        <select class="form-select @error('parent_id') is-invalid @enderror" 
                                id="parent_id" name="parent_id">
                            <option value="">Основная категория (без родителя)</option>
                            @foreach($parentCategories as $parent)
                                <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                    {{ $parent->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('parent_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">Создать категорию</button>
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Отмена</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection