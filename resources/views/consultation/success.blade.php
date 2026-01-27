@extends('layouts.app')

@section('title', 'Заказ консультации принят')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-success text-white text-center py-4">
                    <i class="bi bi-check-circle display-1 mb-3"></i>
                    <h1 class="card-title mb-0">Заказ принят!</h1>
                </div>
                
                <div class="card-body p-5">
                    <div class="text-center mb-5">
                        <h3 class="text-success mb-3">Спасибо за заказ консультации!</h3>
                        <p class="lead">Наш эксперт свяжется с вами в течение 30 минут</p>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <div class="card border-light">
                                <div class="card-body">
                                    <h6><i class="bi bi-clock text-primary me-2"></i>Детали заказа</h6>
                                    <ul class="list-unstyled mb-0">
                                        <li><strong>№ заказа:</strong> #{{ $consultation->id }}</li>
                                        <li><strong>Тип:</strong> {{ $consultation->getConsultationTypeText() }}</li>
                                        <li><strong>Сумма:</strong> {{ number_format($consultation->price, 0, '', ' ') }} ₽</li>
                                        <li><strong>Статус:</strong> <span class="badge bg-warning">В обработке</span></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card border-light">
                                <div class="card-body">
                                    <h6><i class="bi bi-person text-primary me-2"></i>Контакты</h6>
                                    <ul class="list-unstyled mb-0">
                                        <li><strong>Имя:</strong> {{ $consultation->contact_name }}</li>
                                        <li><strong>Телефон:</strong> {{ $consultation->contact_phone }}</li>
                                        <li><strong>Email:</strong> {{ $consultation->contact_email }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle me-2"></i>Что дальше?</h6>
                        <ul class="mb-0">
                            <li>Эксперт изучит предоставленные материалы</li>
                            <li>Свяжется с вами по указанному телефону</li>
                            <li>Проведет консультацию и даст рекомендации</li>
                            <li>Отправит подробный отчет на email</li>
                        </ul>
                    </div>
                    
                    <div class="text-center mt-5">
                        <a href="{{ route('diagnostic.start') }}" class="btn btn-primary btn-lg me-3">
                            <i class="bi bi-plus-circle me-2"></i>Новая диагностика
                        </a>
                        <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-house me-2"></i>На главную
                        </a>
                    </div>
                </div>
                
                <div class="card-footer text-center text-muted py-3">
                    <small>
                        <i class="bi bi-shield-check me-1"></i>
                        Все данные защищены. Консультация проводится сертифицированными экспертами.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection