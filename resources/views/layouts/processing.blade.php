<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Обработка документов - @yield('title')</title>
    
    <!-- Bootstrap и иконки -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
        }
        
        .processing-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem;
        }
        
        .processing-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .processing-header h1 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .processing-header .breadcrumb {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            margin-bottom: 0;
        }
        
        .processing-header .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
        }
        
        .processing-header .breadcrumb-item a:hover {
            color: white;
        }
        
        .processing-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .processing-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        }
        
        .processing-card .card-header {
            border-radius: 12px 12px 0 0;
            font-weight: 600;
            padding: 1rem 1.5rem;
        }
        
        .processing-card .card-body {
            padding: 1.5rem;
        }
        
        /* Стили для кнопок обработки */
        .processing-btn {
            padding: 1rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            border: none;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .processing-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .processing-btn i {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .processing-btn small {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        /* Стили для прогресс-бара */
        .processing-progress {
            height: 25px;
            border-radius: 12px;
            background-color: #e9ecef;
            overflow: hidden;
        }
        
        .processing-progress .progress-bar {
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Стили для таблиц */
        .processing-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .processing-table th {
            font-weight: 600;
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 1rem;
        }
        
        .processing-table td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .processing-table tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Стили для баджей статусов */
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-uploaded { background-color: #6c757d; color: white; }
        .status-processing { background-color: #ffc107; color: #000; }
        .status-parsed { background-color: #28a745; color: white; }
        .status-preview_created { background-color: #17a2b8; color: white; }
        .status-parse_error { background-color: #dc3545; color: white; }
        
        /* Стили для содержимого документа */
        .document-content {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            font-size: 14px;
            max-height: 500px;
            overflow-y: auto;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .document-content p {
            margin-bottom: 1rem;
            text-align: justify;
        }
        
        .document-heading {
            font-weight: bold;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }
        
        .document-paragraph {
            color: #34495e;
        }
        
        /* Стили для изображений */
        .document-image {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 0.5rem;
            background: white;
            transition: all 0.3s ease;
        }
        
        .document-image:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .document-image img {
            border-radius: 6px;
            object-fit: contain;
            background: #f8f9fa;
        }
        
        /* Стили для статистики */
        .stat-card {
            text-align: center;
            padding: 1.5rem;
            border-radius: 10px;
            background: white;
            border: 1px solid #dee2e6;
        }
        
        .stat-card h2 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-card small {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        /* Анимации */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        /* Стили для AJAX результатов */
        .ajax-results {
            border-left: 4px solid #17a2b8;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0 8px 8px 0;
            margin-top: 1rem;
        }
        
        /* Мобильная адаптивность */
        @media (max-width: 768px) {
            .processing-container {
                padding: 0.75rem;
            }
            
            .processing-header {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .processing-btn {
                padding: 0.75rem;
                margin-bottom: 0.5rem;
            }
            
            .processing-btn i {
                font-size: 1.5rem;
            }
            
            .stat-card {
                padding: 1rem;
                margin-bottom: 0.75rem;
            }
            
            .document-content {
                max-height: 300px;
                font-size: 13px;
            }
            
            .processing-table {
                font-size: 0.9rem;
            }
            
            .processing-table th,
            .processing-table td {
                padding: 0.75rem 0.5rem;
            }
        }
        
        /* Стили для навигации */
        .processing-nav {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .processing-nav .nav-link {
            color: #495057;
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .processing-nav .nav-link:hover {
            background-color: #f8f9fa;
            color: #007bff;
        }
        
        .processing-nav .nav-link.active {
            background-color: #007bff;
            color: white;
            box-shadow: 0 2px 6px rgba(0, 123, 255, 0.3);
        }
        
        /* Стили для алертов */
        .processing-alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        
        /* Стили для модальных окон */
        .processing-modal .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        
        .processing-modal .modal-header {
            border-radius: 12px 12px 0 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .processing-modal .modal-footer {
            border-top: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
        }
        
        /* Кастомные цвета для прогресса */
        .progress-quality-excellent { background-color: #28a745; }
        .progress-quality-good { background-color: #17a2b8; }
        .progress-quality-medium { background-color: #ffc107; }
        .progress-quality-poor { background-color: #dc3545; }
    </style>
    
    @stack('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div class="processing-container">
        <!-- Верхняя навигация -->
        <div class="processing-nav">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm me-2">
                        <i class="bi bi-arrow-left"></i> Назад
                    </a>
                    <h5 class="mb-0 me-3">Обработка документов</h5>
                </div>
                
                <div class="d-flex">
                    <a href="{{ route('admin.documents.processing.index') }}" 
                       class="nav-link {{ request()->routeIs('admin.documents.processing.index') ? 'active' : '' }}">
                        <i class="bi bi-list"></i> Список
                    </a>
                    
                    @if(isset($document) && $document)
                    <a href="{{ route('admin.documents.processing.advanced', $document->id) }}" 
                       class="nav-link {{ request()->routeIs('admin.documents.processing.advanced') ? 'active' : '' }}">
                        <i class="bi bi-cpu"></i> Обработка
                    </a>
                    
                    <a href="{{ route('admin.documents.processing.view-images', $document->id) }}" 
                       class="nav-link {{ request()->routeIs('admin.documents.processing.view-images') ? 'active' : '' }}">
                        <i class="bi bi-images"></i> Изображения
                    </a>
                    @endif
                    
                    <a href="{{ route('admin.documents.index') }}" 
                       class="nav-link">
                        <i class="bi bi-files"></i> Документы
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Основной контент -->
        <div class="fade-in">
            <!-- Алерты -->
            @if(session('success'))
                <div class="alert alert-success processing-alert alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger processing-alert alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if(session('info'))
                <div class="alert alert-info processing-alert alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if($errors->any())
                <div class="alert alert-danger processing-alert alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @yield('content')
        </div>
        
        <!-- Футер -->
        <footer class="mt-4 text-center text-muted">
            <div class="small">
                <p class="mb-1">Система обработки документов AutoDoc AI</p>
                <p class="mb-0">© {{ date('Y') }} Все права защищены</p>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Processing layout loaded');
            
            // Автоматическое скрытие алертов через 5 секунд
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
            
            // Подтверждение действий
            document.querySelectorAll('button[type="submit"]').forEach(button => {
                if (button.hasAttribute('data-confirm')) {
                    button.addEventListener('click', function(e) {
                        if (!confirm(this.getAttribute('data-confirm'))) {
                            e.preventDefault();
                        }
                    });
                }
            });
            
            // Обновление прогресса в реальном времени
            function checkDocumentProgress(documentId) {
                if (!documentId) return;
                
                const progressElements = document.querySelectorAll('.processing-progress .progress-bar');
                if (progressElements.length === 0) return;
                
                fetch(`/admin/documents/processing/${documentId}/progress`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            progressElements.forEach(progressBar => {
                                const width = Math.min(data.progress, 100);
                                progressBar.style.width = width + '%';
                                progressBar.textContent = width.toFixed(1) + '%';
                                progressBar.setAttribute('aria-valuenow', width);
                                
                                // Обновляем класс качества
                                progressBar.classList.remove(
                                    'progress-quality-excellent',
                                    'progress-quality-good', 
                                    'progress-quality-medium',
                                    'progress-quality-poor'
                                );
                                
                                if (width >= 100) {
                                    progressBar.classList.add('progress-quality-excellent');
                                } else if (width >= 70) {
                                    progressBar.classList.add('progress-quality-good');
                                } else if (width >= 30) {
                                    progressBar.classList.add('progress-quality-medium');
                                } else {
                                    progressBar.classList.add('progress-quality-poor');
                                }
                            });
                            
                            // Если обработка завершена, перезагружаем страницу
                            if (data.progress >= 100 || data.status !== 'processing') {
                                setTimeout(() => {
                                    window.location.reload();
                                }, 2000);
                            }
                        }
                    })
                    .catch(error => console.error('Ошибка обновления прогресса:', error));
            }
            
            // Если есть элемент прогресса, начинаем обновление
            const progressBar = document.querySelector('.processing-progress .progress-bar');
            if (progressBar) {
                const urlParts = window.location.pathname.split('/');
                const documentId = urlParts[urlParts.length - 1];
                
                if (documentId && !isNaN(documentId)) {
                    // Проверяем прогресс каждые 3 секунды
                    setInterval(() => checkDocumentProgress(documentId), 3000);
                    // Первая проверка сразу
                    setTimeout(() => checkDocumentProgress(documentId), 1000);
                }
            }
            
            // Анимация для кнопок
            document.querySelectorAll('.processing-btn').forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // Плавная прокрутка
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 20,
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // Подсветка активного меню
            function highlightActiveNav() {
                const currentPath = window.location.pathname;
                document.querySelectorAll('.processing-nav .nav-link').forEach(link => {
                    const href = link.getAttribute('href');
                    if (href === currentPath || 
                        (href !== '/' && currentPath.startsWith(href))) {
                        link.classList.add('active');
                    } else {
                        link.classList.remove('active');
                    }
                });
            }
            
            highlightActiveNav();
            
            // Обработка отправки форм AJAX
            document.querySelectorAll('form[data-ajax]').forEach(form => {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    
                    // Показываем индикатор загрузки
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Обработка...';
                    submitBtn.disabled = true;
                    
                    try {
                        const formData = new FormData(this);
                        const response = await fetch(this.action, {
                            method: this.method,
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Показываем успешное сообщение
                            const alertHtml = `
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-check-circle me-2"></i>
                                    ${data.message}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            `;
                            
                            // Вставляем алерт перед формой
                            this.insertAdjacentHTML('beforebegin', alertHtml);
                            
                            // Перезагружаем страницу через 2 секунды
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else {
                            alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }
                    } catch (error) {
                        alert('Ошибка сети: ' + error.message);
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                });
            });
        });
    </script>
    
    @stack('scripts')
</body>
</html>