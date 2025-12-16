<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes pulse-glow {

            0%,
            100% {
                box-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
            }

            50% {
                box-shadow: 0 0 30px rgba(255, 255, 255, 0.5);
            }
        }

        .animated-gradient {
            background: linear-gradient(-45deg, #0a0f3d, #0016a4, #0320dc, #1e3a8a, #0016a4);
            background-size: 400% 400%;
            animation: gradient 20s ease infinite;
            position: relative;
            overflow: hidden;
        }

        .animated-gradient::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: float 10s ease-in-out infinite;
            opacity: 0.3;
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border: 1.5px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .input-field {
            transition: all 0.3s ease;
        }

        .input-field:focus {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .logo-container {
            animation: pulse-glow 3s ease-in-out infinite;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffffff 0%, #f0f0f0 100%);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(3, 32, 220, 0.1);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-primary:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .slide-up {
            animation: slideUp 0.6s ease-out;
        }

        .icon-hover {
            transition: all 0.3s ease;
        }

        .input-field:focus+.icon-hover {
            transform: scale(1.2);
            color: white;
        }

        .icon-dark-blue {
        color: #1e3a8a !important; /* or any dark blue you prefer */
        }

    </style>
</head>

<body class="animated-gradient min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md slide-up">
        <!-- Login Card -->
        <div class="glass-effect rounded-3xl shadow-2xl p-8 md:p-10 relative">
            <!-- Decorative elements -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-32 h-32 bg-white opacity-5 rounded-full blur-3xl"></div>

            <!-- Logo/Header -->
            <div class="text-center mb-8 relative z-10">
                <div
                    class="inline-flex items-center justify-center w-24 h-24 bg-white rounded-full mb-4 logo-container p-0.5">
                    <img src="psu_logo.png" alt="PSU Logo" class="w-full h-full object-contain" />
                </div>
                <h2 class="text-4xl font-bold text-white mb-2 tracking-tight">Welcome Back</h2>
                <p class="text-white text-opacity-90 text-base">Please sign in to your account</p>
            </div>

            <!-- Session Status -->
            @if (session('status'))
                <div
                    class="mb-6 p-4 bg-green-400 bg-opacity-20 border border-green-300 border-opacity-50 rounded-xl text-green-100 text-sm backdrop-blur-sm">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}" class="space-y-6 relative z-10">
                @csrf

                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-white mb-2 ml-1">
                        {{ __('Email') }}
                    </label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 icon-dark-blue icon-hover" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207">
                                </path>
                            </svg>
                        </div>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                            autocomplete="username"
                            class="input-field w-full pl-12 pr-4 py-3.5 bg-white bg-opacity-15 border border-white border-opacity-25 rounded-xl text-white placeholder-white placeholder-opacity-60 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-40 focus:border-white focus:border-opacity-50"
                            placeholder="you@example.com" />
                    </div>
                    @error('email')
                        <p class="mt-2 text-sm text-red-200 ml-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-white mb-2 ml-1">
                        {{ __('Password') }}
                    </label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 icon-dark-blue icon-hover" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                </path>
                            </svg>
                        </div>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                            class="input-field w-full pl-12 pr-4 py-3.5 bg-white bg-opacity-15 border border-white border-opacity-25 rounded-xl text-white placeholder-white placeholder-opacity-60 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-40 focus:border-white focus:border-opacity-50"
                            placeholder="••••••••" />
                    </div>
                    @error('password')
                        <p class="mt-2 text-sm text-red-200 ml-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between pt-2">
                    <label class="flex items-center cursor-pointer group">
                        <input id="remember_me" type="checkbox" name="remember"
                            class="w-4 h-4 rounded border-2 border-white border-opacity-30 bg-white bg-opacity-15 text-blue-600 focus:ring-2 focus:ring-white focus:ring-opacity-50 cursor-pointer" />
                        <span
                            class="ml-2 text-sm text-white group-hover:text-opacity-90 transition">{{ __('Remember me') }}</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}"
                            class="text-sm text-white font-medium hover:text-opacity-80 transition-all hover:underline">
                            {{ __('Forgot password?') }}
                        </a>
                    @endif
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="btn-primary w-full py-4 px-4 text-blue-900 font-bold text-base rounded-xl shadow-lg relative z-10">
                    {{ __('Log in') }}
                </button>
            </form>

            <!-- Footer -->
            <!-- <div class="mt-8 text-center relative z-10">
                <p class="text-sm text-white text-opacity-80">
                    Don't have an account?
                    <a href="#" class="font-semibold text-white hover:text-opacity-90 transition hover:underline">Sign up</a>
                </p>
            </div> -->
        </div>

    </div>
</body>

</html>