<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\PriceItem;
use App\Models\Brand;

class PriceImportController extends Controller
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
        return view('admin.price.import');
    }

    public function selectBrand()
    {
        $brands = Brand::orderBy('name')->get(['id', 'name', 'name_cyrillic']);
        
        return view('admin.price.import-select', compact('brands'));
    }

    /**
     * Импорт прайс-листа
     */
    /**
 * Импорт прайс-листа
 */
/**
 * Импорт прайс-листа
 */
public function import(Request $request)
{
    DB::beginTransaction();
    
    try {
        // Правильная валидация для чекбоксов
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv',
            'brand_id' => 'required|exists:brands,id',
            'update_existing' => ['nullable', 'in:0,1,true,false,on,off'], // Допускаем разные форматы
            'match_symptoms' => ['nullable', 'in:0,1,true,false,on,off'],
        ], [
            'excel_file.required' => 'Выберите файл для загрузки',
            'excel_file.file' => 'Загруженный файл не является корректным файлом',
            'excel_file.mimes' => 'Файл должен быть в формате Excel (.xlsx, .xls) или CSV',
            'brand_id.required' => 'Выберите бренд',
            'brand_id.exists' => 'Выбранный бренд не найден',
            'update_existing.in' => 'Поле "Обновлять существующие" должно быть логическим значением',
            'match_symptoms.in' => 'Поле "Сопоставлять с симптомами" должно быть логическим значением',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('excel_file');
        $brandId = $request->input('brand_id');
        
        // Преобразуем значения чекбоксов в boolean
        // Чекбоксы могут приходить как "on" (отмечено), "" (не отмечено), "1"/"0" и т.д.
        $updateExisting = $this->convertCheckboxToBoolean($request->input('update_existing'));
        $matchSymptoms = $this->convertCheckboxToBoolean($request->input('match_symptoms'));
        
        Log::info('Price import parameters:', [
            'brand_id' => $brandId,
            'update_existing_raw' => $request->input('update_existing'),
            'update_existing_bool' => $updateExisting,
            'match_symptoms_raw' => $request->input('match_symptoms'),
            'match_symptoms_bool' => $matchSymptoms,
            'filename' => $file->getClientOriginalName()
        ]);
        
        // Проверяем существование бренда
        $brand = Brand::find($brandId);
        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Бренд не найден'
            ], 404);
        }

        $results = [
            'brand_name' => $brand->name,
            'items_processed' => 0,
            'items_created' => 0,
            'items_updated' => 0,
            'items_skipped' => 0,
            'symptoms_matched' => 0,
            'errors' => [],
            'processing' => true,
            'progress' => 0,
        ];

        // Обработка файла
        $this->processPriceFile($file, $brandId, $updateExisting, $matchSymptoms, $results);

        DB::commit();
        
        Log::info('Price import completed successfully:', $results);

        return response()->json([
            'success' => true,
            'message' => 'Импорт прайс-листа завершен успешно',
            'results' => $results
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('Price import error:', [
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
 * Преобразовать значение чекбокса в boolean
 */
private function convertCheckboxToBoolean($value): bool
{
    if ($value === null || $value === '') {
        return false;
    }
    
    if (is_bool($value)) {
        return $value;
    }
    
    if (is_string($value)) {
        // "on" - стандартное значение для отмеченного чекбокса в HTML
        // "true", "1" - другие возможные значения
        $value = strtolower($value);
        return in_array($value, ['on', 'true', '1', 'yes']);
    }
    
    if (is_numeric($value)) {
        return $value == 1;
    }
    
    return false;
}

    /**
     * Обработка Excel файла с прайс-листом
     */
    private function processPriceFile($file, $brandId, $updateExisting, $matchSymptoms, &$results)
    {
        Log::info('Processing price file...');
        
        $this->checkPhpSpreadsheet();
        
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Получаем размеры файла
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
                'catalog_brand' => $this->findColumnIndex($headers, ['brand', 'бренд', 'производитель', 'manufacturer', 'каталог']),
                'sku' => $this->findColumnIndex($headers, ['sku', 'артикул', 'артикул', 'код', 'code', 'номер']),
                'name' => $this->findColumnIndex($headers, ['name', 'название', 'описание', 'description', 'наименование']),
                'quantity' => $this->findColumnIndex($headers, ['quantity', 'количество', 'кол-во', 'stock', 'наличие']),
                'price' => $this->findColumnIndex($headers, ['price', 'цена', 'стоимость', 'cost']),
                'unit' => $this->findColumnIndex($headers, ['unit', 'единица', 'ед.', 'измерения']),
                'description' => $this->findColumnIndex($headers, ['описание', 'description', 'детали', 'details']),
            ];
            
            Log::info('Column indexes:', $colIndexes);
            
            // Проверяем обязательные поля
            if ($colIndexes['sku'] === null) {
                throw new \Exception('Не найдена колонка с SKU/артикулом');
            }
            
            if ($colIndexes['name'] === null) {
                throw new \Exception('Не найдена колонка с названием');
            }
            
            // Обрабатываем строки
            $batchSize = 100;
            $processed = 0;
            
            for ($row = 2; $row <= $highestRow; $row++) {
                try {
                    // Получаем данные строки
                    $rowData = [];
                    for ($col = 'A'; $col <= $highestColumn; $col++) {
                        $cell = $worksheet->getCell($col . $row);
                        $value = $cell->getValue();
                        
                        if ($cell->getDataType() === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA) {
                            $value = $cell->getCalculatedValue();
                        }
                        
                        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                            $value = $value->getPlainText();
                        }
                        
                        $rowData[$col] = $value !== null ? trim((string)$value) : '';
                    }
                    
                    // Извлекаем значения по колонкам
                    $sku = $this->getCellValue($rowData, $colIndexes['sku']);
                    $name = $this->getCellValue($rowData, $colIndexes['name']);
                    
                    // Пропускаем пустые обязательные поля
                    if (empty($sku) || empty($name)) {
                        $results['items_skipped']++;
                        continue;
                    }
                    
                    // Подготавливаем данные
                    $priceData = [
                        'brand_id' => $brandId,
                        'catalog_brand' => $this->getCellValue($rowData, $colIndexes['catalog_brand']),
                        'sku' => $sku,
                        'name' => $name,
                        'quantity' => $this->parseInt($this->getCellValue($rowData, $colIndexes['quantity'])),
                        'price' => $this->parsePrice($this->getCellValue($rowData, $colIndexes['price'])),
                        'unit' => $this->getCellValue($rowData, $colIndexes['unit']),
                        'description' => $this->getCellValue($rowData, $colIndexes['description']),
                    ];
                    
                    // Ищем существующий товар по SKU
                    $existingItem = PriceItem::where('sku', $sku)->first();
                    
                    if ($existingItem) {
                        // Если существует и разрешено обновление
                        if ($updateExisting) {
                            // Не обновляем SKU и название (по условию задачи)
                            unset($priceData['sku']);
                            unset($priceData['name']);
                            
                            $existingItem->update($priceData);
                            $results['items_updated']++;
                            
                            Log::debug("Updated price item: {$sku}");
                        } else {
                            $results['items_skipped']++;
                            continue;
                        }
                    } else {
                        // Создаем новый товар
                        $priceItem = PriceItem::create($priceData);
                        $results['items_created']++;
                        
                        // Находим совпадения с симптомами
                        if ($matchSymptoms) {
                            $matches = $priceItem->findMatchingSymptoms(0.3);
                            if (!empty($matches)) {
                                $priceItem->saveSymptomMatches($matches);
                                $results['symptoms_matched'] += count($matches);
                            }
                        }
                        
                        Log::debug("Created price item: {$sku}");
                    }
                    
                    $results['items_processed']++;
                    $processed++;
                    
                    // Обновляем прогресс каждые batchSize записей
                    if ($processed % $batchSize === 0) {
                        $results['progress'] = round(($row / $highestRow) * 100);
                        Log::info("Progress: {$results['progress']}%");
                    }
                    
                } catch (\Exception $e) {
                    $errorMsg = "Строка {$row}: " . $e->getMessage();
                    Log::error("Error processing row {$row}:", [
                        'error' => $e->getMessage(),
                        'row_data' => $rowData ?? []
                    ]);
                    
                    $results['errors'][] = $errorMsg;
                    $results['items_skipped']++;
                }
            }
            
            // Завершаем прогресс
            $results['progress'] = 100;
            $results['processing'] = false;
            
            Log::info('Price file processing completed:', [
                'total_processed' => $results['items_processed'],
                'created' => $results['items_created'],
                'updated' => $results['items_updated'],
                'skipped' => $results['items_skipped'],
                'symptoms_matched' => $results['symptoms_matched'],
                'errors_count' => count($results['errors'])
            ]);
            
        } catch (\Exception $e) {
            Log::error('Price file processing error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Ошибка обработки файла прайс-листа: ' . $e->getMessage());
        }
    }

    /**
     * Найти индекс колонки по ключевым словам
     */
    private function findColumnIndex($headers, $keywords)
    {
        foreach ($headers as $col => $header) {
            $headerLower = mb_strtolower($header);
            foreach ($keywords as $keyword) {
                if (mb_strpos($headerLower, mb_strtolower($keyword)) !== false) {
                    return $col;
                }
            }
        }
        return null;
    }

    /**
     * Получить значение ячейки
     */
    private function getCellValue($rowData, $colIndex)
    {
        if ($colIndex === null || !isset($rowData[$colIndex])) {
            return '';
        }
        return $rowData[$colIndex];
    }

    /**
     * Парсинг целого числа
     */
    private function parseInt($value)
    {
        if (empty($value)) {
            return 0;
        }
        
        // Удаляем все нецифровые символы, кроме минуса
        $cleaned = preg_replace('/[^\d\-]/', '', $value);
        return intval($cleaned);
    }

    /**
     * Парсинг цены
     */
    private function parsePrice($value)
    {
        if (empty($value)) {
            return 0.00;
        }
        
        // Заменяем запятые на точки и удаляем пробелы
        $cleaned = str_replace([' ', ','], ['', '.'], $value);
        
        // Удаляем все символы, кроме цифр, точек и минусов
        $cleaned = preg_replace('/[^\d\.\-]/', '', $cleaned);
        
        return floatval($cleaned);
    }

    /**
     * Предварительный просмотр файла
     */
    public function preview(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'excel_file' => 'required|file|mimes:xlsx,xls,csv',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('excel_file');
            $this->checkPhpSpreadsheet();
            
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Получаем первые 5 строк для предварительного просмотра
            $highestRow = min($worksheet->getHighestRow(), 6); // 1 заголовок + 5 строк
            $highestColumn = $worksheet->getHighestColumn();
            
            $previewData = [];
            
            // Заголовки
            $headers = [];
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $cellValue = $worksheet->getCell($col . '1')->getValue();
                $headers[] = [
                    'column' => $col,
                    'value' => trim((string)$cellValue),
                    'suggested_field' => $this->suggestFieldName(trim((string)$cellValue))
                ];
            }
            
            // Данные
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = [];
                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    $cell = $worksheet->getCell($col . $row);
                    $value = $cell->getValue();
                    
                    if ($cell->getDataType() === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA) {
                        $value = $cell->getCalculatedValue();
                    }
                    
                    if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                        $value = $value->getPlainText();
                    }
                    
                    $rowData[$col] = $value !== null ? trim((string)$value) : '';
                }
                $previewData[] = $rowData;
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'headers' => $headers,
                    'preview' => $previewData,
                    'total_rows' => $worksheet->getHighestRow() - 1,
                    'total_columns' => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn)
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Preview error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при предпросмотре файла: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Предложить название поля по заголовку
     */
    private function suggestFieldName($header)
    {
        $headerLower = mb_strtolower($header);
        
        $fieldMapping = [
            'sku' => ['артикул', 'код', 'code', 'номер'],
            'name' => ['название', 'наименование', 'описание', 'description'],
            'catalog_brand' => ['бренд', 'производитель', 'manufacturer', 'каталог'],
            'quantity' => ['количество', 'кол-во', 'stock', 'наличие'],
            'price' => ['цена', 'стоимость', 'cost', 'price'],
            'unit' => ['единица', 'ед.', 'измерения', 'unit'],
            'description' => ['описание', 'детали', 'details', 'комментарий']
        ];
        
        foreach ($fieldMapping as $field => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($headerLower, $keyword) !== false) {
                    return $field;
                }
            }
        }
        
        return 'unknown';
    }

    /**
     * Скачать шаблон Excel для прайс-листа
     */
    public function downloadTemplate()
    {
        try {
            $this->checkPhpSpreadsheet();
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Заголовки с русскими названиями
            $headers = [
                'A1' => 'Каталожный бренд',
                'B1' => 'Артикул (SKU)', 
                'C1' => 'Название запчасти',
                'D1' => 'Количество',
                'E1' => 'Цена',
                'F1' => 'Единица измерения',
                'G1' => 'Описание'
            ];
            
            foreach ($headers as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }
            
            // Пример данных
            $examples = [
                'A2' => 'Original',
                'B2' => 'ABC-123-456',
                'C2' => 'Тормозной диск передний',
                'D2' => '10',
                'E2' => '2450.50',
                'F2' => 'шт',
                'G2' => 'Диск тормозной оригинальный'
            ];
            
            foreach ($examples as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }
            
            // Второй пример
            $examples2 = [
                'A3' => 'Bosch',
                'B3' => 'BOS-789-012',
                'C3' => 'Свеча зажигания',
                'D3' => '25',
                'E3' => '450.75',
                'F3' => 'шт',
                'G3' => 'Свеча зажигания медная'
            ];
            
            foreach ($examples2 as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }
            
            // Третий пример
            $examples3 = [
                'A4' => 'KYB',
                'B4' => 'KYB-345-678',
                'C4' => 'Амортизатор передний',
                'D4' => '5',
                'E4' => '5200.00',
                'F4' => 'шт',
                'G4' => 'Амортизатор газовый'
            ];
            
            foreach ($examples3 as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }
            
            // Автоширина
            foreach (range('A', 'G') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Стили для заголовков
            $headerStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E6E6FA']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                    ]
                ]
            ];
            $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
            
            // Стили для данных
            $dataStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                    ]
                ]
            ];
            $sheet->getStyle('A2:G4')->applyFromArray($dataStyle);
            
            // Формат для цены
            $sheet->getStyle('E2:E4')
                  ->getNumberFormat()
                  ->setFormatCode('#,##0.00 ₽');
            
            // Сохраняем файл
            $filename = 'price_import_template_' . date('Y-m-d') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($tempFile);
            
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Price template error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания шаблона: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Проверка статуса импорта
     */
    public function checkImportStatus()
    {
        // Можно реализовать через Redis или кэш для длительных операций
        return response()->json([
            'success' => true,
            'processing' => false,
            'message' => 'Импорт не выполняется'
        ]);
    }
}