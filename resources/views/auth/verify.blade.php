@extends('layouts.app')

@section('title', 'Подтверждение PIN')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card mt-5">
                <div class="card-header">
                    <h4 class="mb-0">Подтверждение входа</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">PIN код отправлен на: <strong>{{ session('email') }}</strong></p>
                    
                    @if(session('debug_pin'))
                        <div class="alert alert-info">
                            <strong>Development PIN:</strong> {{ session('debug_pin') }}
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('login.verify') }}">
                        @csrf
                        <input type="hidden" name="email" value="{{ session('email') }}">
                        <div class="mb-3">
                            <label for="pin_code" class="form-label">PIN код</label>
                            <input type="text" class="form-control @error('pin_code') is-invalid @enderror" 
                                   id="pin_code" name="pin_code" maxlength="6" required autofocus>
                            @error('pin_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Войти</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection