<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Administrasi Umum RS Azra</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo-ajra.jpg') }}">

    <!-- Import Font Poppins dari Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
    body {
        font-family: 'Poppins', sans-serif;
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
        background: linear-gradient(to bottom, #3b82f6, #10b981);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(to bottom, #2563eb, #059669);
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

    /* Page transition effects */
    .page-enter {
        opacity: 0;
        transform: translateY(20px);
    }

    .page-enter-active {
        opacity: 1;
        transform: translateY(0);
        transition: opacity 300ms, transform 300ms;
    }

    /* Scroll to top button style */
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
</head>

<body class="font-poppins antialiased bg-gradient-to-br from-green-50 to-blue-50">
    <div id="view" class="h-full w-full flex flex-row" x-data="{ 
            sidenav: window.innerWidth >= 768 ? true : false,
            handleResize() {
                this.sidenav = window.innerWidth >= 768 ? true : false;
            }
        }" x-init="window.addEventListener('resize', handleResize)">

        <button @click="sidenav = !sidenav"
            class="p-2 border-2 bg-white rounded-md border-gray-200 shadow-lg text-gray-500 focus:bg-blue-500 focus:outline-none focus:text-white absolute top-0 left-0 sm:hidden z-20 m-4">
            <svg class="w-5 h-5 fill-current" fill="currentColor" viewBox="0 0 20 20"
                xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd"
                    d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                    clip-rule="evenodd"></path>
            </svg>
        </button>

        @include('administrasi-umum.layouts.navigation')

        <!-- Main Content -->
        <div class="flex-1 px-6 py-8 md:ml-60">
            @if (session('success'))
            <div id="alert-success" class="fixed top-4 right-4 w-96 alert-animate-in">
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-lg shadow-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-800">{{ session('success') }}</p>
                        </div>
                        <div class="ml-auto pl-3">
                            <button onclick="dismissAlert('alert-success')" class="text-blue-600 hover:text-blue-800">
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
            <div id="alert-error" class="fixed top-4 right-4 w-96 alert-animate-in">
                <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg shadow-lg">
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

            @yield('content')
        </div>
    </div>

    <!-- Scroll to top button -->
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

    // Auto dismiss alerts after 3 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = ['alert-success', 'alert-error'];
        alerts.forEach(alertId => {
            const alert = document.getElementById(alertId);
            if (alert) {
                setTimeout(() => {
                    dismissAlert(alertId);
                }, 3000);
            }
        });
    });

    // Scroll to top function
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
</body>

</html>