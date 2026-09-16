<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk - Help Desk RS AZRA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon_azra.png') }}">
    <style>
        body {
            font-family: 'Instrument Sans', sans-serif;
        }

        .auth-left {
            background: #f8fafc;
            background-image:
                linear-gradient(120deg, rgba(0, 119, 116, 0.1), rgba(0, 119, 116, 0.05)),
                url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23007774' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .auth-right {
            background-color: #007774;
            background-image: linear-gradient(160deg, #007774 0%, #005f5c 100%);
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #0f172a;
        }

        .input-group input {
            width: 100%;
            padding: 0.75rem 2.5rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s;
            background: #fff;
            color: #0f172a;
        }

        .input-group input:focus {
            outline: none;
            border-color: #007774;
            box-shadow: 0 0 0 3px rgba(0, 119, 116, 0.15);
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            cursor: pointer;
            font-size: 1.25rem;
            z-index: 1;
        }

        .login-btn {
            width: 100%;
            background: #007774;
            color: white;
            border: none;
            padding: 0.875rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .login-btn:hover {
            background: #005f5c;
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .success-message {
            background: #f0fdf4;
            border: 1px solid #dcfce7;
            color: #007774;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>

<body class="min-h-screen">
    <div class="login-container flex flex-col lg:flex-row min-h-screen">
        <!-- Left Side - Branding Panel -->
        <div
            class="auth-right flex flex-col justify-center items-center min-h-[30vh] lg:min-h-screen lg:w-3/5 px-6 py-8 lg:order-1">
            <div class="hospital-logo">
                <img src="{{ asset('images/logo-azra-mutu.png') }}" alt="Logo RS Azra"
                    class="w-40 lg:w-72 h-auto mx-auto mb-4">
            </div>
            <h1 class="login-title text-3xl lg:text-5xl font-semibold text-white text-center tracking-widest break-words">
                SISTEM INFORMASI HELP DESK
            </h1>
            <p class="login-subtitle text-white text-sm text-center mt-2">
                Layanan Tiket & Perbaikan Rumah Sakit Azra
            </p>
        </div>

        <!-- Right Side - Login Form -->
        <div class="auth-left flex justify-center items-center min-h-[70vh] lg:min-h-screen lg:w-2/5 px-6 py-8 lg:order-2">
            <form method="POST" action="{{ route('login') }}" class="w-full max-w-md">
                @csrf

                @if(session('error'))
                <div class="error-message">
                    <i class="ri-error-warning-line"></i>
                    <span>{{ session('error') }}</span>
                </div>
                @endif

                @if(session('success'))
                <div class="success-message">
                    <i class="ri-checkbox-circle-line"></i>
                    <span>{{ session('success') }}</span>
                </div>
                @endif

                <h2 class="text-2xl font-semibold text-slate-800 mb-1">Masuk</h2>
                <p class="text-slate-500 text-sm mb-6">Gunakan akun Anda untuk melanjutkan</p>

                <div class="input-group">
                    <label for="login">Nama Pengguna</label>
                    <div class="input-icon-wrapper">
                        <input type="text" name="login" id="login" value="{{ old('login', old('email')) }}" required
                            autofocus placeholder="Masukkan nama pengguna"
                            class="@error('login') border-red-400 @enderror @error('email') border-red-400 @enderror">
                        <i class="ri-user-line input-icon"></i>
                    </div>
                    @error('login')
                    <p class="mt-1 text-red-500 text-sm">{{ $message }}</p>
                    @enderror
                </div>

                <div class="input-group">
                    <label for="password">Kata Sandi</label>
                    <div class="input-icon-wrapper">
                        <input type="password" name="password" id="password" required placeholder="Masukkan kata sandi"
                            @error('password') class="border-red-400" @enderror>
                        <i class="ri-lock-line input-icon toggle-password"></i>
                    </div>
                    @error('password')
                    <p class="mt-1 text-red-500 text-sm">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between mb-5">
                    <label class="flex items-center text-sm text-slate-600 cursor-pointer">
                        <input type="checkbox" name="remember" id="remember"
                            class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                        <span class="ml-2">Ingat saya</span>
                    </label>
                </div>

                <button type="submit" class="login-btn">
                    <i class="ri-login-circle-line"></i>
                    Masuk
                </button>

                
            </form>
        </div>
    </div>

    <script>
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            if (input.type === 'password') {
                input.type = 'text';
                this.classList.remove('ri-lock-line');
                this.classList.add('ri-lock-unlock-line');
            } else {
                input.type = 'password';
                this.classList.remove('ri-lock-unlock-line');
                this.classList.add('ri-lock-line');
            }
        });
    </script>
</body>

</html>