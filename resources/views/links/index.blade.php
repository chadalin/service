{{-- resources/views/links/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Ссылки')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Управление ссылками</h1>
    <a href="{{ route('links.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Добавить ссылку
    </a>
</div>

<div class="row">
    @forelse($links as $link)
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h5 class="card-title">{{ $link->title }}</h5>
                        <span class="badge bg-{{ $link->auth_type === 'basic' ? 'warning' : ($link->auth_type === 'form' ? 'info' : 'secondary') }}">
                            @if($link->auth_type === 'basic')
                                Basic Auth
                            @elseif($link->auth_type === 'form')
                                Form Auth
                            @else
                                No Auth
                            @endif
                        </span>
                    </div>
                    
                    <p class="card-text">
                        <small class="text-muted">
                            <i class="bi bi-globe"></i> {{ $link->domain }}
                        </small>
                    </p>
                    
                    @if($link->description)
                        <p class="card-text">{{ $link->description }}</p>
                    @endif
                    
                    <div class="mb-2">
                        @if($link->login)
                            <small class="text-muted">
                                <i class="bi bi-person"></i> {{ $link->login }}
                            </small>
                        @endif
                    </div>
                    
                    <div class="btn-group" role="group">
                        <a href="{{ $link->formatted_url }}" class="btn btn-success btn-sm" target="_blank">
                            <i class="bi bi-box-arrow-up-right"></i> Перейти
                        </a>
                        <a href="{{ route('links.edit', $link) }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-pencil"></i> Редактировать
                        </a>
                        <form action="{{ route('links.destroy', $link) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Удалить ссылку?')">
                                <i class="bi bi-trash"></i> Удалить
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    <small>Добавлено: {{ $link->created_at->format('d.m.Y H:i') }}</small>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Нет добавленных ссылок. 
                <a href="{{ route('links.create') }}" class="alert-link">Добавьте первую ссылку</a>
            </div>
        </div>
    @endforelse
</div>

<div class="d-flex justify-content-center">
    {{ $links->links() }}
</div>
@endsection