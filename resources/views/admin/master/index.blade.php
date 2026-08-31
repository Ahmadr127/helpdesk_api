@extends('admin.layouts.app')
@section('title', 'Master Data')
@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Dashboard Header with Card/Border -->
    <div class="bg-gradient-to-r from-white to-blue-200 rounded-lg shadow-md p-6 mb-8 border-l-4 border-blue-500">
        <h1 class="text-2xl font-semibold text-gray-800">Master Data</h1>
        <p class="text-gray-600 mt-2">Overview of all master data categories in the system</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Unit Proses Card -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            <div
                class="flex justify-between items-center mb-4 bg-gradient-to-r from-gray-100 to-blue-300 p-4 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800">Unit Proses</h2>
                <a href="{{ route('admin.master.unit-proses.index') }}"
                    class="p-3 rounded-full hover:bg-blue-500 transition-all duration-200">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-green-50 to-blue-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="unitProsesTable">
                        @foreach($unitProses as $unit)
                        <tr class="hover:bg-gradient-to-r hover:from-green-50 hover:to-blue-50 transition-all">
                            <td class="px-6 py-4">{{ $unit->name }}</td>
                            <td class="px-6 py-4">{{ $unit->code }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs rounded-full 
                                    {{ $unit->status ? 'bg-gradient-to-r from-green-100 to-green-200 text-green-800' : 'bg-gradient-to-r from-red-100 to-red-200 text-red-800' }}">
                                    {{ $unit->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ $unit->created_at->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Categories Card -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            <div
                class="flex justify-between items-center mb-4 bg-gradient-to-r from-gray-100 to-blue-300 p-4 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800">Categories</h2>
                <a href="{{ route('admin.master.categories.index') }}"
                    class="p-3 rounded-full hover:bg-green-500 transition-all duration-200">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16m-7 6h7" />
                    </svg>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-green-50 to-blue-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Unit Proses</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($categories as $category)
                        <tr class="hover:bg-gradient-to-r hover:from-green-50 hover:to-blue-50 transition-all">
                            <td class="px-6 py-4">{{ $category->name }}</td>
                            <td class="px-6 py-4">{{ $category->unitProses ? $category->unitProses->name : '-' }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs rounded-full 
                                    {{ $category->status ? 'bg-gradient-to-r from-green-100 to-green-200 text-green-800' : 'bg-gradient-to-r from-red-100 to-red-200 text-red-800' }}">
                                    {{ $category->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ $category->created_at->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Departments Card -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            <div
                class="flex justify-between items-center mb-4 bg-gradient-to-r from-gray-100 to-blue-300 p-4 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800">Departments</h2>
                <a href="{{ route('admin.master.departments.index') }}"
                    class="p-3 rounded-full hover:bg-purple-500 transition-all duration-200">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-green-50 to-blue-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="departmentsTable">
                        @foreach($departments as $department)
                        <tr class="hover:bg-gradient-to-r hover:from-green-50 hover:to-blue-50 transition-all">
                            <td class="px-6 py-4">{{ $department->name }}</td>
                            <td class="px-6 py-4">{{ $department->code }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs rounded-full 
                                    {{ $department->status ? 'bg-gradient-to-r from-green-100 to-green-200 text-green-800' : 'bg-gradient-to-r from-red-100 to-red-200 text-red-800' }}">
                                    {{ $department->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ $department->created_at->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Buildings Card -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            <div
                class="flex justify-between items-center mb-4 bg-gradient-to-r from-gray-100 to-blue-300 p-4 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800">Buildings</h2>
                <a href="{{ route('admin.master.buildings.index') }}"
                    class="p-3 rounded-full hover:bg-yellow-500 transition-all duration-200">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-green-50 to-blue-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="buildingsTable">
                        @foreach($buildings as $building)
                        <tr class="hover:bg-gradient-to-r hover:from-green-50 hover:to-blue-50 transition-all">
                            <td class="px-6 py-4">{{ $building->name }}</td>
                            <td class="px-6 py-4">{{ $building->code }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs rounded-full 
                                    {{ $building->status ? 'bg-gradient-to-r from-green-100 to-green-200 text-green-800' : 'bg-gradient-to-r from-red-100 to-red-200 text-red-800' }}">
                                    {{ $building->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ $building->created_at->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Locations Card -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            <div
                class="flex justify-between items-center mb-4 bg-gradient-to-r from-gray-100 to-blue-300 p-4 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800">Locations</h2>
                <a href="{{ route('admin.master.locations.index') }}"
                    class="p-3 rounded-full hover:bg-indigo-500 transition-all duration-200">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-green-50 to-blue-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Building</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="locationsTable">
                        @foreach($locations as $location)
                        <tr class="hover:bg-gradient-to-r hover:from-green-50 hover:to-blue-50 transition-all">
                            <td class="px-6 py-4">{{ $location->name }}</td>
                            <td class="px-6 py-4">{{ $location->building->name }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs rounded-full 
                                    {{ $location->status ? 'bg-gradient-to-r from-green-100 to-green-200 text-green-800' : 'bg-gradient-to-r from-red-100 to-red-200 text-red-800' }}">
                                    {{ $location->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ $location->created_at->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Positions Card -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            <div class="flex justify-between items-center mb-4 bg-gradient-to-r from-gray-100 to-blue-300 p-4 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800">Positions</h2>
                <a href="{{ route('admin.master.positions.index') }}" class="p-3 rounded-full hover:bg-pink-500 transition-all duration-200">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-green-50 to-blue-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="positionsTable">
                        @foreach($positions as $position)
                        <tr class="hover:bg-gradient-to-r hover:from-green-50 hover:to-blue-50 transition-all">
                            <td class="px-6 py-4">{{ $position->name }}</td>
                            <td class="px-6 py-4">{{ $position->code }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full {{ $position->status ? 'bg-gradient-to-r from-green-100 to-green-200 text-green-800' : 'bg-gradient-to-r from-red-100 to-red-200 text-red-800' }}">
                                    {{ $position->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ $position->created_at->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection