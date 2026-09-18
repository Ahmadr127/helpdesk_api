@extends('admin.layouts.app')
@section('title', 'Master Data')
@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Dashboard Header with Card/Border -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8 border-l-4 border-blue-500">
        <h1 class="text-2xl font-semibold text-gray-800">Master Data</h1>
        <p class="text-gray-600 mt-2">Overview of all master data categories in the system</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Unit Proses Card -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-blue-200">
            <div
                class="flex justify-between items-center mb-4 bg-blue-50 p-4 rounded-lg">
                <h2 class="text-xl font-semibold text-blue-800 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Unit Proses
                </h2>
                <a href="{{ route('admin.master.unit-proses.index') }}" title="Kelola Unit Proses"
                    class="p-3 rounded-lg bg-blue-500 hover:bg-blue-600 transition-all duration-200 shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 5l7 7-7 7M5 12h15" />
                    </svg>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-50">
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
                        <tr class="hover:bg-slate-50 transition-all">
                            <td class="px-6 py-4">{{ $unit->name }}</td>
                            <td class="px-6 py-4">{{ $unit->code }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs rounded-full 
                                    {{ $unit->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
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
        <div class="bg-white rounded-lg shadow-md p-6 border border-green-200">
            <div
                class="flex justify-between items-center mb-4 bg-green-50 p-4 rounded-lg">
                <h2 class="text-xl font-semibold text-green-800 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.29 3.71l1.42 1.42 1.42-1.42 1.42-1.42 1.42 1.42M11 5v4m3 0v3-4 0v1m3 0v5M11 9v5" />
                    </svg>
                    Categories
                </h2>
                <a href="{{ route('admin.master.categories.index') }}" title="Kelola Categories"
                    class="p-3 rounded-lg bg-green-500 hover:bg-green-600 transition-all duration-200 shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 5l7 7-7 7M5 12h15" />
                    </svg>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-50">
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
                        <tr class="hover:bg-slate-50 transition-all">
                            <td class="px-6 py-4">{{ $category->name }}</td>
                            <td class="px-6 py-4">{{ $category->unitProses ? $category->unitProses->name : '-' }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs rounded-full 
                                    {{ $category->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
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
        <div class="bg-white rounded-lg shadow-md p-6 border border-purple-200">
            <div
                class="flex justify-between items-center mb-4 bg-purple-50 p-4 rounded-lg">
                <h2 class="text-xl font-semibold text-purple-800 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3.5 18.5v-2.5a2.5 2.5 0 012.5.536.857M6 20a3 3 0 011.215.886L8.93 22.33a2 2 0 011.215-.886V15M13.69 19.22l1.507-1.507 1.718-.9.002-3.907M8.93 16.83V7.5a2.5 2.5 0 012.5-.536.857L8 6.5a.75.75 0 01.78 0L9.034 9.257" />
                    </svg>
                    Departments
                </h2>
                <a href="{{ route('admin.master.departments.index') }}" title="Kelola Departments"
                    class="p-3 rounded-lg bg-purple-500 hover:bg-purple-600 transition-all duration-200 shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 5l7 7-7 7M5 12h15" />
                    </svg>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-50">
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
                        <tr class="hover:bg-slate-50 transition-all">
                            <td class="px-6 py-4">{{ $department->name }}</td>
                            <td class="px-6 py-4">{{ $department->code }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs rounded-full 
                                    {{ $department->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
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
        <div class="bg-white rounded-lg shadow-md p-6 border border-amber-200">
            <div
                class="flex justify-between items-center mb-4 bg-amber-50 p-4 rounded-lg">
                <h2 class="text-xl font-semibold text-amber-800 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 21v18M8 21v12M16 17v8M3 11h13M8 8v8M16 8v5M3 8v5m9 0v8M8 3h15" />
                    </svg>
                    Buildings
                </h2>
                <a href="{{ route('admin.master.buildings.index') }}" title="Kelola Buildings"
                    class="p-3 rounded-lg bg-amber-500 hover:bg-amber-600 transition-all duration-200 shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 5l7 7-7 7M5 12h15" />
                    </svg>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-50">
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
                        <tr class="hover:bg-slate-50 transition-all">
                            <td class="px-6 py-4">{{ $building->name }}</td>
                            <td class="px-6 py-4">{{ $building->code }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs rounded-full 
                                    {{ $building->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
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
        <div class="bg-white rounded-lg shadow-md p-6 border border-indigo-200">
            <div
                class="flex justify-between items-center mb-4 bg-indigo-50 p-4 rounded-lg">
                <h2 class="text-xl font-semibold text-indigo-800 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20.354 8.858l1.002-.354m-5.004 1.5l1.002.354m-1.002-5.354 1.504-.354m5.504-8.146l1.613 1.389m-12.789-2.853 4.285 2.893m5.002-1.704 1.641-.635" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.292 3.713l2.125 2.125M9.292.375h4.416m8.958-8.458 2.125 2.125" />
                    </svg>
                    Locations
                </h2>
                <a href="{{ route('admin.master.locations.index') }}" title="Kelola Locations"
                    class="p-3 rounded-lg bg-indigo-500 hover:bg-indigo-600 transition-all duration-200 shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 5l7 7-7 7M5 12h15" />
                    </svg>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="locationsTable">
                        @foreach($locations as $location)
                        <tr class="hover:bg-slate-50 transition-all">
                            <td class="px-6 py-4">{{ $location->name }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs rounded-full 
                                    {{ $location->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
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
        <div class="bg-white rounded-lg shadow-md p-6 border border-pink-200">
            <div class="flex justify-between items-center mb-4 bg-pink-50 p-4 rounded-lg">
                <h2 class="text-xl font-semibold text-pink-800 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.5 12a3.5 3.5 0 11-7 0h7M15.5 12v7a5 5 0 010-7 0h7M15.5 12a4 4 0 01-7 0h7M15.5 12v7a4 4 0 01-7 0h7" />
                    </svg>
                    Positions
                </h2>
                <a href="{{ route('admin.master.positions.index') }}" title="Kelola Positions" class="p-3 rounded-lg bg-pink-500 hover:bg-pink-600 transition-all duration-200 shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 12h15" />
                    </svg>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="positionsTable">
                        @foreach($positions as $position)
                        <tr class="hover:bg-slate-50 transition-all">
                            <td class="px-6 py-4">{{ $position->name }}</td>
                            <td class="px-6 py-4">{{ $position->code }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full {{ $position->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
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
    <!-- Kategori Order Card -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-teal-200">
            <div class="flex justify-between items-center mb-4 bg-teal-50 p-4 rounded-lg">
                <h2 class="text-xl font-semibold text-teal-800 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.5 11.5l-1.5 1.5 1.5 1.5 1.5-1.5 1.5-1.5M6.5 11.5h6.5m7.5 11.5a3 3 0 013.5-1.5l.707.707m.414-.414M11.5 6.5v2" />
                    </svg>
                    Kategori Order
                </h2>
                <a href="{{ route('admin.master.kategori-order.index') }}" title="Kelola Kategori Order" class="p-3 rounded-lg bg-teal-500 hover:bg-teal-600 transition-all duration-200 shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 12h15" />
                    </svg>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="kategoriOrderTable">
                        @foreach($kategoriOrders as $kategori)
                        <tr class="hover:bg-slate-50 transition-all">
                            <td class="px-6 py-4">{{ $kategori->name }}</td>
                            <td class="px-6 py-4">{{ $kategori->code ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full {{ $kategori->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $kategori->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ $kategori->created_at->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection