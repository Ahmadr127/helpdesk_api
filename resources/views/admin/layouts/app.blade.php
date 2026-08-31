<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Rumah Sakit Azra</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo-ajra.jpg') }}">

    <!-- Import Font Poppins dari Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Tambahkan animate.css untuk animasi yang lebih menarik -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <style>
    body {
        font-family: 'Poppins', sans-serif;
        background-image: linear-gradient(to right, #ecfdf5, #eff6ff);
        background-attachment: fixed;
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    ::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }

    /* Card styling */
    .card {
        background-color: white;
        border-radius: 0.75rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .card-header {
        background-image: linear-gradient(to right, #ecfdf5, #d1fae5);
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(209, 250, 229, 0.5);
    }

    @keyframes slideInDown {
        from {
            transform: translateY(-100%);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    @keyframes slideOutUp {
        from {
            transform: translateY(0);
            opacity: 1;
        }

        to {
            transform: translateY(-100%);
            opacity: 0;
        }
    }

    .alert-animate-in {
        animation: slideInDown 0.5s ease-out forwards;
    }

    .alert-animate-out {
        animation: slideOutUp 0.5s ease-in forwards;
    }

    /* Scroll to top button style matching notifications page */
    #back-to-top {
        position: fixed;
        bottom: 6;
        right: 6;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
        transform: translateY(10px);
        z-index: 999;
    }

    #back-to-top.visible {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    </style>
    @livewireStyles
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    // Konfigurasi global SweetAlert2
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
    </script>

    @stack('styles')
</head>

<body class="font-poppins antialiased">
    <div id="view" class="h-full w-full flex flex-row" x-data="{ 
            sidenav: window.innerWidth >= 768 ? true : false,
            handleResize() {
                this.sidenav = window.innerWidth >= 768 ? true : false;
            }
        }" x-init="window.addEventListener('resize', handleResize)">

        <button @click="sidenav = !sidenav"
            class="p-2 border-2 bg-white rounded-md border-gray-200 shadow-lg text-gray-500 focus:bg-green-500 focus:outline-none focus:text-white absolute top-4 left-4 z-50 sm:hidden">
            <svg class="w-5 h-5 fill-current" fill="currentColor" viewBox="0 0 20 20"
                xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd"
                    d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                    clip-rule="evenodd"></path>
            </svg>
        </button>

        @include('admin.layouts.navigation')

        <!-- Main Content -->
        <div class="flex-1 px-4 py-8 md:ml-60">
            <!-- Header bar -->
            <div
                class="mb-8 bg-white rounded-xl shadow-sm p-4 flex justify-between items-center bg-gradient-to-r from-gray-50 to-blue-200">
                <h1 class="text-xl font-semibold text-gray-800">@yield('title', 'Dashboard')</h1>
                <div class="flex items-center space-x-4">
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center focus:outline-none">
                            <div
                                class="w-10 h-10 rounded-full bg-gradient-to-r from-green-400 to-blue-400 text-white flex items-center justify-center mr-2">
                                <span
                                    class="text-sm font-bold">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                            </div>
                            <span
                                class="hidden md:block text-sm font-medium text-gray-700">{{ Auth::user()->name }}</span>
                            <svg class="w-4 h-4 ml-1 text-gray-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false"
                            class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if (session('success'))
            <div id="alert-success" class="fixed top-4 right-4 w-96 alert-animate-in z-50">
                <div class="bg-white border-l-4 border-green-400 p-4 rounded-lg shadow-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                        </div>
                        <div class="ml-auto pl-3">
                            <button onclick="dismissAlert('alert-success')" class="text-green-600 hover:text-green-800">
                                <span class="sr-only">Close</span>
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if (session('error'))
            <div id="alert-error" class="fixed top-4 right-4 w-96 alert-animate-in z-50">
                <div class="bg-white border-l-4 border-red-400 p-4 rounded-lg shadow-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                        </div>
                        <div class="ml-auto pl-3">
                            <button onclick="dismissAlert('alert-error')" class="text-red-600 hover:text-red-800">
                                <span class="sr-only">Close</span>
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="content-wrapper">
                @yield('content')
            </div>
        </div>
    </div>

    <!-- Scroll to top button matching notifications page -->
    <div id="back-to-top"
        class="fixed bottom-6 right-6 opacity-0 invisible transition-all duration-300 transform translate-y-10">
        <button onclick="scrollToTop()"
            class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-3 rounded-full shadow-lg hover:shadow-xl focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18">
                </path>
            </svg>
        </button>
    </div>

    <script>
    function dismissAlert(alertId) {
        const alert = document.getElementById(alertId);
        if (alert) {
            alert.classList.remove('alert-animate-in');
            alert.classList.add('alert-animate-out');
            setTimeout(() => alert.remove(), 500);
        }
    }

    // Auto dismiss alerts after 4 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = ['alert-success', 'alert-error'];
        alerts.forEach(alertId => {
            const alert = document.getElementById(alertId);
            if (alert) {
                setTimeout(() => {
                    dismissAlert(alertId);
                }, 4000);
            }
        });
    });

    // Scroll to top function matching notifications page
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });
    }

    // Show/hide back-to-top button on scroll
    document.addEventListener('DOMContentLoaded', function() {
        const backToTopBtn = document.getElementById("back-to-top");

        window.addEventListener("scroll", function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.add("visible");
                backToTopBtn.classList.remove("opacity-0", "invisible", "translate-y-10");
                backToTopBtn.classList.add("opacity-100", "visible", "translate-y-0");
            } else {
                backToTopBtn.classList.remove("visible");
                backToTopBtn.classList.remove("opacity-100", "visible", "translate-y-0");
                backToTopBtn.classList.add("opacity-0", "invisible", "translate-y-10");
            }
        });
    });
    </script>

    @stack('scripts')
    @livewireScripts

    @if(session('success'))
    <script>
    Swal.fire({
        title: 'Berhasil!',
        text: "{{ session('success') }}",
        icon: 'success',
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#10b981',
        showClass: {
            popup: 'animate__animated animate__fadeInDown'
        },
        hideClass: {
            popup: 'animate__animated animate__fadeOutUp'
        },
        customClass: {
            title: 'text-green-600 font-semibold'
        },
        timer: 3000,
        timerProgressBar: true
    });
    </script>
    @endif

    @if(session('error'))
    <script>
    Swal.fire({
        title: 'Gagal!',
        text: "{{ session('error') }}",
        icon: 'error',
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#ef4444',
        showClass: {
            popup: 'animate__animated animate__fadeInDown'
        },
        hideClass: {
            popup: 'animate__animated animate__fadeOutUp'
        },
        customClass: {
            title: 'text-red-600 font-semibold'
        }
    });
    </script>
    @endif
</body>

</html>