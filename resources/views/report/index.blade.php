@extends('layouts.diagnostic')

@section('title', 'Мои отчеты диагностики')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Мои отчеты диагностики</h1>
            <p class="text-gray-600">История всех ваших диагностических проверок</p>
        </div>
        
        @if($cases->isEmpty())
        <div class="bg-white rounded-xl shadow-sm p-8 text-center">
            <div class="mb-4">
                <i class="fas fa-file-alt text-4xl text-gray-300"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Отчетов пока нет</h3>
            <p class="text-gray-600 mb-6">Проведите первую диагностику вашего автомобиля</p>
            <a href="{{ route('diagnostic.start') }}" class="btn-primary inline-block">
                <i class="fas fa-stethoscope mr-2"></i> Начать диагностику
            </a>
        </div>
        @else
        <div class="grid grid-cols-1 gap-4">
            @foreach($cases as $case)
            <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300">
                <div class="p-4 md:p-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between mb-4">
                        <div>
                            <div class="flex items-center mb-2">
                                <span class="text-sm text-gray-500 mr-3">
                                    #{{ substr($case->id, 0, 8) }}
                                </span>
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">
                                    {{ $case->status === 'report_ready' ? 'Готов' : 'В процессе' }}
                                </span>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-1">
                                {{ $case->brand->name ?? 'Марка не указана' }} 
                                {{ $case->model->name ?? '' }}
                            </h3>
                            <div class="text-sm text-gray-600">
                                <span class="mr-3">
                                    <i class="fas fa-calendar mr-1"></i>
                                    {{ $case->created_at->format('d.m.Y') }}
                                </span>
                                <span>
                                    <i class="fas fa-clock mr-1"></i>
                                    {{ $case->created_at->format('H:i') }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="mt-3 md:mt-0">
                            @if($case->activeReport)
                            <div class="text-right">
                                <div class="text-2xl font-bold text-blue-600">
                                    {{ $case->activeReport->getTotalCost() ? number_format($case->activeReport->getTotalCost(), 0, '', ' ') . ' ₽' : '—' }}
                                </div>
                                <div class="text-sm text-gray-500">Ориентировочно</div>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        @if(!empty($case->symptoms) && is_array($case->symptoms))
                        <div class="flex flex-wrap gap-2">
                            @foreach(array_slice($case->symptoms, 0, 3) as $symptomId)
                                @php
                                    $symptom = \App\Models\Diagnostic\Symptom::find($symptomId);
                                @endphp
                                @if($symptom)
                                    <span class="px-3 py-1 bg-gray-100 text-gray-700 text-sm rounded-full">
                                        {{ $symptom->name }}
                                    </span>
                                @endif
                            @endforeach
                            @if(count($case->symptoms) > 3)
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 text-sm rounded-full">
                                    +{{ count($case->symptoms) - 3 }} еще
                                </span>
                            @endif
                        </div>
                        @endif
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('diagnostic.report.show', $case->id) }}" 
                           class="btn-primary flex-1 text-center py-3">
                            <i class="fas fa-eye mr-2"></i> Просмотреть отчет
                        </a>
                        
                        @if($case->activeReport)
                        <a href="{{ route('diagnostic.report.pdf', $case->id) }}" 
                           class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-4 rounded-lg shadow hover:shadow-md transition-all duration-300 flex-1 text-center">
                            <i class="fas fa-file-pdf mr-2"></i> Скачать PDF
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        <!-- Пагинация -->
        <div class="mt-6">
            {{ $cases->links() }}
        </div>
        @endif
    </div>
</div>
@endsection