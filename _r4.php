<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
view()->share('errors', new \Illuminate\Support\ViewErrorBag());
$user = \App\Models\User::whereNotNull('department_id')->first();
auth()->login($user);
$ticket = \App\Models\Pengajuan::where('pemohon_id', $user->id)->first() ?? \App\Models\Pengajuan::first();
$categories = \App\Models\KategoriPengajuan::where('status',1)->get();
$locations = \App\Models\Location::where('status',1)->get();
$buildings = \App\Models\Building::where('status',1)->get();
$userDepartment = \App\Models\Department::with(['location','building'])->find($ticket->department_id ?? $user->department_id);
$h = view('user.ticket.edit', compact('ticket','categories','locations','buildings','userDepartment'))->render();
echo "OK ".strlen($h)."\n";
