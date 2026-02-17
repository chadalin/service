<?php
// app/Console/Commands/CheckEmailPrices.php

namespace App\Console\Commands;

use App\Models\EmailAccount;
use App\Models\PriceImportLog;
use App\Services\EmailPriceImporter;
use Illuminate\Console\Command;

class CheckEmailPrices extends Command
{
    protected $signature = 'prices:check-email {--account= : Check specific account by ID}';
    protected $description = 'Check email accounts for price lists and import them';

    private EmailPriceImporter $importer;

    public function __construct(EmailPriceImporter $importer)
    {
        parent::__construct();
        $this->importer = $importer;
    }

    public function handle()
    {
        $accountId = $this->option('account');
        
        if ($accountId) {
            $account = EmailAccount::find($accountId);
            if (!$account) {
                $this->error("Account with ID {$accountId} not found");
                return 1;
            }
            $this->processAccount($account);
        } else {
            $accounts = EmailAccount::where('is_active', true)->get();
            
            foreach ($accounts as $account) {
                $this->info("Checking account: {$account->name} ({$account->email})");
                $this->processAccount($account);
            }
        }

        return 0;
    }

    private function processAccount(EmailAccount $account)
    {
        try {
            $result = $this->importer->importFromAccount($account);
            
            $this->info("Processed: {$result['processed']} emails");
            $this->info("Imported: {$result['imported']} files");
            $this->info("Errors: {$result['errors']}");
            
            $account->update(['last_checked_at' => now()]);
            
        } catch (\Exception $e) {
            $this->error("Error processing account {$account->name}: {$e->getMessage()}");
            
            PriceImportLog::create([
                'email_account_id' => $account->id,
                'status' => 'error',
                'error_message' => $e->getMessage()
            ]);
        }
    }
}