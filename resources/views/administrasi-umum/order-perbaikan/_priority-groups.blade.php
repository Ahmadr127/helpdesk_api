<!-- High Priority Orders -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <button onclick="togglePriorityGroup('high')" class="w-full">
        <div class="p-4 bg-red-50 border-b border-red-100">
            <h3 class="text-lg font-semibold text-red-700 flex items-center justify-between">
                <div class="flex items-center">
                    Order Prioritas Tinggi/Urgent
                </div>
                <svg id="high-arrow" class="w-5 h-5 transform transition-transform duration-200 rotate-180"
                    fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </h3>
        </div>
    </button>
    <div id="high-content" class="overflow-x-auto">
        @include('administrasi-umum.order-perbaikan._table', ['orders' => $orders->where('prioritas', 'TINGGI/URGENT')])
    </div>
</div>

<!-- Medium Priority Orders -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <button onclick="togglePriorityGroup('medium')" class="w-full">
        <div class="p-4 bg-yellow-50 border-b border-yellow-100">
            <h3 class="text-lg font-semibold text-yellow-700 flex items-center justify-between">
                <div class="flex items-center">
                    Order Prioritas Sedang
                </div>
                <svg id="medium-arrow" class="w-5 h-5 transform transition-transform duration-200 rotate-180"
                    fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </h3>
        </div>
    </button>
    <div id="medium-content" class="overflow-x-auto">
        @include('administrasi-umum.order-perbaikan._table', ['orders' => $orders->where('prioritas', 'SEDANG')])
    </div>
</div>

<!-- Low Priority Orders -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <button onclick="togglePriorityGroup('low')" class="w-full">
        <div class="p-4 bg-blue-50 border-b border-blue-100">
            <h3 class="text-lg font-semibold text-blue-700 flex items-center justify-between">
                <div class="flex items-center">
                    Order Prioritas Rendah
                </div>
                <svg id="low-arrow" class="w-5 h-5 transform transition-transform duration-200 rotate-180"
                    fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </h3>
        </div>
    </button>
    <div id="low-content" class="overflow-x-auto">
        @include('administrasi-umum.order-perbaikan._table', ['orders' => $orders->where('prioritas', 'RENDAH')])
    </div>
</div>