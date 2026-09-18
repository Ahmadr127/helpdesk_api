<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

class InformationController extends Controller
{
    public function index()
    {
        return view('user.information.index');
    }
}
