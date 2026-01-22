<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Diagnostic\Symptom;
use App\Models\Diagnostic\Rule;
use App\Models\Brand;
use App\Models\CarModel;

class SymptomImportController extends Controller
{
    /**
     * Проверка PhpSpreadsheet
     */
    private function checkPhpSpreadsheet()
    {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            throw new \Exception('Установите PhpSpreadsheet: composer require phpoffice/phpspreadsheet');
        }
    }

    public function index()
    {
        return view('admin.diagnostic.symptom-import');
    }

    public function selectBrandModel()
    {
        $brands = Brand::orderBy('name')->get(['id', 'name']);
        $models = CarModel::orderBy('name')->get(['id', 'brand_id', 'name']);
        
        return view('admin.diagnostic.symptom-import-select', compact('brands', 'models'));
    }

    /**
     * Импорт для выбранной марки/модели
     */
    public function importForBrandModel(Request $request)
    {
        DB::beginTransaction();
        
        try {
            // Валидация
            $validator = Validator::make($request->all(), [
                'csv_file' => 'required|file|mimes:xlsx,xls',
                'brand_id' => 'required',
                'model_id' => 'nullable',
                'update_existing' => 'boolean',
            ], [
                'csv_file.required' => 'Выберите файл для загрузки',
                'csv_file.file' => 'Загруженный файл не является корректным файлом',
                'csv_file.mimes' => 'Файл должен быть в формате Excel (.xlsx или .xls)',
                'brand_id.required' => 'Выберите марку автомобиля',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('csv_file');
            $brandId = $request->input('brand_id');
            $modelId = $request->input('model_id', null);
            $updateExisting = $request->boolean('update_existing', true);
            
            Log::info('Import parameters:', [
                'brand_id' => $brandId,
                'model_id' => $modelId,
                'update_existing' => $updateExisting,
                'filename' => $file->getClientOriginalName()
            ]);
            
            // Проверяем существование бренда
            $brand = Brand::find($brandId);
            if (!$brand) {
                return response()->json([
                    'success' => false,
                    'message' => 'Марка не найдена'
                ], 404);
            }
            
            // Проверяем существование модели если указана
            $model = null;
            if ($modelId) {
                $model = CarModel::find($modelId);
                if (!$model) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Модель не найдена'
                    ], 404);
                }
            }

            $results = [
                'brand_name' => $brand->name,
                'model_name' => $model ? $model->name : 'Все модели',
                'symptoms_created' => 0,
                'symptoms_updated' => 0,
                'rules_created' => 0,
                'rules_updated' => 0,
                'errors' => [],
                'total_rows' => 0,
                'skipped_rows' => 0,
            ];

            // Обработка Excel файла
            $this->processExcelFile($file, $brandId, $modelId, $updateExisting, $results);

            DB::commit();
            
            Log::info('Import completed successfully:', $results);

            return response()->json([
                'success' => true,
                'message' => 'Импорт завершен успешно',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Import error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при импорте: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Обработка Excel файла с поддержкой многострочных данных
     */
    private function processExcelFile($file, $brandId, $modelId, $updateExisting, &$results)
    {
        Log::info('Processing Excel file with advanced multi-line support...');
        
        $this->checkPhpSpreadsheet();
        
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Получаем все данные как они есть в Excel
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            
            Log::info('Excel dimensions:', [
                'highestRow' => $highestRow,
                'highestColumn' => $highestColumn
            ]);
            
            if ($highestRow <= 1) {
                throw new \Exception('Файл пустой или содержит только заголовок');
            }
            
            // Определяем заголовки
            $headers = [];
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $cellValue = $worksheet->getCell($col . '1')->getValue();
                $headers[$col] = trim((string)$cellValue);
            }
            
            Log::info('Headers found:', $headers);
            
            // Определяем индексы колонок
            $colIndexes = [
                'name' => $this->findColumnIndex($headers, ['symptom', 'название', 'name']),
                'description' => $this->findColumnIndex($headers, ['description', 'описание']),
                'slug' => $this->findColumnIndex($headers, ['slug']),
                'diagnostic_steps' => $this->findColumnIndex($headers, ['diagnostic', 'steps', 'диагностика', 'шаги']),
                'possible_causes' => $this->findColumnIndex($headers, ['possible', 'causes', 'причины']),
                'required_data' => $this->findColumnIndex($headers, ['required', 'data', 'данные']),
                'complexity' => $this->findColumnIndex($headers, ['complexity', 'level', 'сложность']),
                'estimated_time' => $this->findColumnIndex($headers, ['estimated', 'time', 'время', 'минуты']),
                'price' => $this->findColumnIndex($headers, ['price', 'стоимость', 'consultation', 'цена']),
            ];
            
            Log::info('Column indexes:', $colIndexes);
            
            if ($colIndexes['name'] === null) {
                throw new \Exception('Не найдена колонка с названием симптома');
            }
            
            // Обрабатываем строки с учетом многострочных данных
            $currentSymptom = null;
            $symptomRows = [];
            $currentRowNum = 2; // Начинаем с второй строки (после заголовка)
            
            while ($currentRowNum <= $highestRow) {
                $rowData = $this->getRowData($worksheet, $currentRowNum, $highestColumn);
                
                // Проверяем, есть ли название симптома в этой строке
                $symptomName = $this->getCellValue($rowData, $colIndexes['name']);
                
                if (!empty($symptomName)) {
                    // Если у нас есть накопленный симптом, обрабатываем его
                    if ($currentSymptom !== null) {
                        $this->processMultiRowSymptom($currentSymptom, $symptomRows, $colIndexes,
                            $brandId, $modelId, $updateExisting, $results);
                    }
                    
                    // Начинаем новый симптом
                    $currentSymptom = [
                        'name' => $symptomName,
                        'description' => '',
                        'slug' => '',
                        'diagnostic_steps_raw' => '',
                        'possible_causes_raw' => '',
                        'required_data_raw' => '',
                        'complexity' => '',
                        'estimated_time' => '',
                        'price' => '',
                        'start_row' => $currentRowNum
                    ];
                    
                    // Заполняем данные из первой строки
                    $this->fillSymptomDataFromRow($currentSymptom, $rowData, $colIndexes);
                    
                    $symptomRows = [$rowData];
                    
                    Log::debug("New symptom started at row {$currentRowNum}: {$symptomName}");
                } elseif ($currentSymptom !== null) {
                    // Это продолжение текущего симптома
                    // Собираем многострочные данные
                    $this->appendSymptomDataFromRow($currentSymptom, $rowData, $colIndexes);
                    $symptomRows[] = $rowData;
                    
                    Log::debug("Continued symptom at row {$currentRowNum}");
                } else {
                    // Пустая строка перед первым симптомом - игнорируем
                    Log::debug("Empty row {$currentRowNum} before first symptom");
                }
                
                $currentRowNum++;
            }
            
            // Обрабатываем последний симптом
            if ($currentSymptom !== null) {
                $this->processMultiRowSymptom($currentSymptom, $symptomRows, $colIndexes,
                    $brandId, $modelId, $updateExisting, $results);
            }
            
            Log::info('Excel processing completed:', [
                'total_processed' => $results['total_rows'],
                'symptoms_created' => $results['symptoms_created'],
                'rules_created' => $results['rules_created'],
                'errors_count' => count($results['errors'])
            ]);
            
        } catch (\Exception $e) {
            Log::error('Excel processing error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Ошибка обработки Excel файла: ' . $e->getMessage());
        }
    }

    /**
     * Найти индекс колонки по ключевым словам
     */
    private function findColumnIndex($headers, $keywords)
    {
        foreach ($headers as $col => $header) {
            $headerLower = strtolower($header);
            foreach ($keywords as $keyword) {
                if (strpos($headerLower, strtolower($keyword)) !== false) {
                    return $col;
                }
            }
        }
        return null;
    }

    /**
     * Получить данные строки
     */
    private function getRowData($worksheet, $rowNum, $highestColumn)
    {
        $rowData = [];
        for ($col = 'A'; $col <= $highestColumn; $col++) {
            $cell = $worksheet->getCell($col . $rowNum);
            $value = $cell->getValue();
            
            // Получаем форматированное значение
            if ($cell->getDataType() === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA) {
                $value = $cell->getCalculatedValue();
            }
            
            // Обрабатываем объекты RichText
            if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                $value = $value->getPlainText();
            }
            
            $rowData[$col] = $value !== null ? trim((string)$value) : '';
        }
        return $rowData;
    }

    /**
     * Получить значение ячейки по индексу колонки
     */
    private function getCellValue($rowData, $colIndex)
    {
        if ($colIndex === null || !isset($rowData[$colIndex])) {
            return '';
        }
        return $rowData[$colIndex];
    }

    /**
     * Заполнить данные симптома из строки
     */
    private function fillSymptomDataFromRow(&$symptom, $rowData, $colIndexes)
    {
        $symptom['description'] = $this->getCellValue($rowData, $colIndexes['description']);
        $symptom['slug'] = $this->getCellValue($rowData, $colIndexes['slug']);
        $symptom['diagnostic_steps_raw'] = $this->getCellValue($rowData, $colIndexes['diagnostic_steps']);
        $symptom['possible_causes_raw'] = $this->getCellValue($rowData, $colIndexes['possible_causes']);
        $symptom['required_data_raw'] = $this->getCellValue($rowData, $colIndexes['required_data']);
        $symptom['complexity'] = $this->getCellValue($rowData, $colIndexes['complexity']);
        $symptom['estimated_time'] = $this->getCellValue($rowData, $colIndexes['estimated_time']);
        $symptom['price'] = $this->getCellValue($rowData, $colIndexes['price']);
    }

    /**
     * Добавить данные симптома из дополнительной строки
     */
    private function appendSymptomDataFromRow(&$symptom, $rowData, $colIndexes)
    {
        // Для текстовых полей добавляем с переносом строки
        $appendIfNotEmpty = function(&$target, $newValue) {
            if (!empty($newValue)) {
                if (!empty($target)) {
                    $target .= "\n" . $newValue;
                } else {
                    $target = $newValue;
                }
            }
        };
        
        $appendIfNotEmpty($symptom['diagnostic_steps_raw'], $this->getCellValue($rowData, $colIndexes['diagnostic_steps']));
        $appendIfNotEmpty($symptom['possible_causes_raw'], $this->getCellValue($rowData, $colIndexes['possible_causes']));
        $appendIfNotEmpty($symptom['required_data_raw'], $this->getCellValue($rowData, $colIndexes['required_data']));
        
        // Для одиночных полей берем только если еще не заполнены
        if (empty($symptom['description'])) {
            $symptom['description'] = $this->getCellValue($rowData, $colIndexes['description']);
        }
        if (empty($symptom['slug'])) {
            $symptom['slug'] = $this->getCellValue($rowData, $colIndexes['slug']);
        }
        if (empty($symptom['complexity'])) {
            $symptom['complexity'] = $this->getCellValue($rowData, $colIndexes['complexity']);
        }
        if (empty($symptom['estimated_time'])) {
            $symptom['estimated_time'] = $this->getCellValue($rowData, $colIndexes['estimated_time']);
        }
        if (empty($symptom['price'])) {
            $symptom['price'] = $this->getCellValue($rowData, $colIndexes['price']);
        }
    }

    /**
     * Обработать симптом, собранный из нескольких строк
     */
    private function processMultiRowSymptom($symptomData, $symptomRows, $colIndexes,
        $brandId, $modelId, $updateExisting, &$results)
    {
        $startRow = $symptomData['start_row'];
        $endRow = $startRow + count($symptomRows) - 1;
        $rowRange = $startRow . '-' . $endRow;
        $symptomName = $symptomData['name'];
        
        try {
            Log::debug("Processing symptom '{$symptomName}' (rows {$rowRange})", [
                'diagnostic_steps_length' => strlen($symptomData['diagnostic_steps_raw']),
                'possible_causes_length' => strlen($symptomData['possible_causes_raw']),
                'rows_count' => count($symptomRows)
            ]);
            
            if (empty($symptomName)) {
                $errorMsg = "Строки {$rowRange}: Отсутствует название симптома";
                $results['errors'][] = $errorMsg;
                $results['skipped_rows'] += count($symptomRows);
                return;
            }
            
            // Генерируем slug
            $slug = !empty($symptomData['slug']) ? 
                \Illuminate\Support\Str::slug($symptomData['slug']) : 
                \Illuminate\Support\Str::slug($symptomName);
            
            // Делаем slug уникальным
            $originalSlug = $slug;
            $counter = 1;
            while (Symptom::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            // Создаем или обновляем симптом
            $symptom = Symptom::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $symptomName,
                    'description' => $symptomData['description'],
                    'slug' => $slug,
                    'is_active' => true,
                ]
            );
            
            if ($symptom->wasRecentlyCreated) {
                $results['symptoms_created']++;
                Log::debug("Created symptom: {$symptomName} (ID: {$symptom->id})");
            } else {
                $results['symptoms_updated']++;
                Log::debug("Updated symptom: {$symptomName} (ID: {$symptom->id})");
            }
            
            // Парсим многострочные данные в правильный формат JSON
            $diagnosticSteps = $this->parseMultiLineToArray($symptomData['diagnostic_steps_raw']);
            $possibleCauses = $this->parseMultiLineToArray($symptomData['possible_causes_raw']);
            $requiredData = $this->parseMultiLineToArray($symptomData['required_data_raw']);
            
            // Преобразуем массивы в JSON
            $diagnosticStepsJson = json_encode($diagnosticSteps, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $possibleCausesJson = json_encode($possibleCauses, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $requiredDataJson = json_encode($requiredData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
            // Парсим числовые значения
            $complexity = $this->parseInt($symptomData['complexity'] ?? '3', 1, 10);
            $estimatedTime = $this->parseInt($symptomData['estimated_time'] ?? '60', 1, 480);
            $price = $this->parseFloat($symptomData['price'] ?? '3000');
            
            Log::debug("Parsed data for '{$symptomName}':", [
                'diagnostic_steps_count' => count($diagnosticSteps),
                'possible_causes_count' => count($possibleCauses),
                'required_data_count' => count($requiredData),
                'complexity' => $complexity,
                'price' => $price,
                'diagnostic_steps_json_valid' => json_last_error() === JSON_ERROR_NONE
            ]);
            
            // Проверяем существование правила
            $existingRule = Rule::where([
                'symptom_id' => $symptom->id,
                'brand_id' => $brandId,
                'model_id' => $modelId,
            ])->first();
            
            // Подготавливаем данные для правила
            $ruleData = [
                'symptom_id' => $symptom->id,
                'brand_id' => $brandId,
                'model_id' => $modelId,
                'diagnostic_steps' => $diagnosticSteps,
                'possible_causes' => $possibleCauses,
                'required_data' => $requiredData,
                'complexity_level' => $complexity,
                'estimated_time' => $estimatedTime,
                'base_consultation_price' => $price,
                'order' => 0,
                'is_active' => true,
            ];
            
            // Проверяем JSON перед сохранением
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка формирования JSON: ' . json_last_error_msg());
            }
            
            if ($existingRule) {
                if ($updateExisting) {
                    $existingRule->update($ruleData);
                    $results['rules_updated']++;
                    Log::debug("Updated rule for symptom: {$symptomName}");
                }
            } else {
                $rule = Rule::create($ruleData);
                $results['rules_created']++;
                Log::debug("Created rule for symptom: {$symptomName} (Rule ID: {$rule->id})");
            }
            
            $results['total_rows']++;
            Log::debug("Successfully processed symptom: {$symptomName}");
            
        } catch (\Exception $e) {
            $errorMsg = "Строки {$rowRange} (симптом: {$symptomName}): " . $e->getMessage();
            Log::error("Error processing accumulated symptom:", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'symptom_data' => [
                    'name' => $symptomName,
                    'diagnostic_steps_sample' => substr($symptomData['diagnostic_steps_raw'] ?? '', 0, 200),
                    'rows_count' => count($symptomRows)
                ]
            ]);
            
            $results['errors'][] = $errorMsg;
            $results['skipped_rows'] += count($symptomRows);
        }
    }

    /**
     * Парсинг многострочного текста в массив для JSON
     */
    private function parseMultiLineToArray($text)
    {
        if (empty($text)) {
            return [];
        }
        
        // Нормализуем переносы строк
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));
        
        // Если текст пустой после обрезки
        if (empty($text)) {
            return [];
        }
        
        // Сначала проверяем наличие стандартных разделителей
        $hasSemicolon = strpos($text, ';') !== false;
        $hasComma = strpos($text, ',') !== false;
        $hasPipe = strpos($text, '|') !== false;
        
        $result = [];
        
        if ($hasSemicolon) {
            // Разделяем по точке с запятой
            $parts = explode(';', $text);
            foreach ($parts as $part) {
                $trimmed = trim($part);
                if (!empty($trimmed)) {
                    $result[] = $trimmed;
                }
            }
        } elseif ($hasComma) {
            // Разделяем по запятым
            $parts = explode(',', $text);
            foreach ($parts as $part) {
                $trimmed = trim($part);
                if (!empty($trimmed)) {
                    $result[] = $trimmed;
                }
            }
        } elseif ($hasPipe) {
            // Разделяем по вертикальной черте
            $parts = explode('|', $text);
            foreach ($parts as $part) {
                $trimmed = trim($part);
                if (!empty($trimmed)) {
                    $result[] = $trimmed;
                }
            }
        } else {
            // Если нет разделителей, разбиваем по переносам строк
            $lines = explode("\n", $text);
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (!empty($trimmed)) {
                    $result[] = $trimmed;
                }
            }
        }
        
        return $result;
    }

    /**
     * Парсинг целого числа
     */
    private function parseInt($value, $min = 1, $max = 10)
    {
        $int = intval($value);
        return max($min, min($max, $int));
    }

    /**
     * Парсинг числа с плавающей точкой
     */
    private function parseFloat($value, $min = 0)
    {
        $float = floatval(str_replace(',', '.', $value));
        return max($min, $float);
    }

    /**
     * Получить модели для марки
     */
    public function getModels($brandId)
    {
        try {
            $models = CarModel::where('brand_id', $brandId)
                ->orderBy('name')
                ->get(['id', 'name']);
            
            return response()->json([
                'success' => true,
                'models' => $models
            ]);
        } catch (\Exception $e) {
            Log::error('Get models error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения моделей'
            ], 500);
        }
    }

    /**
     * Скачать шаблон Excel
     */
    public function downloadTemplate()
    {
        try {
            $this->checkPhpSpreadsheet();
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Заголовки
            $headers = [
                'A1' => 'symptom_name',
                'B1' => 'symptom_description', 
                'C1' => 'symptom_slug (опционально)',
                'D1' => 'diagnostic_steps',
                'E1' => 'possible_causes',
                'F1' => 'required_data',
                'G1' => 'complexity_level (1-10)',
                'H1' => 'estimated_time (минуты)',
                'I1' => 'consultation_price'
            ];
            
            foreach ($headers as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }
            
            // Пример данных с переносами строк
            $example1 = [
                'A2' => 'U3000-49',
                'B2' => 'Описание кода ошибки U3000-49',
                'C2' => 'u3000-49',
                'D2' => "Шаг 1: Проверить аккумулятор; Шаг 2: Проверить стартер; Шаг 3: Проверить топливную систему",
                'E2' => "Разряженный аккумулятор; Неисправный стартер; Проблемы с топливным насосом",
                'F2' => 'Напряжение аккумулятора; Состояние стартера; Давление топлива',
                'G2' => '3',
                'H2' => '120',
                'I2' => '3500'
            ];
            
            foreach ($example1 as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }
            
            // Пример продолжения данных (вторая строка для того же симптома)
            $example2 = [
                'A3' => '', // Пусто - продолжение предыдущего симптома
                'B3' => '', // Пусто
                'C3' => '', // Пусто
                'D3' => 'Шаг 4: Проверить систему зажигания',
                'E3' => 'Неисправные свечи зажигания',
                'F3' => 'Состояние свечей зажигания',
                'G3' => '', // Пусто
                'H3' => '', // Пусто
                'I3' => ''  // Пусто
            ];
            
            foreach ($example2 as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }
            
            // Третий симптом
            $example3 = [
                'A4' => 'U3000-50',
                'B4' => 'Описание кода ошибки U3000-50',
                'C4' => 'u3000-50',
                'D4' => "Шаг 1: Диагностика электронной системы\nШаг 2: Проверка датчиков\nШаг 3: Анализ кодов ошибок",
                'E4' => "Электронная неисправность; Проблемы с датчиками; Короткое замыкание",
                'F4' => 'Данные диагностики; Показания датчиков; Логи ошибок',
                'G4' => '4',
                'H4' => '180',
                'I4' => '4500'
            ];
            
            foreach ($example3 as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }
            
            // Четвертый симптом с продолжением в трех строках
            $example4 = [
                'A5' => 'U3000-51',
                'B5' => 'Комплексная диагностика',
                'C5' => 'u3000-51',
                'D5' => 'Шаг 1: Внешний осмотр',
                'E5' => 'Внешние повреждения',
                'F5' => '',
                'G5' => '5',
                'H5' => '240',
                'I5' => '6000'
            ];
            
            foreach ($example4 as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }
            
            // Продолжение четвертого симптома
            $example5 = [
                'A6' => '', // Пусто - продолжение
                'B6' => '', // Пусто
                'C6' => '', // Пусто
                'D6' => 'Шаг 2: Компьютерная диагностика',
                'E6' => 'Программные ошибки',
                'F6' => 'Диагностические коды',
                'G6' => '', // Пусто
                'H6' => '', // Пусто
                'I6' => ''  // Пусто
            ];
            
            foreach ($example5 as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }
            
            // Автоширина
            foreach (range('A', 'I') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Включаем перенос текста для колонок с длинным текстом
            $sheet->getStyle('D:D')->getAlignment()->setWrapText(true);
            $sheet->getStyle('E:E')->getAlignment()->setWrapText(true);
            $sheet->getStyle('F:F')->getAlignment()->setWrapText(true);
            
            // Стили для заголовков
            $headerStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E6E6FA']
                ]
            ];
            $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
            
            // Сохраняем файл
            $filename = 'symptoms_import_template_' . date('Y-m-d') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($tempFile);
            
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Template error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания шаблона: ' . $e->getMessage()
            ], 500);
        }
    }
}