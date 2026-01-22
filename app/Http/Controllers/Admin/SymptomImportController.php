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
        // Включим подробное логирование
        Log::info('=== IMPORT START ===');
        Log::info('Request data:', $request->all());
        Log::info('Has file:', ['has_file' => $request->hasFile('csv_file')]);
        
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
                Log::error('Validation failed:', $validator->errors()->toArray());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('csv_file');
            $brandId = $request->input('brand_id'); // Это строка "LAND_ROVER"
            $modelId = $request->input('model_id', null); // Это число или null
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
                Log::error('Brand not found:', ['brand_id' => $brandId]);
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
                    Log::error('Model not found:', ['model_id' => $modelId]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Модель не найдена'
                    ], 404);
                }
                
                // Проверяем что модель принадлежит бренду
                if ($model->brand_id !== $brandId) {
                    Log::error('Model does not belong to brand:', [
                        'model_brand_id' => $model->brand_id,
                        'selected_brand_id' => $brandId
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Модель не принадлежит выбранной марке'
                    ], 400);
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
     * Обработка Excel файла
     */
    private function processExcelFile($file, $brandId, $modelId, $updateExisting, &$results)
    {
        Log::info('Processing Excel file...');
        
        $this->checkPhpSpreadsheet();
        
        try {
            // Загружаем файл
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Получаем все данные
            $data = $worksheet->toArray();
            
            Log::info('Excel file loaded:', [
                'total_rows' => count($data),
                'first_row' => $data[0] ?? []
            ]);
            
            if (empty($data)) {
                throw new \Exception('Файл пустой');
            }
            
            // Проверяем, есть ли заголовок
            $firstRow = $data[0];
            $isHeader = $this->isHeaderRow($firstRow);
            $startRow = $isHeader ? 1 : 0;
            
            if ($isHeader) {
                Log::info('Header detected and skipped:', $firstRow);
            }
            
            // Обрабатываем каждую строку
            for ($i = $startRow; $i < count($data); $i++) {
                $row = $data[$i];
                $rowNumber = $i + 1; // Номер строки для отображения
                
                Log::debug("Processing row {$rowNumber}:", [
                    'row_data' => $row,
                    'non_empty_cells' => count(array_filter($row, function($v) { 
                        return $v !== null && trim($v) !== ''; 
                    }))
                ]);
                
                $this->processRow($row, $rowNumber, $brandId, $modelId, $updateExisting, $results);
            }
            
            Log::info('Excel processing completed:', [
                'total_processed' => $results['total_rows'],
                'symptoms_created' => $results['symptoms_created'],
                'rules_created' => $results['rules_created']
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
     * Обработка строки
     */
    private function processRow($row, $rowNumber, $brandId, $modelId, $updateExisting, &$results)
    {
        try {
            // Очищаем значения
            $row = array_map(function($value) {
                if (is_null($value)) {
                    return '';
                }
                return trim((string)$value);
            }, $row);
            
            Log::debug("Row {$rowNumber} after cleaning:", $row);
            
            // Пропускаем пустые строки
            $nonEmptyCells = array_filter($row, function($value) {
                return $value !== '';
            });
            
            if (empty($nonEmptyCells)) {
                Log::debug("Row {$rowNumber} is empty, skipping");
                $results['skipped_rows']++;
                return;
            }
            
            // Проверяем наличие названия симптома
            if (empty($row[0])) {
                $errorMsg = "Строка {$rowNumber}: Отсутствует название симптома";
                Log::warning($errorMsg);
                $results['errors'][] = $errorMsg;
                $results['skipped_rows']++;
                return;
            }
            
            $symptomName = $row[0];
            $description = $row[1] ?? '';
            $slugInput = $row[2] ?? '';
            
            // Генерируем slug
            $slug = !empty($slugInput) ? 
                \Illuminate\Support\Str::slug($slugInput) : 
                \Illuminate\Support\Str::slug($symptomName);
            
            Log::debug("Row {$rowNumber} - Symptom: {$symptomName}, Slug: {$slug}");
            
            // Создаем или обновляем симптом
            $symptomData = [
                'name' => $symptomName,
                'description' => $description,
                'slug' => $slug,
                'is_active' => true,
            ];
            
            $symptom = Symptom::updateOrCreate(
                ['slug' => $slug],
                $symptomData
            );
            
            if ($symptom->wasRecentlyCreated) {
                $results['symptoms_created']++;
                Log::debug("Row {$rowNumber} - Created new symptom (ID: {$symptom->id})");
            } else {
                $results['symptoms_updated']++;
                Log::debug("Row {$rowNumber} - Updated existing symptom (ID: {$symptom->id})");
            }
            
            // Подготавливаем данные для правила
            $diagnosticSteps = $this->parseTextToArray($row[3] ?? '');
            $possibleCauses = $this->parseTextToArray($row[4] ?? '');
            $requiredData = $this->parseTextToArray($row[5] ?? '');
            $complexity = $this->parseInt($row[6] ?? 3);
            $estimatedTime = $this->parseInt($row[7] ?? 60);
            $price = $this->parseFloat($row[8] ?? 3000);
            
            Log::debug("Row {$rowNumber} - Rule data prepared:", [
                'diagnostic_steps_count' => count($diagnosticSteps),
                'complexity' => $complexity,
                'price' => $price
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
                'brand_id' => $brandId, // Это строка "LAND_ROVER"
                'model_id' => $modelId, // Это число или null
                'diagnostic_steps' => $diagnosticSteps,
                'possible_causes' => $possibleCauses,
                'required_data' => $requiredData,
                'complexity_level' => $complexity,
                'estimated_time' => $estimatedTime,
                'base_consultation_price' => $price,
                'order' => 0,
                'is_active' => true,
            ];
            
            Log::debug("Row {$rowNumber} - Rule data for DB:", $ruleData);
            
            if ($existingRule) {
                Log::debug("Row {$rowNumber} - Rule exists (ID: {$existingRule->id})");
                
                if ($updateExisting) {
                    $existingRule->update($ruleData);
                    $results['rules_updated']++;
                    Log::debug("Row {$rowNumber} - Rule updated");
                }
            } else {
                // Создаем новое правило
                $rule = Rule::create($ruleData);
                $results['rules_created']++;
                Log::debug("Row {$rowNumber} - Created new rule (ID: {$rule->id})");
            }
            
            $results['total_rows']++;
            Log::debug("Row {$rowNumber} - Processing completed successfully");
            
        } catch (\Exception $e) {
            $errorMsg = "Строка {$rowNumber}: " . $e->getMessage();
            Log::error("Error processing row {$rowNumber}:", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'row_data' => $row
            ]);
            
            $results['errors'][] = $errorMsg;
            $results['skipped_rows']++;
        }
    }

    /**
     * Проверка на заголовок
     */
    private function isHeaderRow($row)
    {
        if (empty($row) || empty($row[0])) {
            return false;
        }
        
        $firstCell = strtolower(trim($row[0]));
        
        $headerKeywords = ['symptom', 'симптом', 'name', 'название'];
        
        foreach ($headerKeywords as $keyword) {
            if (strpos($firstCell, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Парсинг текста в массив
     */
   /**
 * Парсинг текста в массив с сохранением переносов строк
 */
   private function parseTextToArray($text)
{
    if (empty($text)) {
        return [];
    }
    
    $text = trim($text);
    
    // Нормализуем переносы строк
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    
    // Если текст содержит только переносы строк без других разделителей
    if (strpos($text, ';') === false && 
        strpos($text, ',') === false && 
        strpos($text, '|') === false) {
        
        // Разбиваем по переносам строк
        $items = explode("\n", $text);
        $items = array_map('trim', $items);
        $items = array_filter($items, function($item) {
            return !empty($item);
        });
        
        if (!empty($items)) {
            return array_values($items);
        }
    }
    
    // Сохраняем переносы строк внутри элементов
    $text = str_replace("\n", '###NEWLINE###', $text);
    
    // Пробуем стандартные разделители
    $delimiters = [';', ',', '|'];
    
    foreach ($delimiters as $delimiter) {
        if (strpos($text, $delimiter) !== false) {
            $items = explode($delimiter, $text);
            $items = array_map(function($item) {
                // Восстанавливаем переносы строк
                $item = str_replace('###NEWLINE###', "\n", $item);
                return trim($item);
            }, $items);
            
            $items = array_filter($items, function($item) {
                return !empty($item);
            });
            
            return array_values($items);
        }
    }
    
    // Если нет других разделителей
    $text = str_replace('###NEWLINE###', "\n", $text);
    return [$text];
}

    /**
     * Парсинг целого числа
     */
    private function parseInt($value, $min = 1, $max = 10)
    {
        $int = intval($value);
        $result = max($min, min($max, $int));
        
        Log::debug("Parsed integer:", [
            'original' => $value,
            'parsed' => $result
        ]);
        
        return $result;
    }

    /**
     * Парсинг числа с плавающей точкой
     */
    private function parseFloat($value, $min = 0)
    {
        $float = floatval(str_replace(',', '.', $value));
        $result = max($min, $float);
        
        Log::debug("Parsed float:", [
            'original' => $value,
            'parsed' => $result
        ]);
        
        return $result;
    }

    /**
     * Получить модели для марки
     */
    public function getModels($brandId)
    {
        Log::info('Getting models for brand:', ['brand_id' => $brandId]);
        
        try {
            $models = CarModel::where('brand_id', $brandId)
                ->orderBy('name')
                ->get(['id', 'name']);
            
            Log::info('Models found:', [
                'count' => $models->count(),
                'models' => $models->toArray()
            ]);
            
            return response()->json([
                'success' => true,
                'models' => $models
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting models:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
            
            // Пример данных
            $example = [
                'A2' => 'Не заводится двигатель',
                'B2' => 'Двигатель не запускается при повороте ключа',
                'C2' => 'engine-not-starting',
                'D2' => 'Проверить аккумулятор; Проверить стартер; Проверить топливную систему',
                'E2' => 'Разряженный аккумулятор; Неисправный стартер; Проблемы с топливным насосом',
                'F2' => 'Напряжение аккумулятора; Состояние стартера; Давление топлива',
                'G2' => '3',
                'H2' => '120',
                'I2' => '3500'
            ];
            
            foreach ($example as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }
            
            // Автоширина
            foreach (range('A', 'I') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Сохраняем файл
            $filename = 'symptoms_template_' . date('Y-m-d') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($tempFile);
            
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Error creating template:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания шаблона: ' . $e->getMessage()
            ], 500);
        }
    }
}