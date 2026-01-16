<!DOCTYPE html>
<html>
<head>
    <title>Project Structure Info</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .info-card {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 20px;
            border-radius: 4px;
        }
        .info-card h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #2980b9;
        }
        .json-viewer {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            max-height: 500px;
            overflow: auto;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            background: #e74c3c;
            color: white;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 10px;
        }
        .success { background: #2ecc71; }
        .warning { background: #f39c12; }
        .info { background: #3498db; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📁 Project Structure Information</h1>
        
        <div class="info-grid">
            <div class="info-card">
                <h3>Project Details</h3>
                <p><strong>Name:</strong> {{ $projectName }}</p>
                <p><strong>Laravel:</strong> {{ $laravelVersion }}</p>
                <p><strong>PHP:</strong> {{ $phpVersion }}</p>
                <p><strong>Database:</strong> {{ $databaseDriver }}</p>
            </div>
            
            <div class="info-card">
                <h3>Quick Actions</h3>
                <a href="{{ url('/project-info/database') }}" class="btn info" target="_blank">
                    📊 Database Structure
                </a>
                <a href="{{ url('/project-info/models') }}" class="btn success" target="_blank">
                    🗂️ All Models
                </a>
                <a href="{{ url('/project-info/controllers') }}" class="btn warning" target="_blank">
                    🎮 All Controllers
                </a>
                <a href="{{ url('/project-info/all') }}" class="btn" target="_blank">
                    📋 Complete Info (JSON)
                </a>
            </div>
            
            <div class="info-card">
                <h3>How to Use</h3>
                <p>Click any button to get JSON data about your project structure.</p>
                <p>Share the JSON output with AI assistant for analysis.</p>
                <p>All data is read-only and doesn't modify your project.</p>
            </div>
        </div>
        
        <div class="info-card">
            <h3>Copy-Paste Commands</h3>
            <p>Use these commands in your terminal:</p>
            <div class="json-viewer">
# Get all migrations
ls -la database/migrations/

# Export database schema
php artisan schema:dump --prune

# Show all routes
php artisan route:list --json

# Show all models
ls -la app/Models/
            </div>
        </div>
    </div>
</body>
</html>