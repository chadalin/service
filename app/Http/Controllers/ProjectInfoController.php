<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProjectInfoController extends Controller
{
    /**
     * Главная страница с информацией о проекте
     */
    public function showProjectInfo()
    {
        return view('project-info.index', [
            'projectName' => config('app.name'),
            'laravelVersion' => app()->version(),
            'phpVersion' => phpversion(),
            'databaseDriver' => config('database.default'),
        ]);
    }

    /**
     * Показать структуру базы данных
     */
    public function showDatabaseStructure()
    {
        $tables = [];
        
        try {
            // Получаем все таблицы
            $tableNames = DB::select('SHOW TABLES');
            $databaseName = config('database.connections.'.config('database.default').'.database');
            
            foreach ($tableNames as $table) {
                $tableName = $table->{'Tables_in_'.$databaseName} ?? 
                            (array_values((array)$table)[0] ?? 'unknown');
                
                // Получаем структуру таблицы
                $columns = DB::select("DESCRIBE `{$tableName}`");
                
                // Получаем индексы
                $indexes = DB::select("SHOW INDEX FROM `{$tableName}`");
                
                // Получаем внешние ключи (для MySQL)
                $foreignKeys = DB::select("
                    SELECT 
                        COLUMN_NAME,
                        REFERENCED_TABLE_NAME,
                        REFERENCED_COLUMN_NAME,
                        CONSTRAINT_NAME
                    FROM 
                        INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE 
                        TABLE_SCHEMA = '{$databaseName}' 
                        AND TABLE_NAME = '{$tableName}'
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                $tables[$tableName] = [
                    'columns' => $columns,
                    'indexes' => $indexes,
                    'foreign_keys' => $foreignKeys,
                    'row_count' => DB::table($tableName)->count(),
                ];
            }
            
            // Сортируем таблицы по имени
            ksort($tables);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Не удалось получить структуру базы данных',
                'message' => $e->getMessage()
            ], 500);
        }
        
        return response()->json([
            'database' => config('database.default'),
            'connection' => config('database.connections.'.config('database.default')),
            'tables' => $tables,
            'total_tables' => count($tables),
        ]);
    }

    /**
     * Показать все модели
     */
    public function showModels()
    {
        $models = [];
        $modelsPath = app_path('Models');
        
        if (File::exists($modelsPath)) {
            $files = File::allFiles($modelsPath);
            
            foreach ($files as $file) {
                $className = 'App\\Models\\' . $file->getFilenameWithoutExtension();
                
                if (class_exists($className)) {
                    try {
                        $model = new $className;
                        $table = $model->getTable();
                        
                        $models[] = [
                            'name' => $file->getFilenameWithoutExtension(),
                            'file' => $file->getRelativePathname(),
                            'table' => $table,
                            'fillable' => $model->getFillable(),
                            'casts' => $model->getCasts(),
                            'timestamps' => $model->timestamps,
                            'connection' => $model->getConnectionName(),
                        ];
                    } catch (\Exception $e) {
                        $models[] = [
                            'name' => $file->getFilenameWithoutExtension(),
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }
        }
        
        // Ищем модели в других местах
        $otherModels = $this->findModelsInDirectory(app_path());
        
        return response()->json([
            'models_directory' => $modelsPath,
            'models' => array_merge($models, $otherModels),
            'total_models' => count($models) + count($otherModels),
        ]);
    }

    /**
     * Показать все контроллеры
     */
    public function showControllers()
    {
        $controllers = [];
        $controllersPath = app_path('Http/Controllers');
        
        if (File::exists($controllersPath)) {
            $files = File::allFiles($controllersPath);
            
            foreach ($files as $file) {
                $relativePath = str_replace([app_path(), '.php'], '', $file->getPathname());
                $className = 'App' . str_replace('/', '\\', $relativePath);
                
                if (class_exists($className)) {
                    $reflection = new \ReflectionClass($className);
                    $methods = [];
                    
                    foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                        if ($method->class === $className && !$method->isConstructor()) {
                            $methods[] = $method->name;
                        }
                    }
                    
                    $controllers[] = [
                        'name' => $file->getFilenameWithoutExtension(),
                        'namespace' => $className,
                        'file' => $file->getRelativePathname(),
                        'methods' => $methods,
                        'parent_class' => $reflection->getParentClass() ? $reflection->getParentClass()->getName() : null,
                    ];
                }
            }
        }
        
        return response()->json([
            'controllers_directory' => $controllersPath,
            'controllers' => $controllers,
            'total_controllers' => count($controllers),
        ]);
    }

    /**
     * Вся информация о проекте
     */
    public function showAllInfo()
    {
        return response()->json([
            'project' => [
                'name' => config('app.name'),
                'environment' => app()->environment(),
                'debug' => config('app.debug'),
                'url' => config('app.url'),
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
            ],
            'versions' => [
                'laravel' => app()->version(),
                'php' => phpversion(),
            ],
            'database' => $this->getDatabaseInfo(),
            'models' => json_decode($this->showModels()->getContent(), true),
            'controllers' => json_decode($this->showControllers()->getContent(), true),
            'directories' => [
                'app' => $this->scanDirectory(app_path()),
                'config' => $this->scanDirectory(config_path()),
                'database' => $this->scanDirectory(database_path('migrations')),
                'routes' => $this->scanDirectory(base_path('routes')),
            ],
        ]);
    }

    /**
     * Вспомогательная функция для поиска моделей
     */
    private function findModelsInDirectory($directory, $namespace = 'App\\')
    {
        $models = [];
        
        if (!File::exists($directory)) {
            return $models;
        }
        
        $files = File::allFiles($directory);
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $relativePath = str_replace([app_path(), '.php'], '', $file->getPathname());
                $className = $namespace . str_replace('/', '\\', ltrim($relativePath, '/\\'));
                
                if (class_exists($className)) {
                    $reflection = new \ReflectionClass($className);
                    
                    // Проверяем, является ли класс моделью Eloquent
                    if ($reflection->isSubclassOf('Illuminate\\Database\\Eloquent\\Model') || 
                        Str::contains($className, 'Model')) {
                        
                        try {
                            $model = new $className;
                            
                            $models[] = [
                                'name' => $file->getFilenameWithoutExtension(),
                                'file' => $file->getRelativePathname(),
                                'table' => $model->getTable(),
                                'namespace' => $className,
                            ];
                        } catch (\Exception $e) {
                            // Пропускаем модели, которые нельзя инстанциировать
                        }
                    }
                }
            }
        }
        
        return $models;
    }

    /**
     * Сканирование директории
     */
    private function scanDirectory($path)
    {
        if (!File::exists($path)) {
            return [];
        }
        
        $items = [];
        $files = File::allFiles($path);
        
        foreach ($files as $file) {
            $items[] = [
                'name' => $file->getFilename(),
                'path' => $file->getRelativePathname(),
                'size' => $file->getSize(),
                'modified' => date('Y-m-d H:i:s', $file->getMTime()),
            ];
        }
        
        return $items;
    }

    /**
     * Получить информацию о базе данных
     */
    private function getDatabaseInfo()
    {
        try {
            $connection = config('database.default');
            $config = config('database.connections.' . $connection);
            
            $dbInfo = [
                'driver' => $connection,
                'database' => $config['database'] ?? null,
                'host' => $config['host'] ?? null,
                'port' => $config['port'] ?? null,
                'username' => $config['username'] ?? null,
                'charset' => $config['charset'] ?? null,
                'collation' => $config['collation'] ?? null,
            ];
            
            // Получаем таблицы
            $tables = DB::select('SHOW TABLES');
            $dbInfo['tables_count'] = count($tables);
            
            return $dbInfo;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}