@extends('admin.layouts.app')

@section('title', 'Edit Tiket')

@section('content')
<div class="w-full">
    <div class="w-full">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-400 px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Edit Tiket #{{ $ticket->nomor }}
                </h2>
                <a href="{{ route('admin.tickets.myticket') }}" class="inline-flex items-center text-white hover:text-blue-100 transition-colors bg-blue-700 hover:bg-blue-800 px-3 py-2 rounded-md">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali
                </a>
            </div>
            <div class="p-6">
                @if($errors->any())
                    <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                        <ul class="list-disc list-inside text-sm">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @include('user.pengajuan._form', [
                    'action' => route('user.pengajuan.update', $ticket),
                    'method' => 'PUT',
                    'accent' => 'blue',
                    'pengajuan' => $ticket,
                    'kategoris' => $categories,
                    'locations' => $locations,
                    'buildings' => $buildings,

                    'userDepartment' => $userDepartment,
                    'submitLabel' => 'Perbarui Tiket',
                    'cancelUrl' => route('admin.tickets.myticket'),
                    'showOrderFields' => false,
                ])
            </div>
        </div>
    </div>
</div>
@endsection