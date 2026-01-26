@extends('layouts.diagnostic')

@section('title', ' - Мои консультации')

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Заголовок -->
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-3">Мои консультации</h1>
        <p class="text-gray-600">История заказанных консультаций</p>
    </div>
    
    <!-- Список консультаций -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6">
            @if($consultations->count() > 0)
                <div class="space-y-4">
                    @foreach($consultations as $consultation)
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h3 class="font-bold text-gray-800">
                                        @if($consultation->rule && $consultation->rule->symptom)
                                            {{ $consultation->rule->symptom->name }}
                                        @elseif($consultation->case)
                                            Диагностический кейс #{{ $consultation->case->id }}
                                        @else
                                            Консультация по диагностике
                                        @endif
                                    </h3>
                                    
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        @if($consultation->brand)
                                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                                {{ $consultation->brand->name }}
                                            </span>
                                        @endif
                                        
                                        @if($consultation->model)
                                            <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm">
                                                {{ $consultation->model->name }}
                                            </span>
                                        @endif
                                        
                                        <span class="px-3 py-1 
                                            @if($consultation->status === 'completed') bg-green-100 text-green-800
                                            @elseif($consultation->status === 'in_progress') bg-yellow-100 text-yellow-800
                                            @elseif($consultation->status === 'pending') bg-orange-100 text-orange-800
                                            @else bg-gray-100 text-gray-800 @endif
                                            rounded-full text-sm">
                                            {{ $this->getStatusText($consultation->status) }}
                                        </span>
                                        
                                        <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">
                                            {{ $this->getTypeText($consultation->consultation_type) }}
                                        </span>
                                    </div>
                                    
                                    <div class="mt-3 text-sm text-gray-600">
                                        <div class="flex items-center gap-4">
                                            <div>
                                                <i class="fas fa-calendar mr-1"></i>
                                                {{ $consultation->created_at->format('d.m.Y H:i') }}
                                            </div>
                                            <div>
                                                <i class="fas fa-money-bill-wave mr-1"></i>
                                                {{ number_format($consultation->price, 0, '', ' ') }} ₽
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="ml-4">
                                    <a href="{{ route('diagnostic.consultation.show', $consultation->id) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye mr-1"></i> Подробнее
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Пагинация -->
                <div class="mt-6">
                    {{ $consultations->links() }}
                </div>
            @else
                <div class="text-center py-10">
                    <i class="fas fa-inbox text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-600 mb-2">Нет консультаций</h3>
                    <p class="text-gray-500 mb-6">У вас еще нет заказанных консультаций</p>
                    <a href="{{ route('diagnostic.start') }}" class="btn btn-primary">
                        <i class="fas fa-stethoscope mr-2"></i> Начать диагностику
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

@php
    function getStatusText($status) {
        $statuses = [
            'pending' => 'Ожидание',
            'in_progress' => 'В работе',
            'completed' => 'Завершена',
            'cancelled' => 'Отменена'
        ];
        return $statuses[$status] ?? $status;
    }
    
    function getTypeText($type) {
        $types = [
            'basic' => 'Базовая',
            'premium' => 'Премиум',
            'expert' => 'Экспертная'
        ];
        return $types[$type] ?? $type;
    }
@endphp
@endsection