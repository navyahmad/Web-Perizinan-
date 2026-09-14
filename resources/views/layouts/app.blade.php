<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Website Izin Kantor') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Scripts and Styles via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>
<body class="min-h-full flex flex-col text-slate-800 antialiased font-sans bg-slate-50">

    <!-- Top Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-xs" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo & Brand -->
                <div class="flex items-center">
                    <a href="{{ route('public.form') }}" class="flex items-center space-x-3 group">
                        <img src="{{ asset('images/logo-generalsolusindo.png') }}" alt="General Solusindo"
                            class="h-9 sm:h-10 w-auto object-contain">
                    </a>
                </div>

                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex items-center space-x-1 lg:space-x-2">
                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                Dashboard Admin
                            </a>
                        @else
                            <a href="{{ route('hrd.dashboard') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('hrd.dashboard') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                Dashboard HRD
                            </a>
                        @endif

                        <a href="{{ route('requests.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('requests.index') || request()->routeIs('requests.show') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            Pengajuan Izin
                        </a>

                        <a href="{{ route('requests.history') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('requests.history') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            Riwayat
                        </a>

                        <a href="{{ route('reports.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('reports.index') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            Laporan
                        </a>
                    @else
                        <a href="{{ route('public.form') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('public.form') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            Ajukan Izin
                        </a>
                        <a href="{{ route('status.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('status.index') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            Cek Status
                        </a>
                    @endauth
                </nav>

                <!-- Right Action / User Profile -->
                <div class="hidden md:flex items-center space-x-3">
                    @auth
                        <div class="flex items-center space-x-3 pl-3 border-l border-slate-200">
                            <a href="{{ route('profile.show') }}" class="flex items-center space-x-2 text-sm text-slate-700 hover:text-indigo-600 font-medium">
                                <span class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-700 uppercase">
                                    {{ substr(auth()->user()->name, 0, 2) }}
                                </span>
                                <span>{{ auth()->user()->name }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ auth()->user()->isAdmin() ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ strtoupper(auth()->user()->role) }}
                                </span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-slate-500 hover:text-rose-600 p-1.5 rounded-md hover:bg-rose-50 transition" title="Keluar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center text-xs font-semibold px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 transition shadow-2xs">
                            <svg class="w-3.5 h-3.5 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Login
                        </a>
                    @endauth
                </div>

                <!-- Mobile Hamburger Toggle -->
                <div class="flex items-center md:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="p-2 rounded-md text-slate-400 hover:text-slate-500 hover:bg-slate-100 focus:outline-hidden">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <div x-show="mobileMenuOpen" class="md:hidden border-b border-slate-200 bg-white px-4 pt-2 pb-4 space-y-1">
            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-md text-base font-medium text-slate-700 hover:bg-slate-50">Dashboard Admin</a>
                @else
                    <a href="{{ route('hrd.dashboard') }}" class="block px-3 py-2 rounded-md text-base font-medium text-slate-700 hover:bg-slate-50">Dashboard HRD</a>
                @endif
                <a href="{{ route('requests.index') }}" class="block px-3 py-2 rounded-md text-base font-medium text-slate-700 hover:bg-slate-50">Pengajuan Izin</a>
                <a href="{{ route('requests.history') }}" class="block px-3 py-2 rounded-md text-base font-medium text-slate-700 hover:bg-slate-50">Riwayat</a>
                <a href="{{ route('reports.index') }}" class="block px-3 py-2 rounded-md text-base font-medium text-slate-700 hover:bg-slate-50">Laporan</a>
                <a href="{{ route('profile.show') }}" class="block px-3 py-2 rounded-md text-base font-medium text-slate-700 hover:bg-slate-50">Profil ({{ auth()->user()->name }})</a>
                <form method="POST" action="{{ route('logout') }}" class="pt-2 border-t border-slate-100">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-2 rounded-md text-base font-medium text-rose-600 hover:bg-rose-50">Keluar</button>
                </form>
            @else
                <a href="{{ route('public.form') }}" class="block px-3 py-2 rounded-md text-base font-medium text-slate-700 hover:bg-slate-50">Ajukan Izin</a>
                <a href="{{ route('status.index') }}" class="block px-3 py-2 rounded-md text-base font-medium text-slate-700 hover:bg-slate-50">Cek Status Pengajuan</a>
                <a href="{{ route('login') }}" class="block px-3 py-2 rounded-md text-base font-medium text-indigo-600 hover:bg-indigo-50">Login Staf</a>
            @endauth
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="grow py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Global Flash Alerts -->
            @if(session('success'))
                <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-start shadow-xs" role="alert">
                    <svg class="w-5 h-5 text-emerald-500 mr-3 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <span class="font-semibold">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 flex items-start shadow-xs" role="alert">
                    <svg class="w-5 h-5 text-rose-500 mr-3 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <span class="font-semibold">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 shadow-xs" role="alert">
                    <div class="flex items-center mb-2 font-semibold">
                        <svg class="w-5 h-5 text-rose-500 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        Terdapat kesalahan pada input formulir:
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-xs text-rose-700 pl-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot ?? '' }}
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 space-y-2 sm:space-y-0">
            <div>
                &copy; {{ date('Y') }} <strong>General Solusindo &bull; Pengajuan Izin</strong> &bull; General Solusindo & Tabinaco
            </div>
            <div class="flex items-center space-x-4">
                <span>Zona Waktu: <strong>WIB (Asia/Jakarta)</strong></span>
                <span>&bull;</span>
                <span>Jam Masuk Kerja: <strong>08.30 WIB</strong></span>
            </div>
        </div>
    </footer>

</body>
</html>
