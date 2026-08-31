@extends('administrasi-umum.layouts.app')

@section('title', 'Dashboard Administrasi Umum')

@php
$chartData = [
'orderTrends' => [
'dates' => $orderTrends->pluck('date'),
'counts' => $orderTrends->pluck('count')
],
'statusDistribution' => $statusDistribution,
'progress' => [
'current' => $progressPercentage,
'remaining' => 100 - $progressPercentage
]
];
@endphp

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 bg-gradient-to-r from-white to-blue-100 p-4 rounded-lg shadow-sm">
        <h1 class="text-xl font-semibold text-gray-800">Dashboard Administrasi Umum</h1>
        <p class="text-sm text-gray-500">Ringkasan perbaikan dan pemeliharaan</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <!-- Total Orders Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-50 overflow-hidden transition-all hover:shadow-md">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="p-2.5 bg-blue-500 rounded-full">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xs uppercase font-medium text-gray-500 tracking-wide">Total Order</h3>
                        <p class="text-xl font-semibold text-gray-800 mt-1">{{ $totalOrders }}</p>
                    </div>
                </div>
            </div>
            <div class="h-1 bg-blue-500"></div>
        </div>

        <!-- In Progress Orders Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-50 overflow-hidden transition-all hover:shadow-md">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="p-2.5 bg-yellow-500 rounded-full">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xs uppercase font-medium text-gray-500 tracking-wide">Sedang Diproses</h3>
                        <p class="text-xl font-semibold text-gray-800 mt-1">{{ $pendingOrders }}</p>
                    </div>
                </div>
            </div>
            <div class="h-1 bg-yellow-500"></div>
        </div>

        <!-- Open Orders Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-50 overflow-hidden transition-all hover:shadow-md">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="p-2.5 bg-green-500 rounded-full">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xs uppercase font-medium text-gray-500 tracking-wide">Dibuka</h3>
                        <p class="text-xl font-semibold text-gray-800 mt-1">{{ $openOrders }}</p>
                    </div>
                </div>
            </div>
            <div class="h-1 bg-green-500"></div>
        </div>

        <!-- Reject Orders Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-50 overflow-hidden transition-all hover:shadow-md">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="p-2.5 bg-red-500 rounded-full">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xs uppercase font-medium text-gray-500 tracking-wide">Ditolak</h3>
                        <p class="text-xl font-semibold text-gray-800 mt-1">{{ $rejectedOrders }}</p>
                    </div>
                </div>
            </div>
            <div class="h-1 bg-red-500"></div>
        </div>
    </div>

    <!-- Combined Filter and Charts Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-50 overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-white to-blue-100 px-5 py-4 border-b border-gray-100">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="text-base font-semibold text-gray-800">Ringkasan Dashboard</h2>

                <!-- Filter Controls -->
                <div class="flex flex-wrap gap-3">
                    <select id="timeFilter" name="time_filter"
                        class="text-sm rounded-md border-gray-200 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-white">
                        <option value="all" {{ $timeFilter === 'all' ? 'selected' : '' }}>Semua Waktu</option>
                        <option value="today" {{ $timeFilter === 'today' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="week" {{ $timeFilter === 'week' ? 'selected' : '' }}>Minggu Ini</option>
                        <option value="month" {{ $timeFilter === 'month' ? 'selected' : '' }}>Bulan Ini</option>
                    </select>

                    <select id="statusFilter" name="status"
                        class="text-sm rounded-md border-gray-200 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-white">
                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="open" {{ $statusFilter === 'open' ? 'selected' : '' }}>Dibuka</option>
                        <option value="in_progress" {{ $statusFilter === 'in_progress' ? 'selected' : '' }}>Sedang
                            Diproses</option>
                        <option value="confirmed" {{ $statusFilter === 'confirmed' ? 'selected' : '' }}>Dikonfirmasi
                        </option>
                        <option value="rejected" {{ $statusFilter === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="p-5">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
                <!-- Progress Stats -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg p-6 shadow-sm h-full">
                        <!-- Total Orders Stats -->
                        <div class="mb-6">
                            <div class="text-sm text-gray-600 mb-1">Total Order</div>
                            <div class="text-2xl font-semibold text-gray-800">{{ $confirmedOrders }}/{{ $totalOrders }}
                            </div>
                            <div class="text-sm text-gray-500 mt-1">Sisa: {{ $remainingOrders }} Order</div>
                        </div>

                        <!-- Timeline Progress -->
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm text-gray-600">Masa Pengerjaan</div>
                                <div class="text-sm font-semibold text-blue-600">{{ $totalCompletedPercentage }}%</div>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full"
                                    style="width: {{ $totalCompletedPercentage }}%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500 mt-1">
                                <span>{{ $currentYear }}</span>
                                <span>{{ $targetYear }}</span>
                            </div>
                        </div>

                        <!-- Progress Stats -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm text-gray-600">Progres Order</div>
                                <div class="text-sm font-semibold text-blue-600">{{ $progressPercentage }}%</div>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $progressPercentage }}%">
                                </div>
                            </div>
                            <div class="flex flex-col gap-2 text-sm">
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                                    <span class="text-gray-600">Selesai: {{ $confirmedOrders }}</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></div>
                                    <span class="text-gray-600">Tersisa: {{ $remainingOrders }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Trends Chart -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg border border-gray-100 shadow-sm h-full">
                        <div class="p-4 border-b border-gray-100">
                            <h3 class="text-sm font-medium text-gray-700">Tren Order</h3>
                        </div>
                        <div class="p-4" style="height: 280px;">
                            <canvas id="orderTrendsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Status Distribution Chart -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg border border-gray-100 shadow-sm h-full">
                        <div class="p-4 border-b border-gray-100">
                            <h3 class="text-sm font-medium text-gray-700">Distribusi Status</h3>
                        </div>
                        <div class="p-4" style="height: 280px;">
                            <canvas id="statusDistributionChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Progress Chart -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg border border-gray-100 shadow-sm h-full">
                        <div class="p-4 border-b border-gray-100">
                            <h3 class="text-sm font-medium text-gray-700">Progres Order</h3>
                        </div>
                        <div class="p-4" style="height: 280px;">
                            <div class="flex flex-col items-center justify-center h-full">
                                <div class="relative w-48 h-48">
                                    <canvas id="progressChart"></canvas>
                                    <div
                                        class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-center">
                                        <div class="text-3xl font-bold text-gray-800" id="progressPercentageDisplay">
                                            {{ $progressPercentage }}%</div>
                                        <div class="text-sm text-gray-500">Progres</div>
                                    </div>
                                </div>
                                <div class="mt-4 text-center">
                                    <div class="text-sm text-gray-600">
                                        <span class="font-medium" id="pendingOrdersDisplay">{{ $pendingOrders }}</span>
                                        sedang diproses dari
                                        <span class="font-medium"
                                            id="totalActiveOrdersDisplay">{{ $openOrders + $pendingOrders }}</span>
                                        total order aktif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-50 overflow-hidden">
        <div class="bg-gradient-to-r from-white to-blue-100 px-5 py-4 border-b border-gray-100">
            <div class="flex justify-between items-center">
                <h3 class="text-base font-semibold text-gray-800">Order Terbaru</h3>
                <a href="{{ route('administrasi-umum.order-perbaikan.index') }}"
                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-xs font-medium rounded-md text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-150 ease-in-out">
                    Lihat Semua
                    <svg class="ml-1.5 h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </a>
            </div>
        </div>

        <div class="p-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @include('administrasi-umum.dashboard.partials.recent-orders')
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Initialize chart data
const chartData = @json($chartData);

let orderTrendsChart, statusDistributionChart, progressChart;

document.getElementById('timeFilter').addEventListener('change', function() {
    updateDashboard();
});

document.getElementById('statusFilter').addEventListener('change', function() {
    updateDashboard();
});

function updateDashboard() {
    const timeFilter = document.getElementById('timeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    window.location.href =
        `{{ route('administrasi-umum.dashboard') }}?time_filter=${timeFilter}&status_filter=${statusFilter}`;
}

// Function to fetch updated dashboard data
async function fetchDashboardData() {
    try {
        const timeFilter = document.getElementById('timeFilter').value;
        const statusFilter = document.getElementById('statusFilter').value;
        const response = await fetch(
            `{{ route('administrasi-umum.dashboard.stats') }}?time_filter=${timeFilter}&status_filter=${statusFilter}`
        );
        const data = await response.json();

        // Update statistics cards
        document.getElementById('totalOrders').textContent = data.totalOrders;
        document.getElementById('pendingOrders').textContent = data.pendingOrders;
        document.getElementById('openOrders').textContent = data.openOrders;
        document.getElementById('rejectedOrders').textContent = data.rejectedOrders;

        // Update progress stats
        document.getElementById('confirmedOrdersTotal').textContent = `${data.confirmedOrders}/${data.totalOrders}`;
        document.getElementById('remainingOrdersCount').textContent = data.remainingOrders;
        document.getElementById('totalCompletedPercentage').textContent = `${data.totalCompletedPercentage}%`;
        document.getElementById('progressPercentage').textContent = `${data.progressPercentage}%`;
        document.getElementById('confirmedOrdersCount').textContent = data.confirmedOrders;
        document.getElementById('remainingOrdersText').textContent = data.remainingOrders;

        // Update progress bars
        document.getElementById('timelineProgressBar').style.width = `${data.totalCompletedPercentage}%`;
        document.getElementById('orderProgressBar').style.width = `${data.progressPercentage}%`;

        // Update charts
        updateCharts(data);

        // Update recent orders
        const recentOrdersContainer = document.querySelector('.grid-cols-1.gap-4');
        if (recentOrdersContainer && data.recentOrdersHtml) {
            recentOrdersContainer.innerHTML = data.recentOrdersHtml;
        }

    } catch (error) {
        console.error('Error fetching dashboard data:', error);
    }
}

function updateCharts(data) {
    // Update Order Trends Chart
    if (orderTrendsChart && data.orderTrends) {
        const dates = data.orderTrends.map(item => item.date);
        const counts = data.orderTrends.map(item => item.count);

        orderTrendsChart.data.labels = dates;
        orderTrendsChart.data.datasets[0].data = counts;
        orderTrendsChart.update();
    }

    // Update Status Distribution Chart
    if (statusDistributionChart && data.statusDistribution) {
        statusDistributionChart.data.datasets[0].data = [
            data.statusDistribution.open,
            data.statusDistribution.in_progress,
            data.statusDistribution.confirmed,
            data.statusDistribution.rejected
        ];
        statusDistributionChart.update();
    }

    // Update Progress Chart
    if (progressChart) {
        progressChart.data.datasets[0].data = [data.progressPercentage, 100 - data.progressPercentage];
        progressChart.update();
    }
}

// Initialize charts
const orderTrendsCtx = document.getElementById('orderTrendsChart').getContext('2d');
orderTrendsChart = new Chart(orderTrendsCtx, {
    type: 'line',
    data: {
        labels: chartData.orderTrends.dates,
        datasets: [{
            label: 'Orders',
            data: chartData.orderTrends.counts,
            borderColor: '#3b82f6',
            tension: 0.3,
            fill: true,
            backgroundColor: 'rgba(59, 130, 246, 0.1)'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                displayColors: false,
                backgroundColor: 'rgba(17, 24, 39, 0.8)',
                padding: 10,
                titleFont: {
                    size: 13
                },
                bodyFont: {
                    size: 12
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    font: {
                        size: 11
                    }
                },
                grid: {
                    color: 'rgba(243, 244, 246, 1)',
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        size: 11
                    }
                }
            }
        },
        elements: {
            point: {
                radius: 3,
                hoverRadius: 5
            },
            line: {
                borderWidth: 2
            }
        }
    }
});

const statusDistributionCtx = document.getElementById('statusDistributionChart').getContext('2d');
statusDistributionChart = new Chart(statusDistributionCtx, {
    type: 'doughnut',
    data: {
        labels: ['Dibuka', 'Sedang Diproses', 'Dikonfirmasi', 'Ditolak'],
        datasets: [{
            data: [
                chartData.statusDistribution.open,
                chartData.statusDistribution.in_progress,
                chartData.statusDistribution.confirmed,
                chartData.statusDistribution.rejected
            ],
            backgroundColor: [
                '#3b82f6',
                '#eab308',
                '#22c55e',
                '#ef4444'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    usePointStyle: true,
                    padding: 15,
                    font: {
                        size: 11
                    }
                }
            },
            tooltip: {
                displayColors: false,
                backgroundColor: 'rgba(17, 24, 39, 0.8)',
                padding: 10,
                titleFont: {
                    size: 13
                },
                bodyFont: {
                    size: 12
                }
            }
        },
        cutout: '70%'
    }
});

const progressChartCtx = document.getElementById('progressChart').getContext('2d');
progressChart = new Chart(progressChartCtx, {
    type: 'doughnut',
    data: {
        labels: ['Sedang Diproses', 'Tersisa'],
        datasets: [{
            data: [chartData.progress.current, chartData.progress.remaining],
            backgroundColor: [
                '#3b82f6',
                '#e5e7eb'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                enabled: false
            }
        },
        cutout: '80%'
    }
});

// Start auto-update polling
const POLLING_INTERVAL = 30000; // 30 seconds
setInterval(fetchDashboardData, POLLING_INTERVAL);
</script>
@endpush
@endsection