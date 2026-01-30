<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ShowProjectInfo extends Command
{
    protected $signature = 'project:info {--json : Output as JSON} {--db : Show database only} {--models : Show models only}';
    protected $description = 'Show project structure information';

    public function handle()
    {
        $options = $this->options();
        
        if ($options['json']) {
            $this->showJsonInfo();
            return 0;
        }
        
        if ($options['db']) {
            $this->showDatabaseInfo();
            return 0;
        }
        
        if ($options['models']) {
            $this->showModelsInfo();
            return 0;
        }
        
        $this->showAllInfo();
        return 0;
    }

    protected function showAllInfo()
    {
        $this->info('=== Project Information ===');
        $this->line('Laravel Version: ' . app()->version());
        $this->line('PHP Version: ' . phpversion());
        $this->line('Environment: ' . app()->environment());
        $this->line('');
        
        $this->info('=== Database Information ===');
        $this->showDatabaseInfo(false);
        
        $this->info('=== Models ===');
        $this->showModelsInfo(false);
        
        $this->info('=== Controllers ===');
        $this->showControllersInfo();
        
        $this->info('=== Routes ===');
        $this->call('route:list', ['--compact' => true]);
    }

    protected function showDatabaseInfo($header = true)
    {
        if ($header) {
            $this->info('Database Structure:');
        }
        
        try {
            $tables = DB::select('SHOW TABLES');
            $dbName = config('database.connections.'.config('database.default').'.database');
            
            $this->table(['Table', 'Columns', 'Rows'], collect($tables)->map(function ($table) use ($dbName) {
                $tableName = $table->{'Tables_in_'.$dbName};
                
                $columns = DB::select("DESCRIBE `{$tableName}`");
                $rowCount = DB::table($tableName)->count();
                
                return [
                    $tableName,
                    count($columns),
                    $rowCount,
                ];
            }));
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }

    protected function showModelsInfo($header = true)
    {
        if ($header) {
            $this->info('Models:');
        }
        
        $modelsPath = app_path('Models');
        $models = [];
        
        if (File::exists($modelsPath)) {
            $files = File::files($modelsPath);
            
            foreach ($files as $file) {
                $models[] = [
                    'Model' => $file->getFilenameWithoutExtension(),
                    'File' => $file->getRelativePathname(),
                    'Size' => $this->formatBytes($file->getSize()),
                ];
            }
        }
        
        if (!empty($models)) {
            $this->table(['Model', 'File', 'Size'], $models);
        } else {
            $this->line('No models found in ' . $modelsPath);
        }
    }

    protected function showControllersInfo()
    {
        $controllersPath = app_path('Http/Controllers');
        $controllers = [];
        
        if (File::exists($controllersPath)) {
            $files = File::allFiles($controllersPath);
            
            foreach ($files as $file) {
                $controllers[] = [
                    'Controller' => $file->getFilenameWithoutExtension(),
                    'Path' => $file->getRelativePathname(),
                ];
            }
        }
        
        if (!empty($controllers)) {
            $this->table(['Controller', 'Path'], $controllers);
        }
    }

    protected function showJsonInfo()
    {
        $data = [
            'project' => [
                'laravel_version' => app()->version(),
                'php_version' => phpversion(),
                'environment' => app()->environment(),
                'app_name' => config('app.name'),
            ],
            'database' => $this->getDatabaseJson(),
            'models' => $this->getModelsJson(),
            'controllers' => $this->getControllersJson(),
        ];
        
        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }

    protected function getDatabaseJson()
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $dbName = config('database.connections.'.config('database.default').'.database');
            
            $tableData = [];
            foreach ($tables as $table) {
                $tableName = $table->{'Tables_in_'.$dbName};
                
                $columns = DB::select("DESCRIBE `{$tableName}`");
                $indexes = DB::select("SHOW INDEX FROM `{$tableName}`");
                
                $tableData[$tableName] = [
                    'columns' => $columns,
                    'indexes' => $indexes,
                    'row_count' => DB::table($tableName)->count(),
                ];
            }
            
            return [
                'driver' => config('database.default'),
                'database' => $dbName,
                'tables' => $tableData,
            ];
            
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    protected function getModelsJson()
    {
        $modelsPath = app_path('Models');
        $models = [];
        
        if (File::exists($modelsPath)) {
            $files = File::files($modelsPath);
            
            foreach ($files as $file) {
                $models[] = [
                    'name' => $file->getFilenameWithoutExtension(),
                    'file' => $file->getRelativePathname(),
                    'size_bytes' => $file->getSize(),
                ];
            }
        }
        
        return $models;
    }

    protected function getControllersJson()
    {
        $controllersPath = app_path('Http/Controllers');
        $controllers = [];
        
        if (File::exists($controllersPath)) {
            $files = File::allFiles($controllersPath);
            
            foreach ($files as $file) {
                $controllers[] = [
                    'name' => $file->getFilenameWithoutExtension(),
                    'file' => $file->getRelativePathname(),
                ];
            }
        }
        
        return $controllers;
    }

    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}