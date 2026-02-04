{{-- resources/views/search/results_simple.blade.php --}}

@if($documents->count() > 0)
    <div class="alert alert-info">
        Найдено документов: <strong>{{ $documents->total() }}</strong>
    </div>
    
    @foreach($documents as $document)
        <!-- Отображение документа -->
    @endforeach
    
    {{ $documents->links() }}
@endif