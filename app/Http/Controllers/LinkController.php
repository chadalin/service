<?php
// app/Http/Controllers/LinkController.php

namespace App\Http\Controllers;

use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LinkController extends Controller
{
    public function index()
    {
        $links = Link::latest()->paginate(10);
        return view('links.index', compact('links'));
    }

    public function create()
    {
        return view('links.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'url' => 'required|url',
            'description' => 'nullable|string',
            'login' => 'nullable|string',
            'password' => 'nullable|string',
            'auth_type' => 'required|in:basic,form,none'
        ]);

        Link::create($validated);

        return redirect()->route('links.index')
            ->with('success', 'Ссылка успешно добавлена');
    }

    public function edit(Link $link)
    {
        return view('links.edit', compact('link'));
    }

    public function update(Request $request, Link $link)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'url' => 'required|url',
            'description' => 'nullable|string',
            'login' => 'nullable|string',
            'password' => 'nullable|string',
            'auth_type' => 'required|in:basic,form,none'
        ]);

        $link->update($validated);

        return redirect()->route('links.index')
            ->with('success', 'Ссылка успешно обновлена');
    }

    public function destroy(Link $link)
    {
        $link->delete();

        return redirect()->route('links.index')
            ->with('success', 'Ссылка успешно удалена');
    }

    public function redirect(Link $link)
    {
        // Для Basic Auth
        if ($link->auth_type === 'basic' && $link->login && $link->password) {
            $url = $link->url;
            
            // Добавляем credentials в URL для Basic Auth
            if (parse_url($url, PHP_URL_SCHEME) && parse_url($url, PHP_URL_HOST)) {
                $parsed = parse_url($url);
                $scheme = $parsed['scheme'] ?? 'https';
                $host = $parsed['host'];
                $path = $parsed['path'] ?? '/';
                $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
                
                $url = "{$scheme}://{$link->login}:{$link->password}@{$host}{$path}{$query}";
            }
            
            return redirect($url);
        }
        
        // Для Form-based Auth - проксируем запрос
        if ($link->auth_type === 'form' && $link->login && $link->password) {
            return $this->handleFormAuth($link);
        }

        // Обычный редирект
        return redirect($link->url);
    }

    protected function handleFormAuth(Link $link)
    {
        // Здесь логика для разных сайтов
        // Можно расширять под конкретные сервисы
        
        $domain = $link->domain;
        
        switch ($domain) {
            case 'ccc.hyundai.com':
            case 'digitalpdi.hmc.co.kr':
                // Пример для Hyundai/Kia
                $response = Http::withOptions([
                    'verify' => false,
                ])->asForm()->post('https://' . $domain . '/login', [
                    'username' => $link->login,
                    'password' => $link->password,
                    'remember' => 'on'
                ]);
                
                $cookies = $response->cookies();
                
                return redirect($link->url)->withCookies(
                    collect($cookies)->mapWithKeys(function ($cookie) {
                        return [$cookie->getName() => $cookie->getValue()];
                    })->toArray()
                );
                
            case 'fed-dms-web-prod-public-centralasia.chehejia.com':
                // Пример для Chehejia
                $response = Http::post('https://' . $domain . '/api/auth/login', [
                    'account' => $link->login,
                    'password' => $link->password
                ]);
                
                if ($response->successful()) {
                    $token = $response->json('data.token');
                    return redirect($link->url)->withCookie(
                        cookie('token', $token, 120)
                    );
                }
                break;
        }
        
        // Если не смогли авторизоваться, просто редиректим
        return redirect($link->url);
    }
}