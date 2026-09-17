@extends('admin.layouts.app')

@section('title', 'Manajemen Permission')

@push('styles')
<style>
[x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-4">
    {{-- <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Manajemen Permission</h1>
            <p class="text-sm text-gray-500">Kelola hak akses berbasis role</p>
        </div>
        <a href="{{ route('admin.permissions.index') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">Refresh</a>
    </div> --}}

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">{{ session('error') }}</div>
    @endif

    <!-- Role Permissions with Search Select -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6"
         x-data="{
            open: false,
            search: '',
            selectedKey: '',
            selectedLabel: '',
            allRoles: @js($roles->map(fn($r) => ['key' => $r['slug'], 'slug' => $r['slug'], 'label' => $r['label']])->values()),
            filteredRoles() {
                if (!this.search) return this.allRoles;
                const q = this.search.toLowerCase();
                return this.allRoles.filter(r =>
                    r.label.toLowerCase().includes(q) ||
                    r.slug.toLowerCase().includes(q)
                );
            },
            selectRole(role) {
                this.selectedKey = role.key;
                this.selectedLabel = role.label;
                this.search = role.label;
                this.open = false;
                localStorage.setItem('permission_selected_role', role.key);
            },
            clearSelection() {
                this.selectedKey = '';
                this.selectedLabel = '';
                this.search = '';
                this.open = false;
                localStorage.removeItem('permission_selected_role');
            },
            init() {
                // restore from localStorage
                const saved = localStorage.getItem('permission_selected_role');
                if (saved && this.allRoles.some(r => r.key === saved)) {
                    const role = this.allRoles.find(r => r.key === saved);
                    this.selectedKey = saved;
                    this.selectedLabel = role.label;
                    this.search = role.label;
                }
                // if old input exists (validation error), override
                @if(old('role'))
                    const targetKey = '{{ old('role') }}';
                    if (this.allRoles.some(r => r.key === targetKey)) {
                        const r = this.allRoles.find(r => r.key === targetKey);
                        this.selectedKey = targetKey;
                        this.selectedLabel = r.label;
                        this.search = r.label;
                    }
                @endif
            }
         }"
         x-init="init()">
        <h2 class="font-semibold text-gray-800 mb-2">Permission per Role</h2>

        <!-- Search Select -->
        <div class="relative mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Role <span class="text-red-500">*</span></label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text"
                    x-model="search"
                    @focus="open = true"
                    @input="open = true"
                    placeholder="Cari role..."
                    autocomplete="off"
                    class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-colors text-sm">
                <button x-show="search" x-cloak @click="clearSelection()" type="button"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Dropdown -->
            <div x-show="open" x-cloak
                 @click.away="open = false"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-1"
                 class="absolute z-20 w-full mt-2 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
                <template x-for="role in filteredRoles()" :key="role.key">
                    <button type="button"
                        @click="selectRole(role)"
                        class="w-full text-left px-4 py-3 hover:bg-gray-50 flex items-center justify-between border-b border-gray-50 last:border-0 transition-colors"
                        :class="selectedKey === role.key ? 'bg-blue-50' : ''">
                        <div>
                            <div class="text-sm font-medium text-gray-900" x-text="role.label"></div>
                            <div class="text-xs text-gray-500">
                                role=<span x-text="role.slug"></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700 border border-gray-200"
                                x-text="role.label"></span>
                            <svg x-show="selectedKey === role.key" class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </button>
                </template>
                <div x-show="filteredRoles().length === 0" class="px-4 py-6 text-sm text-gray-500 text-center">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Tidak ada role ditemukan untuk "<span x-text="search"></span>"
                </div>
            </div>

            <p class="text-xs text-gray-500 mt-1.5">Ketik untuk mencari role, klik untuk memilih. Daftar role difilter otomatis.</p>

            <!-- Selected badge -->
            <div x-show="selectedKey" x-cloak class="mt-3 flex items-center gap-2 text-sm bg-blue-50 border border-blue-200 rounded-lg px-3 py-2">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="text-gray-700">Terpilih:</span>
                <span class="px-3 py-1 text-xs rounded-full bg-blue-600 text-white font-medium" x-text="selectedLabel"></span>
                <span class="text-xs text-gray-500" x-text="'(' + selectedKey + ')'"></span>
                <button @click="clearSelection()" type="button" class="ml-auto text-xs text-gray-500 hover:text-red-600 underline">Ganti role</button>
            </div>
        </div>

        <!-- Placeholder when no role selected -->
        <div x-show="!selectedKey" x-cloak class="border-2 border-dashed border-gray-200 rounded-lg p-8 text-center bg-gray-50">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <p class="text-sm font-medium text-gray-600">Belum ada role dipilih</p>
            <p class="text-xs text-gray-500 mt-1">Gunakan kotak pencarian di atas untuk memilih role yang akan diatur permission-nya</p>
        </div>

        @foreach($roles as $roleInfo)
            <div x-show="selectedKey === '{{ $roleInfo['slug'] }}'" x-cloak x-transition class="border border-gray-200 rounded-lg p-4 bg-white">
                <h3 class="font-medium text-gray-900 mb-3 flex items-center flex-wrap gap-2">
                    <span class="px-3 py-1 text-xs rounded-full bg-gray-100 text-gray-700 border border-gray-200">
                        {{ $roleInfo['label'] }}
                    </span>
                    <span class="text-xs text-gray-500">role={{ $roleInfo['slug'] }}</span>
                    <span class="ml-auto text-xs px-2 py-1 bg-gray-100 rounded-full text-gray-600">{{ count($roleInfo['assigned']) }} permission aktif</span>
                </h3>

                <form action="{{ route('admin.permissions.role.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="role" value="{{ $roleInfo['slug'] }}">

                    @foreach($permissions as $group => $perms)
                        <div class="mb-4">
                            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">{{ $group }}</p>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                @foreach($perms as $perm)
                                    <label class="flex items-center space-x-2 p-2 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors {{ in_array($perm->slug, $roleInfo['assigned']) ? 'border-blue-300 bg-blue-50' : 'border-gray-200 bg-white' }}">
                                        <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" {{ in_array($perm->slug, $roleInfo['assigned']) ? 'checked' : '' }} class="rounded text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm">
                                            <span class="font-medium text-gray-800">{{ $perm->name }}</span>
                                            <span class="text-xs text-gray-500 block">{{ $perm->slug }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="flex justify-end pt-4 border-t border-gray-100 mt-4">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium shadow-sm">Simpan {{ $roleInfo['label'] }}</button>
                    </div>
                </form>
            </div>
        @endforeach
    </div>
</div>
@endsection
