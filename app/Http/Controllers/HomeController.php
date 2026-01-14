<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
     /**
     * Главная посадочная страница (B2C)
     */
    public function index()
    {
        return view('page.lending');
    }
 
    public function landing()
    {
        return view('page.lendingd');
    }
}
