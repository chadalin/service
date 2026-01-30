@extends('layouts.app')

@section('title', ' - Создание симптома')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Создание нового симптома</h1>
        <p class="text-gray-600">Добавьте симптом для диагностической системы</p>
    </div>

    <form action="{{ route('admin.diagnostic.symptoms.store') }}" method="POST">
        @csrf
        
        <div class="bg-white rounded-lg shadow p-6 space-y-6">
            <!-- Название -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Название симптома *</label>
                <input type="text" 
                       name="name" 
                       id="name"
                       required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Например: Не заводится">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Описание -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Описание</label>
                <textarea name="description" 
                          id="description"
                          rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Подробное описание симптома..."></textarea>
            </div>

            <!-- Связанные системы -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Связанные системы</label>
                <div class="space-y-2" id="systems-container">
                    <div class="flex space-x-2">
                        <input type="text" 
                               name="related_systems[]" 
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg"
                               placeholder="Например: Двигатель">
                        <button type="button" onclick="addSystemField()" class="px-3 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Статус -->
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           name="is_active" 
                           value="1"
                           checked
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-700">Активный симптом</span>
                </label>
            </div>

            <!-- Кнопки -->
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('admin.diagnostic.symptoms.index') }}" class="btn-secondary">
                    Отмена
                </a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i> Сохранить симптом
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function addSystemField() {
    const container = document.getElementById('systems-container');
    const div = document.createElement('div');
    div.className = 'flex space-x-2';
    div.innerHTML = `
        <input type="text" 
               name="related_systems[]" 
               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg"
               placeholder="Например: Электрика">
        <button type="button" onclick="removeSystemField(this)" class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
            <i class="fas fa-minus"></i>
        </button>
    `;
    container.appendChild(div);
}

function removeSystemField(button) {
    button.parentElement.remove();
}
</script>
@endpush
@endsection