<?php
// app/Http/Controllers/Admin/EmailPriceSettingsController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailAccount;
use App\Models\PriceImportRule;
use App\Models\Brand;
use App\Models\PriceImportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class EmailPriceSettingsController extends Controller
{
    /**
     * Список почтовых аккаунтов
     */
    public function index()
    {
        $accounts = EmailAccount::with('rules.brand')->get();
        return view('admin.email-prices.index', compact('accounts'));
    }

    /**
     * Форма создания аккаунта
     */
    public function createAccount()
    {
        return view('admin.email-prices.create-account');
    }

    /**
     * Сохранение аккаунта
     */
    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'imap_host' => 'required|string|max:255',
            'imap_port' => 'required|integer|min:1|max:65535',
            'imap_encryption' => 'nullable|in:ssl,tls,',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'mailbox' => 'required|string|max:255',
            'check_interval' => 'required|integer|min:1',
            'is_active' => 'boolean'
        ]);

        $validated['password'] = Crypt::encryptString($validated['password']);
        
        EmailAccount::create($validated);

        return redirect()->route('admin.email-prices.index')
            ->with('success', 'Почтовый аккаунт создан');
    }

    /**
     * Форма редактирования аккаунта
     */
    public function editAccount(EmailAccount $account)
    {
        return view('admin.email-prices.edit-account', compact('account'));
    }

    /**
     * Обновление аккаунта
     */
    public function updateAccount(Request $request, EmailAccount $account)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'imap_host' => 'required|string|max:255',
            'imap_port' => 'required|integer|min:1|max:65535',
            'imap_encryption' => 'nullable|in:ssl,tls,',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string|max:255',
            'mailbox' => 'required|string|max:255',
            'check_interval' => 'required|integer|min:1',
            'is_active' => 'boolean'
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Crypt::encryptString($validated['password']);
        } else {
            unset($validated['password']);
        }

        $account->update($validated);

        return redirect()->route('admin.email-prices.index')
            ->with('success', 'Почтовый аккаунт обновлен');
    }

    /**
     * Удаление аккаунта
     */
    public function destroyAccount(EmailAccount $account)
    {
        $account->delete();

        return redirect()->route('admin.email-prices.index')
            ->with('success', 'Почтовый аккаунт удален');
    }

    /**
     * Создание правила для аккаунта
     */
    public function createRule(EmailAccount $account)
    {
        $brands = Brand::orderBy('name')->get();
        return view('admin.email-prices.create-rule', compact('account', 'brands'));
    }

    /**
     * Сохранение правила
     */


public function storeRule(Request $request)
{
    try {
        // Получаем account_id из запроса
        $accountId = $request->input('account_id');
        
        if (!$accountId) {
            throw new \Exception('ID аккаунта не передан');
        }

        $account = EmailAccount::findOrFail($accountId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand_id' => 'required|string|exists:brands,id',
            'email_subject_pattern' => 'nullable|string',
            'email_sender_pattern' => 'nullable|string',
            'filename_patterns' => 'nullable|array',
            'filename_patterns.*' => 'string',
            'update_existing' => 'sometimes|boolean',
            'match_symptoms' => 'sometimes|boolean',
            'priority' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean'
        ]);

        // Обработка чекбоксов
        $validated['update_existing'] = $request->has('update_existing');
        $validated['match_symptoms'] = $request->has('match_symptoms');
        $validated['is_active'] = $request->has('is_active');
        $validated['priority'] = $validated['priority'] ?? 0;
        
        // Преобразуем паттерны
        if (isset($validated['filename_patterns'])) {
            $validated['filename_patterns'] = array_map(function($pattern) {
                if (!preg_match('/^\/.*\/[a-z]*$/i', $pattern)) {
                    $pattern = '/' . str_replace('/', '\/', $pattern) . '/i';
                }
                return $pattern;
            }, $validated['filename_patterns']);
        }

        // Добавляем email_account_id
        $validated['email_account_id'] = $account->id;

        // Создаем правило
        $rule = PriceImportRule::create($validated);

        return redirect()->route('admin.email-prices.index')
            ->with('success', 'Правило импорта успешно создано');

    } catch (\Exception $e) {
        \Log::error('Error creating price import rule:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request' => $request->all(),
            'account_id' => $request->input('account_id')
        ]);

        return back()
            ->withInput()
            ->withErrors(['error' => 'Ошибка при создании правила: ' . $e->getMessage()]);
    }
}
    /**
     * Удаление правила
     */
    public function destroyRule(PriceImportRule $rule)
    {
        $rule->delete();

        return redirect()->route('admin.email-prices.index')
            ->with('success', 'Правило импорта удалено');
    }

    /**
     * Логи импорта
     */
    public function logs(Request $request)
    {
        $query = PriceImportLog::with(['emailAccount', 'rule', 'brand'])
            ->orderBy('created_at', 'desc');

        if ($request->has('account_id')) {
            $query->where('email_account_id', $request->account_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $logs = $query->paginate(50);

        $accounts = EmailAccount::pluck('name', 'id');

        return view('admin.email-prices.logs', compact('logs', 'accounts'));
    }

    /**
     * Ручной запуск проверки для аккаунта
     */
    public function checkNow(EmailAccount $account)
    {
        try {
            $importer = app(\App\Services\EmailPriceImporter::class);
            $result = $importer->importFromAccount($account);

            return response()->json([
                'success' => true,
                'message' => "Проверка завершена. Обработано: {$result['processed']}, импортировано: {$result['imported']}, ошибок: {$result['errors']}"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ], 500);
        }
    }
}