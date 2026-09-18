<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

class KnowledgeBaseController extends Controller
{
    public function index()
    {
        return view('user.knowledge-base.index');
    }
}
