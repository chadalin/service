<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsExpert
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if (!$user || (!$user->is_expert && !$user->is_admin)) {
            abort(403, 'Доступ только для экспертов');
        }
        
        return $next($request);
    }
}