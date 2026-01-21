@php
    $isOwnMessage = $message->user_id === auth()->id();
    $isClient = $message->user_id === $consultation->user_id;
@endphp

<div class="mb-3 {{ $isOwnMessage ? 'text-end' : '' }}">
    <div class="d-flex {{ $isOwnMessage ? 'flex-row-reverse' : '' }}">
        <div class="flex-shrink-0">
            <div class="avatar avatar-sm {{ $isClient ? 'bg-primary' : 'bg-success' }} text-white rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi {{ $isClient ? 'bi-person-fill' : 'bi-person-badge-fill' }}"></i>
            </div>
        </div>
        <div class="flex-grow-1 ms-2" style="max-width: 70%;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-muted">
                    {{ $message->user->name }}
                    <span class="badge {{ $isClient ? 'bg-secondary' : 'bg-success' }} ms-1">
                        {{ $isClient ? 'Клиент' : 'Эксперт' }}
                    </span>
                </small>
                <small class="text-muted">{{ $message->created_at->format('H:i') }}</small>
            </div>
            <div class="card {{ $isOwnMessage ? 'bg-primary text-white' : 'bg-light' }}">
                <div class="card-body p-2">
                    <p class="mb-0">{{ $message->message }}</p>
                    
                    @if($message->metadata && isset($message->metadata['attachments']) && is_array($message->metadata['attachments']))
                        <div class="mt-2">
                            @foreach($message->metadata['attachments'] as $attachment)
                                @if(is_array($attachment) && isset($attachment['path']))
                                    <div class="message-attachment p-2 mb-2 bg-white rounded">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-paperclip me-2"></i>
                                            <small class="text-truncate">{{ $attachment['original_name'] ?? 'Файл' }}</small>
                                            <a href="{{ asset('storage/' . $attachment['path']) }}" 
                                               target="_blank" 
                                               class="ms-2 btn btn-sm btn-outline-primary">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>