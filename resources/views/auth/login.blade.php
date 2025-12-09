@extends('layouts.app')

@section('title', 'Вход в систему')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card mt-5">
                <div class="card-header">
                    <h4 class="mb-0">Вход в AutoDoc AI</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('login.send-pin') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Получить PIN код</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection