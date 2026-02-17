<?php
// app/Services/EmailPriceImporter.php

namespace App\Services;

use App\Models\EmailAccount;
use App\Models\PriceImportRule;
use App\Models\PriceImportLog;
use App\Models\PriceItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpImap\Mailbox;

class EmailPriceImporter
{
    private PriceImportService $priceImportService;
    
    public function __construct(PriceImportService $priceImportService)
    {
        $this->priceImportService = $priceImportService;
    }

    /**
     * Импорт прайсов из почтового аккаунта
     */
    public function importFromAccount(EmailAccount $account): array
    {
        $result = [
            'processed' => 0,
            'imported' => 0,
            'errors' => 0,
            'details' => []
        ];

        try {
            // Подключение к почтовому ящику
            $mailbox = $this->connectToMailbox($account);
            
            // Поиск непрочитанных писем
            $mailsIds = $mailbox->searchMailbox('UNSEEN');
            
            if (empty($mailsIds)) {
                Log::info("No new emails for account {$account->name}");
                return $result;
            }

            foreach ($mailsIds as $mailId) {
                try {
                    $this->processEmail($mailbox, $mailId, $account, $result);
                    $result['processed']++;
                    
                    // Помечаем письмо как прочитанное
                    $mailbox->markMailAsRead($mailId);
                    
                } catch (\Exception $e) {
                    $result['errors']++;
                    Log::error("Error processing email {$mailId}: {$e->getMessage()}");
                    
                    PriceImportLog::create([
                        'email_account_id' => $account->id,
                        'status' => 'error',
                        'error_message' => $e->getMessage()
                    ]);
                }
            }

            $mailbox->disconnect();

        } catch (\Exception $e) {
            Log::error("Error connecting to mailbox {$account->name}: {$e->getMessage()}");
            throw $e;
        }

        return $result;
    }

    /**
     * Подключение к почтовому ящику
     */
    private function connectToMailbox(EmailAccount $account): Mailbox
    {
        $mailbox = new Mailbox(
            "{{$account->imap_host}:{$account->imap_port}/imap/{$account->imap_encryption}}{$account->mailbox}",
            $account->username,
            decrypt($account->password) // предполагаем, что пароль зашифрован
        );

        return $mailbox;
    }

    /**
     * Обработка одного письма
     */
    private function processEmail($mailbox, $mailId, EmailAccount $account, array &$result): void
    {
        $email = $mailbox->getMail($mailId);
        
        $subject = $email->subject ?? '';
        $from = $email->fromAddress ?? '';
        
        Log::info("Processing email", [
            'subject' => $subject,
            'from' => $from,
            'attachments_count' => count($email->getAttachments())
        ]);

        // Ищем подходящее правило
        $rule = PriceImportRule::where('email_account_id', $account->id)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get()
            ->first(function ($rule) use ($subject, $from) {
                return $rule->matchesEmail($subject, $from);
            });

        if (!$rule) {
            Log::info("No matching rule for email", ['subject' => $subject]);
            return;
        }

        // Обрабатываем вложения
        foreach ($email->getAttachments() as $attachment) {
            if (!$rule->matchesFilename($attachment->name)) {
                Log::info("Attachment {$attachment->name} does not match rule patterns");
                continue;
            }

            try {
                $importResult = $this->importAttachment($attachment, $rule, $subject, $from);
                $result['imported']++;
                $result['details'][] = [
                    'filename' => $attachment->name,
                    'result' => $importResult
                ];

            } catch (\Exception $e) {
                $result['errors']++;
                Log::error("Error importing attachment {$attachment->name}: {$e->getMessage()}");
                
                PriceImportLog::create([
                    'email_account_id' => $account->id,
                    'price_import_rule_id' => $rule->id,
                    'brand_id' => $rule->brand_id,
                    'email_subject' => $subject,
                    'email_from' => $from,
                    'filename' => $attachment->name,
                    'status' => 'error',
                    'error_message' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Импорт вложения
     */
    private function importAttachment($attachment, PriceImportRule $rule, string $subject, string $from): array
    {
        // Сохраняем файл временно
        $tempPath = storage_path('app/temp/' . uniqid() . '_' . $attachment->name);
        file_put_contents($tempPath, $attachment->getContents());

        try {
            // Импортируем прайс
            $result = $this->priceImportService->importFromFile(
                $tempPath,
                $rule->brand_id,
                $rule->update_existing,
                $rule->match_symptoms,
                $rule->column_mapping
            );

            // Логируем успешный импорт
            PriceImportLog::create([
                'email_account_id' => $rule->email_account_id,
                'price_import_rule_id' => $rule->id,
                'brand_id' => $rule->brand_id,
                'email_subject' => $subject,
                'email_from' => $from,
                'filename' => $attachment->name,
                'status' => 'success',
                'items_processed' => $result['items_processed'] ?? 0,
                'items_created' => $result['items_created'] ?? 0,
                'items_updated' => $result['items_updated'] ?? 0,
                'items_skipped' => $result['items_skipped'] ?? 0,
                'details' => $result
            ]);

            // Обновляем время последней обработки правила
            $rule->update(['last_processed_at' => now()]);

            return $result;

        } finally {
            // Удаляем временный файл
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }
}