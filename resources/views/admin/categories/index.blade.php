@extends('layouts.app')

@section('title', 'Категории ремонта')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
    <h1 class="h2">Категории ремонта</h1>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">Добавить категорию</a>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Описание</th>
                <th>Родительская категория</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categories as $category)
            <tr>
                <td>{{ $category->id }}</td>
                <td>
                    <strong>{{ $category->name }}</strong>
                    @if($category->children->count() > 0)
                        <span class="badge bg-info">{{ $category->children->count() }} подкат.</span>
                    @endif
                </td>
                <td>{{ Str::limit($category->description, 100) }}</td>
                <td>
                    @if($category->parent)
                        {{ $category->parent->name }}
                    @else
                        <span class="text-muted">Основная категория</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('admin.categories.edit', $category) }}" 
                       class="btn btn-sm btn-outline-primary">Редактировать</a>
                    
                    <form action="{{ route('admin.categories.destroy', $category) }}" 
                          method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" 
                                onclick="return confirm('Удалить категорию?')">
                            Удалить
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection