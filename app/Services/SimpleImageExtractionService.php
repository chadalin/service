<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;
use Exception;
use Throwable;

class SimpleImageExtractionService
{
    /**
     * Извлечение всех изображений из PDF-документа.
     *
     * @param string $pdfFilePath
     * @param string $imageDirectory
     * @return array
     */
    public function extractAllImages($pdfFilePath, $imageDirectory)
    {
        $allImages = [];
        
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfFilePath);
            $objects = $pdf->getObjects();
            
            Log::info("Total objects found in PDF: " . count($objects));

            foreach ($objects as $object) {
                // ✅ ИСПРАВЛЕНИЕ: Используем \Throwable для надежного перехвата всех ошибок
                try {
                    $details = $object->getDetails();
                    if (isset($details['Subtype']) && $details['Subtype'] === 'Image') {
                        $imageData = $object->getContent();
                        $imageExt = $this->getImageExtensionFromObjectDetails($details, $imageData);
                        
                        if ($imageExt && !empty($imageData)) {
                            $imageName = Str::random(20) . '.' . $imageExt;
                            $fullPath = $imageDirectory . '/' . $imageName;
                            
                            if (Storage::disk('public')->put($fullPath, $imageData)) {
                                $allImages[] = [
                                    'path' => $fullPath,
                                    'url' => Storage::url($fullPath),
                                    'type' => 'embedded'
                                ];
                                Log::info("Successfully extracted image: {$fullPath}");
                            }
                        }
                    }
                } catch (Throwable $e) {
                    Log::warning('Image object processing failed, skipping: ' . $e->getMessage());
                    continue; 
                }
            }
        } catch (Throwable $e) {
            Log::error("Failed to extract images from PDF: " . $e->getMessage());
        }
        
        Log::info("Extracted " . count($allImages) . " images from PDF");
        return $allImages;
    }

    /**
     * Определяет расширение изображения на основе деталей и данных.
     *
     * @param array $details
     * @param string $imageData
     * @return string|null
     */
    protected function getImageExtensionFromObjectDetails($details, $imageData)
    {
        if (isset($details['Filter'])) {
            $filter = $details['Filter'];
            if (is_array($filter)) {
                $filter = end($filter);
            }
            switch ($filter) {
                case 'DCTDecode':
                    return 'jpg';
                case 'FlateDecode':
                case 'LZWDecode':
                    return 'png';
                case 'JPXDecode':
                    return 'jp2';
            }
        }
        
        if (!empty($imageData)) {
            $mimeType = $this->getMimeTypeFromData($imageData);
            if ($mimeType) {
                switch ($mimeType) {
                    case 'image/jpeg':
                        return 'jpg';
                    case 'image/png':
                        return 'png';
                    case 'image/gif':
                        return 'gif';
                    case 'image/bmp':
                        return 'bmp';
                    case 'image/webp':
                        return 'webp';
                }
            }
        }
        
        return null;
    }

    /**
     * Определяет MIME-тип файла по его содержимому.
     *
     * @param string $imageData
     * @return string|null
     */
    protected function getMimeTypeFromData($imageData)
    {
        $bytes = substr($imageData, 0, 4);
        
        if (bin2hex($bytes[0]) === 'ff' && bin2hex($bytes[1]) === 'd8') {
            return 'image/jpeg';
        }
        
        if (bin2hex($bytes[0]) === '89' && substr($bytes, 1) === 'PNG') {
            return 'image/png';
        }
        
        if (substr($bytes, 0, 3) === 'GIF') {
            return 'image/gif';
        }
        
        if (substr($bytes, 0, 2) === 'BM') {
            return 'image/bmp';
        }

        if (substr($bytes, 0, 4) === 'RIFF' && substr($imageData, 8, 4) === 'WEBP') {
             return 'image/webp';
        }

        return null;
    }
}