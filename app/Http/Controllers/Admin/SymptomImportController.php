<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Diagnostic\Symptom;
use App\Models\Diagnostic\Rule;
use App\Models\Brand;
use App\Models\CarModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SymptomImportController extends Controller
{
    public function index()
    {
        return view('admin.diagnostic.symptom-import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
                throw new \Exception('Для импорта Excel файлов требуется установить PhpSpreadsheet. Выполните: composer require phpoffice/phpspreadsheet');
            }

            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Удаляем заголовок
            $header = array_shift($rows);
            
            $results = [
                'symptoms_created' => 0,
                'symptoms_updated' => 0,
                'rules_created' => 0,
                'rules_updated' => 0,
                'errors' => [],
                'total_rows' => count($rows),
            ];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                
                try {
                    // Пропускаем пустые строки
                    if (empty(array_filter($row, function($value) {
                        return !is_null($value) && $value !== '';
                    }))) {
                        continue;
                    }

                    // 1. Обработка симптома
                    $symptomName = trim($row[0] ?? '');
                    if (empty($symptomName)) {
                        $results['errors'][] = "Строка {$rowNumber}: Отсутствует название симптома";
                        continue;
                    }

                    $symptomData = [
                        'name' => $symptomName,
                        'description' => trim($row[1] ?? ''),
                        'slug' => $this->generateSlug(trim($row[2] ?? ''), $symptomName),
                        'is_active' => true,
                    ];

                    // Создаем или обновляем симптом
                    $symptom = Symptom::updateOrCreate(
                        ['slug' => $symptomData['slug']],
                        $symptomData
                    );

                    if ($symptom->wasRecentlyCreated) {
                        $results['symptoms_created']++;
                    } else {
                        $results['symptoms_updated']++;
                    }

                    // 2. Обработка правила (если указан бренд)
                    $brandName = trim($row[3] ?? '');
                    if (!empty($brandName)) {
                        // Ищем бренд
                        $brand = Brand::where('name', $brandName)
                            ->orWhere('name_cyrillic', $brandName)
                            ->first();

                        if (!$brand) {
                            // Пропускаем ошибку но продолжаем импорт
                            $results['errors'][] = "Строка {$rowNumber}: Бренд '{$brandName}' не найден. Создается симптом без правила.";
                            continue;
                        }

                        // Ищем модель если указана
                        $modelId = null;
                        $modelName = trim($row[4] ?? '');
                        if (!empty($modelName)) {
                            $model = CarModel::where('brand_id', $brand->id)
                                ->where(function($query) use ($modelName) {
                                    $query->where('name', $modelName)
                                          ->orWhere('name_cyrillic', $modelName);
                                })
                                ->first();

                            if ($model) {
                                $modelId = $model->id;
                            }
                        }

                        // Подготавливаем данные для правила
                        $ruleData = [
                            'symptom_id' => $symptom->id,
                            'brand_id' => $brand->id,
                            'model_id' => $modelId,
                            'diagnostic_steps' => $this->parseTextToArray(trim($row[5] ?? '')),
                            'possible_causes' => $this->parseTextToArray(trim($row[6] ?? '')),
                            'required_data' => $this->parseTextToArray(trim($row[7] ?? '')),
                            'complexity_level' => $this->parseInt($row[8] ?? 1, 1, 10),
                            'estimated_time' => $this->parseInt($row[9] ?? 60, 10, 480),
                            'base_consultation_price' => $this->parseFloat($row[10] ?? 3000, 0),
                            'is_active' => true,
                        ];

                        // Создаем или обновляем правило
                        $rule = Rule::updateOrCreate(
                            [
                                'symptom_id' => $symptom->id,
                                'brand_id' => $brand->id,
                                'model_id' => $modelId,
                            ],
                            $ruleData
                        );

                        if ($rule->wasRecentlyCreated) {
                            $results['rules_created']++;
                        } else {
                            $results['rules_updated']++;
                        }
                    }

                } catch (\Exception $e) {
                    $results['errors'][] = "Строка {$rowNumber}: " . $e->getMessage();
                    Log::error('Import error at row ' . $rowNumber, [
                        'error' => $e->getMessage(),
                        'row' => $row
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Импорт завершен',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Import failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при импорте: ' . $e->getMessage(),
                'debug' => env('APP_DEBUG') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Генерация slug из строки или названия
     */
    private function generateSlug($slug, $name)
    {
        if (!empty($slug)) {
            return \Illuminate\Support\Str::slug($slug);
        }
        
        return \Illuminate\Support\Str::slug($name);
    }

    /**
     * Парсинг текста в массив (поддерживает JSON и простой текст)
     */
    private function parseTextToArray($text)
    {
        if (empty($text)) {
            return [];
        }

        $text = trim($text);
        
        // Пытаемся декодировать как JSON
        if (str_starts_with($text, '[') || str_starts_with($text, '{')) {
            $decoded = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Если не JSON, разбиваем по разделителям
        $items = [];
        
        // Пробуем разные разделители
        if (strpos($text, ';') !== false) {
            $items = array_map('trim', explode(';', $text));
        } elseif (strpos($text, ',') !== false) {
            $items = array_map('trim', explode(',', $text));
        } elseif (strpos($text, '|') !== false) {
            $items = array_map('trim', explode('|', $text));
        } elseif (strpos($text, "\n") !== false) {
            $items = array_map('trim', explode("\n", $text));
        } else {
            $items = [$text];
        }

        // Фильтруем пустые значения
        $items = array_filter($items, function($item) {
            return !empty($item);
        });

        // Если это строки с цифрами, преобразуем в массив
        return array_values($items);
    }

    /**
     * Парсинг целого числа с проверкой диапазона
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

    public function downloadTemplate()
    {
        $filename = 'symptoms_import_template_' . date('Y-m-d') . '.xlsx';
        
        try {
            if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                throw new \Exception('PhpSpreadsheet не установлен. Установите: composer require phpoffice/phpspreadsheet');
            }

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Заголовки
            $headers = [
                'symptom_name',
                'symptom_description', 
                'symptom_slug',
                'brand',
                'model',
                'diagnostic_steps',
                'possible_causes',
                'required_data',
                'complexity_level',
                'estimated_time',
                'consultation_price'
            ];
            
            // Записываем заголовки
            foreach ($headers as $col => $header) {
                $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            }
            
            // Примеры данных (используем простой текст вместо JSON)
            $examples = [
                [
                    'Не заводится двигатель',
                    'Двигатель не запускается при повороте ключа',
                    'engine-not-starting',
                    'Toyota',
                    'Camry',
                    'Проверить аккумулятор; Проверить стартер; Проверить топливную систему',
                    'Разряженный аккумулятор; Неисправный стартер; Проблемы с топливным насосом',
                    'Напряжение аккумулятора; Состояние стартера; Давление топлива',
                    3,
                    120,
                    3500
                ],
                [
                    'Стук в двигателе',
                    'Металлический стук при работе двигателя',
                    'engine-knocking',
                    'Honda',
                    'Civic',
                    'Проверить уровень масла; Диагностика подшипников; Проверка гидрокомпенсаторов',
                    'Низкий уровень масла; Износ шатунных подшипников; Неисправные гидрокомпенсаторы',
                    'Уровень и состояние масла; Звуковая диагностика; Давление масла',
                    5,
                    180,
                    5000
                ]
            ];
            
            // Записываем примеры
            foreach ($examples as $row => $example) {
                foreach ($example as $col => $value) {
                    $sheet->setCellValueByColumnAndRow($col + 1, $row + 3, $value);
                }
            }
            
            // Настраиваем ширину колонок
            foreach (range('A', 'K') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Стили для заголовков
            $headerStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => 'E6E6FA']
                ]
            ];
            $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);
            
            // Стили для примеров
            $exampleStyle = [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => 'F0FFF0']
                ]
            ];
            $sheet->getStyle('A3:K4')->applyFromArray($exampleStyle);
            
            // Добавляем пояснения
            $sheet->setCellValue('A6', 'Инструкция:');
            $sheet->setCellValue('A7', '1. diagnostic_steps, possible_causes, required_data: используйте точку с запятой (;) для разделения элементов');
            $sheet->setCellValue('A8', '2. brand: должен существовать в базе данных (таблица brands)');
            $sheet->setCellValue('A9', '3. model: опционально, должен существовать для указанного бренда');
            $sheet->setCellValue('A10', '4. complexity_level: от 1 до 10');
            $sheet->setCellValue('A11', '5. estimated_time: в минутах');
            $sheet->setCellValue('A12', '6. consultation_price: в рублях');
            
            // Стиль для инструкции
            $instructionStyle = [
                'font' => ['italic' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => 'FFF8DC']
                ]
            ];
            $sheet->getStyle('A6:A12')->applyFromArray($instructionStyle);
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $writer->save('php://output');
            exit;
            
        } catch (\Exception $e) {
            // Fallback: создаем CSV файл если Excel не работает
            $this->downloadCSVTemplate();
        }
    }

    /**
     * Fallback: скачивание CSV шаблона
     */
    private function downloadCSVTemplate()
    {
        $filename = 'symptoms_import_template_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // BOM для UTF-8
            
            // Заголовки
            fputcsv($handle, [
                'symptom_name',
                'symptom_description', 
                'symptom_slug',
                'brand',
                'model',
                'diagnostic_steps',
                'possible_causes',
                'required_data',
                'complexity_level',
                'estimated_time',
                'consultation_price'
            ], ';');
            
            // Примеры
            fputcsv($handle, [
                'Не заводится двигатель',
                'Двигатель не запускается при повороте ключа',
                'engine-not-starting',
                'Toyota',
                'Camry',
                'Проверить аккумулятор; Проверить стартер; Проверить топливную систему',
                'Разряженный аккумулятор; Неисправный стартер; Проблемы с топливным насосом',
                'Напряжение аккумулятора; Состояние стартера; Давление топлива',
                3,
                120,
                3500
            ], ';');
            
            fputcsv($handle, [
                'Стук в двигателе',
                'Металлический стук при работе двигателя',
                'engine-knocking',
                'Honda',
                'Civic',
                'Проверить уровень масла; Диагностика подшипников; Проверка гидрокомпенсаторов',
                'Низкий уровень масла; Износ шатунных подшипников; Неисправные гидрокомпенсаторы',
                'Уровень и состояние масла; Звуковая диагностика; Давление масла',
                5,
                180,
                5000
            ], ';');
            
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}