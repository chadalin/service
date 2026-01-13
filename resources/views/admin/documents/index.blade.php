@extends('layouts.app')

@section('title', 'Документы')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
    <h1 class="h2">Документы</h1>
    <a href="{{ route('admin.documents.create') }}" class="btn btn-primary">Загрузить документ</a>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Бренд/Модель</th>
                <th>Категория</th>
                <th>Тип файла</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach($documents as $document)
            <tr>
                <td>{{ $document->id }}</td>
                <td>{{ $document->title }}</td>
                <td>{{ $document->carModel->brand->name }} {{ $document->carModel->name }}</td>
                <td>{{ $document->category->name }}</td>
                <td>{{ strtoupper($document->file_type) }}</td>
                <td>
                    <span class="badge bg-{{ $document->status === 'processed' ? 'success' : ($document->status === 'processing' ? 'warning' : 'danger') }}">
                        {{ $document->status }}
                    </span>
                </td>
                <td>
                    <form action="{{ route('admin.documents.destroy', $document) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить документ?')">Удалить</button>
                    </form>
                </td>

                <td>
    <div class="btn-group btn-group-sm">
        <a href="{{ route('admin.documents.show', $document) }}" 
           class="btn btn-outline-primary">
            <i class="fas fa-eye"></i>
        </a>
        <a href="{{ route('admin.documents.download', $document) }}" 
           class="btn btn-outline-secondary">
            <i class="fas fa-download"></i>
        </a>
        <form action="{{ route('admin.documents.destroy', $document) }}" 
              method="POST" 
              class="d-inline"
              onsubmit="return confirm('Удалить этот документ?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">
                <i class="fas fa-trash"></i>
            </button>
        </form>
    </div>
</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    {{ $documents->links() }}
</div>
@endsection