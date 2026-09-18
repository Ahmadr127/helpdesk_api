<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

class FAQController extends Controller
{
    public function index()
    {
        return view('user.faq.index');
    }
}
