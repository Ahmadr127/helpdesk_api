<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Rumah Sakit Azra</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo-ajra.jpg') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">

    <style>
    body {
        font-family: 'Poppins', sans-serif;
        min-height: 100vh;
        background-image: linear-gradient(to right, #ecfdf5, #eff6ff);
        background-attachment: fixed;
    }

    /* Notifikasi yang konsisten dengan admin layout */
    .notification-container {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 99999;
        width: auto;
        min-width: 300px;
        max-width: 90%;
        pointer-events: none;
    }

    .notification-alert {
        background-color: white;
        border-radius: 0.5rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        margin-bottom: 0.75rem;
        padding: 1rem;
        opacity: 0;
        transform: translateY(-20px);
        animation: slideInDown 0.4s cubic-bezier(0.23, 1, 0.32, 1) forwards;
        pointer-events: auto;
        border-left: 4px solid #2e7d32;
        transition: all 0.3s ease;
    }

    .notification-alert.error {
        border-left-color: #d32f2f;
    }

    .notification-alert.fade-out {
        animation: slideOutUp 0.4s cubic-bezier(0.23, 1, 0.32, 1) forwards;
    }

    @keyframes slideInDown {
        from {
            transform: translate3d(0, -100%, 0);
            opacity: 0;
        }

        to {
            transform: translate3d(0, 0, 0);
            opacity: 1;
        }
    }

    @keyframes slideOutUp {
        from {
            transform: translate3d(0, 0, 0);
            opacity: 1;
        }

        to {
            transform: translate3d(0, -100%, 0);
            opacity: 0;
        }
    }

    html {
        scroll-behavior: smooth;
    }

    .content-wrapper {
        will-change: transform;
        transform: translateZ(0);
    }

    .alert-animate-in {
        animation: slideInRight 0.4s ease-out forwards;
    }

    .alert-animate-out {
        animation: slideOutRight 0.4s ease-in forwards;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }

        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    /* Center notifications at the top of the screen */
    #notification-container {
        left: 50%;
        transform: translateX(-50%);
        width: 100%;
        max-width: 500px;
        text-align: center;
    }

    /* Card design consistency */
    .card-header {
        background-image: linear-gradient(to right, #ecfdf5, #d1fae5);
        border-bottom: 1px solid rgba(209, 250, 229, 0.5);
    }

    /* Custom scrollbar */
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

    /* Scroll to top button */
    #back-to-top {
        position: fixed;
        bottom: 70px;
        right: 20px;
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
    @stack('styles')
</head>

<body>
    <script>
    // Define dismissAlert function at the top of the body
    function dismissAlert(alertId) {
        const alert = document.getElementById(alertId);
        if (alert) {
            alert.classList.remove('alert-animate-in');
            alert.classList.add('alert-animate-out');
            setTimeout(function() {
                alert.remove();
            }, 400);
        }
    }
    </script>

    <!-- Container untuk notifikasi - moved to top of body for highest priority -->
    <div id="notification-container" class="notification-container"></div>

    <!-- Navigation -->
    @include('user.layouts.navigation')

    <div class="pt-16 min-h-screen">
        <!-- Notification container - moved to top center -->
        <div id="notification-container" class="fixed top-20 z-50"></div>

        <main class="pb-24">
            @yield('content')
        </main>
    </div>

    <!-- Footer yang fixed di bagian bawah -->
    <footer class="bg-white shadow-inner py-4 border-t fixed bottom-0 left-0 right-0 z-10 backdrop-blur-sm bg-white/80">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <p class="text-gray-700 font-medium">© {{ date('Y') }} Rumah Sakit Azra. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scroll to top button -->
    <div id="back-to-top"
        class="fixed bottom-20 right-6 opacity-0 invisible transition-all duration-300 transform translate-y-10">
        <button onclick="scrollToTop()"
            class="bg-gradient-to-br from-green-500 to-green-600 text-white p-3 rounded-full shadow-lg hover:shadow-xl focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18">
                </path>
            </svg>
        </button>
    </div>

    <!-- Spacer untuk mencegah konten tertutup footer -->


    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const alerts = ['alert-success', 'alert-error'];
        alerts.forEach(function(alertId) {
            const alert = document.getElementById(alertId);
            if (alert) {
                setTimeout(function() {
                    if (alert && alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 5000); // Increased to 5000ms (5 seconds)
            }
        });

        // Show/hide back-to-top button on scroll
        const backToTopBtn = document.getElementById("back-to-top");

        window.addEventListener("scroll", function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.remove("opacity-0", "invisible", "translate-y-10");
                backToTopBtn.classList.add("opacity-100", "visible", "translate-y-0");
            } else {
                backToTopBtn.classList.remove("opacity-100", "visible", "translate-y-0");
                backToTopBtn.classList.add("opacity-0", "invisible", "translate-y-10");
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

    // Fungsi untuk membuat notifikasi yang konsisten dengan admin layout
    function createNotification(message, type) {
        // Hapus semua notifikasi yang ada terlebih dahulu
        const container = document.getElementById('notification-container');
        if (!container) return;

        // Buat notifikasi baru
        const notification = document.createElement('div');
        notification.className = "notification-alert";

        if (type === 'success') {
            notification.innerHTML = `
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">${message}</p>
                        </div>
                        <div class="ml-auto pl-3">
                            <button onclick="this.parentNode.parentNode.parentNode.parentNode.remove()" class="text-green-600 hover:text-green-800">
                                <span class="sr-only">Close</span>
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
            `;
        } else {
            notification.innerHTML = `
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">${message}</p>
                        </div>
                        <div class="ml-auto pl-3">
                            <button onclick="this.parentNode.parentNode.parentNode.parentNode.remove()" class="text-red-600 hover:text-red-800">
                                <span class="sr-only">Close</span>
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
            `;
        }

        container.appendChild(notification);

        // Auto dismiss after 5 seconds (increased from 4)
        setTimeout(function() {
            if (notification && notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000); // Increased to 5000ms (5 seconds)
    }

    // Cek apakah ada pesan flash dari server saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        @if(session('success'))
        createNotification("{{ session('success') }}", 'success');
        @endif

        @if(session('error'))
        createNotification("{{ session('error') }}", 'error');
        @endif
    });
    </script>

    @stack('scripts')
</body>

</html>