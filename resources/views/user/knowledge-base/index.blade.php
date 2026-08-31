@extends('user.layouts.app')

@section('title', 'Knowledge Base')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Knowledge Base</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Category Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-xl font-semibold mb-4">Getting Started</h3>
                <ul class="space-y-2">
                    <li>
                        <a href="#" class="text-blue-600 hover:underline">How to Register</a>
                    </li>
                    <li>
                        <a href="#" class="text-blue-600 hover:underline">First Time Patient Guide</a>
                    </li>
                    <li>
                        <a href="#" class="text-blue-600 hover:underline">Insurance Information</a>
                    </li>
                </ul>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-xl font-semibold mb-4">Services</h3>
                <ul class="space-y-2">
                    <li>
                        <a href="#" class="text-blue-600 hover:underline">Available Treatments</a>
                    </li>
                    <li>
                        <a href="#" class="text-blue-600 hover:underline">Specialist Consultations</a>
                    </li>
                    <li>
                        <a href="#" class="text-blue-600 hover:underline">Emergency Services</a>
                    </li>
                </ul>
            </div>

            <!-- Add more category cards as needed -->
        </div>
    </div>
</div>
@endsection