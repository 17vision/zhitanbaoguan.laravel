<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;

class PageController extends Controller
{
    public function home()
    {
        return view('home');
    }

    public function getUser()
    {
        return view('activity.user');
    }
    public function storeUser()
    {
        return 123;
    }
}
