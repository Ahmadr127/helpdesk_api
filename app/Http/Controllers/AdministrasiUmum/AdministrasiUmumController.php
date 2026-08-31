<?php

namespace App\Http\Controllers\AdministrasiUmum;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdministrasiUmumController extends Controller
{
    public function profile()
    {
        return view('administrasi-umum.profile.index', [
            'user' => Auth::user()
        ]);
    }

    public function settings()
    {
        return view('administrasi-umum.settings.index', [
            'user' => Auth::user()
        ]);
    }

    public function notifications()
    {
        return view('administrasi-umum.notifications.index', [
            'user' => Auth::user()
        ]);
    }

    public function dokumen()
    {
        return view('administrasi-umum.dokumen.index', [
            'user' => Auth::user()
        ]);
    }

    public function formulir()
    {
        return view('administrasi-umum.formulir.index', [
            'user' => Auth::user()
        ]);
    }

    public function prosedur()
    {
        return view('administrasi-umum.prosedur.index', [
            'user' => Auth::user()
        ]);
    }
} 