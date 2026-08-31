@extends('admin.layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center">
            <div class="p-2.5 bg-blue-500 rounded-full">
                <i class="fas fa-ticket-alt text-white text-lg"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-gray-500 text-xs uppercase tracking-wide">Total Tiket</h3>
                <p class="text-xl font-semibold" id="totalTickets">{{ $totalTickets }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center">
            <div class="p-2.5 bg-green-500 rounded-full">
                <i class="fas fa-ticket-alt text-white text-lg"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-gray-500 text-xs uppercase tracking-wide">Tiket Dibuka</h3>
                <p class="text-xl font-semibold" id="openTickets">{{ $openTickets }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center">
            <div class="p-2.5 bg-yellow-500 rounded-full">
                <i class="fas fa-clock text-white text-lg"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-gray-500 text-xs uppercase tracking-wide">Tiket Dalam Proses</h3>
                <p class="text-xl font-semibold" id="inProgressTickets">{{ $inProgressTickets }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center">
            <div class="p-2.5 bg-red-500 rounded-full">
                <i class="fas fa-chart-line text-white text-lg"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-gray-500 text-xs uppercase tracking-wide">Ditutup & Dikonfirmasi</h3>
                <p class="text-xl font-semibold" id="closedTickets">{{ $closedAndConfirmedTickets }}</p>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-lg p-6 mb-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Ringkasan Dashboard</h2>
        <div class="flex gap-2">
            <select id="timeFilter"
                class="text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                <option value="all">Semua Waktu</option>
                <option value="today">Hari Ini</option>
                <option value="week">Minggu Ini</option>
                <option value="month">Bulan Ini</option>
            </select>
            <select id="statusFilter"
                class="text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                <option value="all">Semua Status</option>
                <option value="open">Dibuka</option>
                <option value="in_progress">Dalam proses</option>
                <option value="pending">Tertunda</option>
                <option value="closed">Ditutup</option>
                <option value="confirmed">Dikonfirmasi</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Total Ticket Section -->
        <div class="bg-white rounded-lg border p-4">
            <h3 class="text-gray-700 mb-2">Total Tiket</h3>
            <div class="text-2xl font-bold mb-2">{{ $totalTickets }}/{{ $totalActiveTickets }}</div>
            <div class="text-sm text-gray-500 mb-4">Sisa: {{ $remainingTickets }} Tiket</div>

            <div class="mb-4">
                <div class="flex justify-between text-sm mb-1">
                    <span>Masa Pengerjaan</span>
                    <span class="text-blue-600" data-processing-percentage>{{ $processingPercentage }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $processingPercentage }}%"></div>
                </div>
            </div>

            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span>Progres Tiket</span>
                    <span class="text-blue-600" data-ticket-progress>{{ $ticketProgress }}%</span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span class="flex items-center gap-1">
                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                        <span data-completed-count>Selesai: {{ $completedTickets }}</span>
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                        <span data-remaining-count>Tersisa: {{ $remainingTickets }}</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Tren Tiket Chart -->
        <div class="bg-white rounded-lg border p-4">
            <h3 class="text-gray-700 mb-4">Tren Tiket</h3>
            <div class="h-48">
                <canvas id="ticketChart"></canvas>
            </div>
        </div>

        <!-- Distribusi Status Chart -->
        <div class="bg-white rounded-lg border p-4">
            <h3 class="text-gray-700 mb-4">Distribusi Status</h3>
            <div class="h-48">
                <canvas id="statusPieChart"></canvas>
            </div>
        </div>

        <!-- Progres Tiket -->
        <div class="bg-white rounded-lg border p-4">
            <h3 class="text-gray-700 mb-4">Progres Tiket</h3>
            <div class="flex flex-col items-center justify-center h-48">
                <div class="relative w-32 h-32">
                    <canvas id="progressChart"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-2xl font-bold" id="progressPercentage">0%</span>
                        <span class="text-sm text-gray-500">Progres</span>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mt-4" id="progressText">
                    0 sedang diproses dari 0 total tiket aktif
                </p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-gradient-to-r from-white to-blue-300 p-5">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">Tiket Terbaru</h2>
                <a href="{{ route('admin.tickets.index') }}"
                    class="text-xs font-medium text-gray-600 hover:text-black px-3 py-1.5 rounded-lg hover:bg-gray-400 transition-all duration-200 flex items-center">
                    Lihat Semua
                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>
        <div class="p-5">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th
                                class="px-4 py-2 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID</th>
                            <th
                                class="px-4 py-2 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pengguna</th>
                            <th
                                class="px-4 py-2 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th
                                class="px-4 py-2 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @foreach($recentTickets as $ticket)
                        <tr>
                            <td class="px-4 py-3 border-b">{{ $ticket->ticket_number }}</td>
                            <td class="px-4 py-3 border-b">{{ $ticket->user->name }}</td>
                            <td class="px-4 py-3 border-b">
                                <span class="px-2 py-1 text-xs rounded-full 
                                {{ $ticket->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                   ($ticket->status === 'closed' ? 'bg-red-100 text-red-800' : 
                                   ($ticket->status === 'confirmed' ? 'bg-green-100 text-green-800' :
                                   ($ticket->status === 'open' ? 'bg-blue-200 text-blue-800' :
                                   'bg-purple-100 text-purple-800'))) }}">
                                    {{ $ticket->status === 'pending' ? 'Tertunda' :
                                       ($ticket->status === 'closed' ? 'Ditutup' :
                                       ($ticket->status === 'confirmed' ? 'Dikonfirmasi' :
                                       ($ticket->status === 'open' ? 'Dibuka' :
                                       ($ticket->status === 'in_progress' ? 'Dalam proses' : ucfirst($ticket->status))))) }}
                                    {{ $ticket->status === 'closed' ? '(Menunggu konfirmasi)' : '' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 border-b">
                                <a href="{{ route('admin.tickets.show', $ticket) }}"
                                    class="text-yellow-600 hover:text-yellow-900 text-xs">
                                    Lihat Detail
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-gradient-to-r from-blue-300 to-white p-5">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">Pengguna Terbaru</h2>
                <a href="{{ route('admin.users.index') }}"
                    class="text-xs font-medium text-gray-600 hover:text-black px-3 py-1.5 rounded-lg hover:bg-gray-400 transition-all duration-200 flex items-center">
                    Lihat Semua
                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>
        <div class="p-5">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th
                                class="px-4 py-2 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nama</th>
                            <th
                                class="px-4 py-2 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email</th>
                            <th
                                class="px-4 py-2 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Bergabung</th>
                            <th
                                class="px-4 py-2 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @foreach($recentUsers as $user)
                        <tr>
                            <td class="px-4 py-3 border-b">{{ $user->name }}</td>
                            <td class="px-4 py-3 border-b">{{ $user->email }}</td>
                            <td class="px-4 py-3 border-b">{{ $user->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3 border-b">
                                <a href="{{ route('admin.users.edit', $user) }}"
                                    class="text-yellow-600 hover:text-yellow-900 text-xs">
                                    Lihat Detail
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let ticketChart;
let statusPieChart;
let progressChart;

function updateSummaryData(summary) {
    // Update total tickets
    document.querySelector('.text-2xl.font-bold').textContent = `${summary.totalTickets}/${summary.totalActiveTickets}`;
    document.querySelector('.text-sm.text-gray-500.mb-4').textContent = `Sisa: ${summary.remainingTickets} Tiket`;

    // Update processing percentage
    const processingPercentageEl = document.querySelector('[data-processing-percentage]');
    processingPercentageEl.textContent = `${summary.processingPercentage}%`;
    processingPercentageEl.nextElementSibling.querySelector('.bg-blue-600').style.width =
        `${summary.processingPercentage}%`;

    // Update ticket progress
    const ticketProgressEl = document.querySelector('[data-ticket-progress]');
    ticketProgressEl.textContent = `${summary.ticketProgress}%`;

    // Update completed and remaining counts
    document.querySelector('[data-completed-count]').textContent = `Selesai: ${summary.completedTickets}`;
    document.querySelector('[data-remaining-count]').textContent = `Tersisa: ${summary.remainingTickets}`;

    // Update top stats cards
    document.getElementById('totalTickets').textContent = summary.totalTickets;
}

function initCharts() {
    // Line chart for ticket trends
    const ticketCtx = document.getElementById('ticketChart').getContext('2d');
    ticketChart = new Chart(ticketCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Tiket',
                data: [],
                borderColor: 'rgb(59, 130, 246)',
                tension: 0.4,
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
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Pie chart for status distribution
    const statusCtx = document.getElementById('statusPieChart').getContext('2d');
    statusPieChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Dibuka', 'Dalam proses', 'Dikonfirmasi', 'Ditolak'],
            datasets: [{
                data: [],
                backgroundColor: [
                    'rgb(59, 130, 246)', // Blue for Open
                    'rgb(245, 158, 11)', // Yellow for In Progress
                    'rgb(34, 197, 94)', // Green for Confirmed
                    'rgb(239, 68, 68)' // Red for Rejected
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
                        boxWidth: 12,
                        padding: 15,
                        font: {
                            size: 11
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });

    // Progress chart
    const progressCtx = document.getElementById('progressChart').getContext('2d');
    progressChart = new Chart(progressCtx, {
        type: 'doughnut',
        data: {
            labels: ['Progres', 'Sisa'],
            datasets: [{
                data: [0, 100],
                backgroundColor: [
                    'rgb(59, 130, 246)', // Blue for Progress
                    'rgb(229, 231, 235)' // Gray for Remaining
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '80%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: false
                }
            }
        }
    });
}

function updateCharts() {
    const timeFilter = document.getElementById('timeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;

    fetch(`/admin/dashboard/stats?time=${timeFilter}&status=${statusFilter}`)
        .then(response => response.json())
        .then(data => {
            // Update line chart
            ticketChart.data.labels = data.dates.map(date => {
                const d = new Date(date);
                return d.toLocaleDateString('id-ID', {
                    month: 'short',
                    day: 'numeric'
                });
            });
            ticketChart.data.datasets[0].data = data.counts;
            ticketChart.update();

            // Update pie chart
            statusPieChart.data.datasets[0].data = [
                data.statusDistribution.open,
                data.statusDistribution.in_progress,
                data.statusDistribution.confirmed,
                data.statusDistribution.closed
            ];
            statusPieChart.update();

            // Update progress chart and text
            if (data.summary) {
                const inProgress = data.statusDistribution.in_progress;
                const totalActive = data.summary.totalActiveTickets;
                const progressPercentage = totalActive > 0 ? Math.round((inProgress / totalActive) * 100) : 0;

                progressChart.data.datasets[0].data = [progressPercentage, 100 - progressPercentage];
                progressChart.update();

                // Update center text and description
                document.getElementById('progressPercentage').textContent = `${progressPercentage}%`;
                document.getElementById('progressText').textContent =
                    `${inProgress} Dalam proses dari ${totalActive} total tiket aktif`;
            }

            // Update summary data
            if (data.summary) {
                updateSummaryData(data.summary);
            }

            // Update top stats cards
            document.getElementById('openTickets').textContent = data.statusDistribution.open;
            document.getElementById('inProgressTickets').textContent = data.statusDistribution.in_progress;
            document.getElementById('closedTickets').textContent =
                data.statusDistribution.closed + data.statusDistribution.confirmed;
        });
}

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    updateCharts();

    document.getElementById('timeFilter').addEventListener('change', updateCharts);
    document.getElementById('statusFilter').addEventListener('change', updateCharts);
});
</script>
@endpush