<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;

class UserReportController extends Controller
{
    public function index()
    {
        return view('user.report');
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:bug,feature,other',
            'description' => 'required|string',
            'screenshot' => 'nullable|image|max:2048',
        ]);

        $data = [
            'user_id' => auth()->id(),
            'type' => $request->type,
            'description' => $request->description,
        ];

        if ($request->hasFile('screenshot')) {
            $path = $request->file('screenshot')->store('reports', 'public');
            $data['screenshot'] = $path;
        }

        Report::create($data);

        return redirect()->back()->with('success', 'Report submitted successfully.');
    }
} 