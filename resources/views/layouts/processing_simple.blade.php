<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Обработка документов - @yield('title')</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding-bottom: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .breadcrumb {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.75rem 1rem;
            border-radius: 8px;
        }
        
        .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
        }
        
        .breadcrumb-item a:hover {
            color: white;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600;
            padding: 1.25rem 1.5rem;
        }
        
        .btn {
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }
        
        .processing-btn {
            height: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .processing-btn i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .progress {
            height: 25px;
            border-radius: 12px;
        }
        
        .progress-bar {
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .document-content {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            font-size: 14px;
            max-height: 400px;
            overflow-y: auto;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .document-content p {
            margin-bottom: 1rem;
        }
        
        .document-heading {
            font-weight: bold;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }
        
        .stat-card {
            text-align: center;
            padding: 1.5rem;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            background: white;
        }
        
        .stat-card h2 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .nav-link {
            color: #495057;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            margin: 0.25rem 0;
        }
        
        .nav-link:hover {
            background-color: #f8f9fa;
            color: #007bff;
        }
        
        .nav-link.active {
            background-color: #007bff;
            color: white;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .fa-spin {
            animation: spin 1s linear infinite;
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 1.5rem 0;
            }
            
            .processing-btn {
                height: 100px;
                padding: 1rem;
            }
            
            .processing-btn i {
                font-size: 1.5rem;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Шапка -->
    <div class="header">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                <div>
                    <h1 class="h2 mb-2">@yield('page_title', 'Обработка документов')</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Главная</a></li>
                            @yield('breadcrumbs')
                        </ol>
                    </nav>
                </div>
                
                <div class="mt-2 mt-md-0">
                    <div class="btn-group" role="group">
                        <a href="{{ route('admin.documents.processing.index') }}" class="btn btn-outline-light">
                            <i class="bi bi-list"></i> Список
                        </a>
                        <a href="{{ route('admin.documents.index') }}" class="btn btn-outline-light ms-2">
                            <i class="bi bi-files"></i> Документы
                        </a>
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-light ms-2">
                            <i class="bi bi-house"></i> Дашборд
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Основной контент -->
    <div class="container">
        <!-- Уведомления -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
            
            // Обновление прогресса для обработки документов
            function updateDocumentProgress() {
                const progressElements = document.querySelectorAll('[data-progress-url]');
                
                progressElements.forEach(element => {
                    const url = element.getAttribute('data-progress-url');
                    if (!url) return;
                    
                    fetch(url)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Обновляем все прогресс-бары
                                document.querySelectorAll('.progress-bar').forEach(bar => {
                                    if (bar.id === 'progressBar' || bar.classList.contains('progress-bar')) {
                                        bar.style.width = data.progress + '%';
                                        bar.textContent = data.progress.toFixed(1) + '%';
                                        bar.setAttribute('aria-valuenow', data.progress);
                                    }
                                });
                                
                                // Обновляем текстовые элементы
                                const percentElement = document.getElementById('progressPercent');
                                if (percentElement) {
                                    percentElement.textContent = data.progress.toFixed(1) + '%';
                                }
                                
                                const statusElement = document.getElementById('progressStatus');
                                if (statusElement) {
                                    statusElement.textContent = data.message || 'В обработке...';
                                }
                                
                                // Если обработка завершена, перезагружаем страницу
                                if (data.progress >= 100 || data.status !== 'processing') {
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 2000);
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error updating progress:', error);
                        });
                });
            }
            
            // Если есть элементы прогресса, обновляем их каждые 3 секунды
            const progressElements = document.querySelectorAll('[data-progress-url]');
            if (progressElements.length > 0) {
                setInterval(updateDocumentProgress, 3000);
                // Первое обновление сразу
                setTimeout(updateDocumentProgress, 1000);
            }
            
            // Обработка подтверждения действий
            document.querySelectorAll('form').forEach(form => {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && submitBtn.hasAttribute('data-confirm')) {
                    form.addEventListener('submit', function(e) {
                        if (!confirm(submitBtn.getAttribute('data-confirm'))) {
                            e.preventDefault();
                            return false;
                        }
                        
                        // Показываем индикатор загрузки
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Обработка...';
                        submitBtn.disabled = true;
                        
                        // Восстанавливаем кнопку через 30 секунд на всякий случай
                        setTimeout(() => {
                            if (submitBtn.disabled) {
                                submitBtn.innerHTML = originalText;
                                submitBtn.disabled = false;
                            }
                        }, 30000);
                    });
                }
            });
            
            // Анимация для кнопок
            document.querySelectorAll('.btn:not(:disabled)').forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
    
    @stack('scripts')
</body>
</html>