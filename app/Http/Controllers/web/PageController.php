<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;

class PageController extends Controller
{
    public function home()
    {
        return view('home');
    }
}
