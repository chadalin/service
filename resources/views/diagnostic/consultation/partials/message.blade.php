@php
    $isOwnMessage = $message->user_id === auth()->id();
    $isClient = $message->user_id === $consultation->user_id;
    $isSystem = $message->type === 'system';
    $user = $message->user;
@endphp

<div class="message {{ $isOwnMessage ? 'own' : '' }} {{ $isClient ? 'message-client' : 'message-expert' }} animate-slide-in"
     data-message-id="{{ $message->id }}">
    <div class="message-avatar">
        @if($isSystem)
            <i class="bi bi-info-circle"></i>
        @else
            {{ $user ? strtoupper(substr($user->name, 0, 1)) : '?' }}
        @endif
    </div>
    <div class="message-content">
        @if(!$isSystem)
        <div class="message-header">
            <div class="message-user">
                {{ $user->name ?? ($isClient ? 'Клиент' : 'Эксперт') }}
                <span class="badge bg-{{ $isClient ? 'secondary' : 'success' }} ms-1">
                    {{ $isClient ? 'Клиент' : 'Эксперт' }}
                </span>
            </div>
            <div class="message-time">
                {{ $message->created_at->format('H:i') }}
            </div>
        </div>
        @endif
        
        <div class="message-text">{{ $message->message }}</div>
        
        @if($message->metadata)
    @php
        // Проверяем тип данных
        if (is_string($message->metadata)) {
            $metadataArray = json_decode($message->metadata, true);
            $attachments = $metadataArray['attachments'] ?? [];
        } elseif (is_array($message->metadata)) {
            $attachments = $message->metadata['attachments'] ?? [];
        } else {
            $attachments = [];
        }
    @endphp
            
            @if(count($attachments) > 0)
            <div class="message-attachments">
                @foreach($attachments as $attachment)
                <div class="attachment-item">
                    <div class="attachment-icon">
                        @php
                            $ext = pathinfo($attachment['original_name'] ?? '', PATHINFO_EXTENSION);
                            $icon = match(strtolower($ext)) {
                                'jpg', 'jpeg', 'png', 'gif', 'bmp' => 'bi-file-image',
                                'pdf' => 'bi-file-pdf',
                                'doc', 'docx' => 'bi-file-word',
                                'xls', 'xlsx' => 'bi-file-excel',
                                'mp4', 'avi', 'mov', 'mkv' => 'bi-file-play',
                                'mp3', 'wav' => 'bi-file-music',
                                default => 'bi-file-earmark'
                            };
                        @endphp
                        <i class="bi {{ $icon }}"></i>
                    </div>
                    <div class="attachment-info">
                        <div class="attachment-name">{{ $attachment['original_name'] ?? 'Файл' }}</div>
                        @if(isset($attachment['size']))
                            <div class="attachment-size">
                                @php
                                    $size = $attachment['size'];
                                    if ($size < 1024) {
                                        echo $size . ' B';
                                    } elseif ($size < 1024 * 1024) {
                                        echo round($size / 1024, 1) . ' KB';
                                    } else {
                                        echo round($size / (1024 * 1024), 1) . ' MB';
                                    }
                                @endphp
                            </div>
                        @endif
                    </div>
                    <a href="{{ asset('storage/' . ($attachment['path'] ?? '')) }}" 
                       download="{{ $attachment['original_name'] ?? 'file' }}" 
                       class="attachment-download">
                        <i class="bi bi-download fs-5"></i>
                    </a>
                </div>
                @endforeach
            </div>
            @endif
        @endif
        
        @if($isSystem)
        <div class="message-time">{{ $message->created_at->format('H:i') }}</div>
        @endif
    </div>
</div>