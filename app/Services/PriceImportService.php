<?php
// app/Services/PriceImportService.php

namespace App\Services;

use App\Models\PriceItem;
use App\Models\Brand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PriceImportService
{
    /**
     * Импорт прайса из файла
     */
    public function importFromFile(string $filePath, int $brandId, bool $updateExisting, bool $matchSymptoms, ?array $columnMapping = null): array
    {
        $result = [
            'items_processed' => 0,
            'items_created' => 0,
            'items_updated' => 0,
            'items_skipped' => 0,
            'symptoms_matched' => 0,
            'errors' => []
        ];

        DB::beginTransaction();

        try {
            $brand = Brand::findOrFail($brandId);
            
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            
            // Определяем заголовки
            $headers = $this->getHeaders($worksheet, $highestColumn);
            
            // Определяем индексы колонок
            $colIndexes = $this->mapColumns($headers, $columnMapping);
            
            // Проверяем обязательные поля
            $this->validateRequiredColumns($colIndexes);
            
            // Обрабатываем строки
            for ($row = 2; $row <= $highestRow; $row++) {
                try {
                    $this->processRow($worksheet, $row, $colIndexes, $brandId, $updateExisting, $matchSymptoms, $result);
                } catch (\Exception $e) {
                    $result['errors'][] = "Row {$row}: " . $e->getMessage();
                    $result['items_skipped']++;
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $result;
    }

    private function getHeaders($worksheet, string $highestColumn): array
    {
        $headers = [];
        for ($col = 'A'; $col <= $highestColumn; $col++) {
            $cellValue = $worksheet->getCell($col . '1')->getValue();
            $headers[$col] = trim((string)$cellValue);
        }
        return $headers;
    }

    private function mapColumns(array $headers, ?array $columnMapping): array
    {
        if ($columnMapping) {
            return $columnMapping;
        }

        // Автоматическое определение колонок
        return [
            'catalog_brand' => $this->findColumnIndex($headers, ['brand', 'бренд', 'производитель']),
            'sku' => $this->findColumnIndex($headers, ['sku', 'артикул', 'код', 'code']),
            'name' => $this->findColumnIndex($headers, ['name', 'название', 'описание', 'наименование']),
            'quantity' => $this->findColumnIndex($headers, ['quantity', 'количество', 'кол-во', 'stock']),
            'price' => $this->findColumnIndex($headers, ['price', 'цена', 'стоимость', 'cost']),
            'unit' => $this->findColumnIndex($headers, ['unit', 'единица', 'ед.']),
            'description' => $this->findColumnIndex($headers, ['описание', 'description', 'детали']),
        ];
    }

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

    private function validateRequiredColumns(array $colIndexes): void
    {
        if ($colIndexes['sku'] === null) {
            throw new \Exception('Не найдена колонка с SKU/артикулом');
        }
        if ($colIndexes['name'] === null) {
            throw new \Exception('Не найдена колонка с названием');
        }
    }

    private function processRow($worksheet, int $row, array $colIndexes, int $brandId, bool $updateExisting, bool $matchSymptoms, array &$result): void
    {
        $rowData = $this->getRowData($worksheet, $row, $colIndexes);
        
        $sku = trim(strtoupper($rowData['sku']));
        $name = $rowData['name'];
        
        if (empty($sku) || empty($name)) {
            $result['items_skipped']++;
            return;
        }

        $priceData = [
            'brand_id' => $brandId,
            'catalog_brand' => $rowData['catalog_brand'],
            'sku' => $sku,
            'name' => $name,
            'quantity' => $this->parseInt($rowData['quantity']),
            'price' => $this->parsePrice($rowData['price']),
            'unit' => $rowData['unit'],
            'description' => $rowData['description'],
        ];

        $existingItem = PriceItem::where('sku', $sku)
            ->where('brand_id', $brandId)
            ->first();

        if ($existingItem) {
            if ($updateExisting) {
                $updateData = $priceData;
                unset($updateData['sku']);
                
                $existingItem->update($updateData);
                $result['items_updated']++;
            } else {
                $result['items_skipped']++;
            }
        } else {
            $priceItem = PriceItem::create($priceData);
            $result['items_created']++;
            
            if ($matchSymptoms) {
                // Здесь логика сопоставления с симптомами
                // $matches = $priceItem->findMatchingSymptoms(0.3);
                // $priceItem->saveSymptomMatches($matches);
                // $result['symptoms_matched'] += count($matches);
            }
        }

        $result['items_processed']++;
    }

    private function getRowData($worksheet, int $row, array $colIndexes): array
    {
        $data = [];
        
        foreach ($colIndexes as $field => $col) {
            if ($col === null) {
                $data[$field] = '';
                continue;
            }
            
            $cell = $worksheet->getCell($col . $row);
            $value = $cell->getValue();
            
            if ($cell->getDataType() === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA) {
                $value = $cell->getCalculatedValue();
            }
            
            if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                $value = $value->getPlainText();
            }
            
            $data[$field] = $value !== null ? trim((string)$value) : '';
        }
        
        return $data;
    }

    private function parseInt($value): int
    {
        if (empty($value)) {
            return 0;
        }
        $cleaned = preg_replace('/[^\d\-]/', '', $value);
        return (int)$cleaned;
    }

    private function parsePrice($value): float
    {
        if (empty($value)) {
            return 0.00;
        }
        $cleaned = str_replace([' ', ','], ['', '.'], $value);
        $cleaned = preg_replace('/[^\d\.\-]/', '', $cleaned);
        return (float)$cleaned;
    }
}