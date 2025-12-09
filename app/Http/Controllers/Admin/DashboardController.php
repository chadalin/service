<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Document;
use App\Models\User;
use App\Models\SearchQuery;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users_count' => User::count(),
            'brands_count' => Brand::count(),
            'documents_count' => Document::count(),
            'searches_count' => SearchQuery::count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}