<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-ajra.jpg') }}">

    <!-- Tambahkan Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
    body {
        font-family: 'Poppins', sans-serif;
        background-image: url('{{ asset('images/azralogin.jpg') }}');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
    }

    .auth-container {
        background-color: rgba(255, 255, 255, 0.95);
    }
    </style>
</head>

<body class="bg-white">
    <div class="min-h-screen flex items-center justify-center p-6 lg:p-8">
        <div class="w-full max-w-6xl auth-container rounded-2xl shadow-lg flex overflow-hidden">
            <!-- Left Side - Register Form -->
            <div class="w-full lg:w-1/2 p-8 lg:p-12">
                <div class="max-w-md mx-auto">
                    <h2 class="text-3xl font-bold text-gray-800 mb-2">Create Account</h2>
                    <p class="text-gray-600 mb-8">Join us today! Please enter your details</p>

                    @if ($errors->any())
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md">
                        <ul>
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <form method="POST" action="{{ route('register') }}">
                        @csrf
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-semibold mb-2" for="name">
                                Full Name
                            </label>
                            <input
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition-colors"
                                id="name" type="text" name="name" value="{{ old('name') }}"
                                placeholder="Enter your full name" required>
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-semibold mb-2" for="email">
                                Username
                            </label>
                            <input
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition-colors"
                                id="email" type="text" name="email" value="{{ old('email') }}"
                                placeholder="Enter your username" required>
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-semibold mb-2" for="phone">
                                Phone Number
                            </label>
                            <input
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition-colors"
                                id="phone" type="text" name="phone" value="{{ old('phone') }}"
                                placeholder="Enter your phone number" required>
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-semibold mb-2" for="position">
                                Position
                            </label>
                            <select name="position" id="position" required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition-colors">
                                <option value="">Select Position</option>
                                @foreach($positions as $code => $name)
                                <option value="{{ $code }}" {{ old('position') == $code ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                                @endforeach
                            </select>
                            @error('position')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-semibold mb-2" for="password">
                                Password
                            </label>
                            <div class="relative">
                                <input
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition-colors"
                                    id="password" type="password" name="password" placeholder="Create a password"
                                    required minlength="3">
                                <button type="button" onclick="togglePassword('password', 'eyeIcon')"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" id="eyeIcon" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Password must be minimum 3 characters</p>
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-semibold mb-2" for="password_confirmation">
                                Confirm Password
                            </label>
                            <div class="relative">
                                <input
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition-colors"
                                    id="password_confirmation" type="password" name="password_confirmation"
                                    placeholder="Confirm your password" required minlength="3">
                                <button type="button"
                                    onclick="togglePassword('password_confirmation', 'eyeIconConfirm')"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" id="eyeIconConfirm"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <button type="submit"
                                class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors duration-200">
                                Create Account
                            </button>
                            <div class="text-center">
                                <span class="text-gray-600">Already have an account?</span>
                                <a href="{{ route('login') }}"
                                    class="text-blue-600 hover:text-blue-700 font-semibold ml-1">
                                    Sign In
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Side - Image -->
            <div class="hidden lg:block lg:w-1/2">
                <div class="h-full">
                    <img src="{{ asset('images/registerpage.jpg') }}" alt="Register illustration"
                        class="w-full h-full object-cover">
                </div>
            </div>
        </div>
    </div>

    <script>
    function togglePassword(inputId, iconId) {
        const passwordInput = document.getElementById(inputId);
        const eyeIcon = document.getElementById(iconId);

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                `;
        } else {
            passwordInput.type = 'password';
            eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                `;
        }
    }
    </script>
</body>

</html>