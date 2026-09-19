@extends('layouts.app')

@section('content')
<div class="min-h-[70vh] flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <img src="{{ asset('images/logo-generalsolusindo.png') }}" alt="General Solusindo"
            class="h-12 w-auto mx-auto object-contain">
        <h2 class="mt-4 text-center text-2xl font-bold tracking-tight text-slate-900">
            Login Staf Kantor
        </h2>
        <p class="mt-1 text-center text-sm text-slate-700">
            Khusus Manager & HRD untuk persetujuan izin
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-6 shadow-sm rounded-xl border border-slate-200 sm:px-10">
            <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="username" class="block text-sm font-medium text-slate-700">
                        Username <span class="text-rose-500">*</span>
                    </label>
                    <div class="mt-1">
                        <input id="username" name="username" type="text" autocomplete="username" required
                            value="{{ old('username') }}"
                            class="block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-slate-900 shadow-2xs placeholder:text-slate-400 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 sm:text-sm"
                            placeholder="Masukkan username Anda">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">
                        Password <span class="text-rose-500">*</span>
                    </label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            class="block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-slate-900 shadow-2xs placeholder:text-slate-400 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 sm:text-sm"
                            placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox"
                            class="h-4 w-4 rounded-sm border-slate-300 text-indigo-600 focus:ring-indigo-600">
                        <label for="remember" class="ml-2 block text-sm text-slate-700">
                            Ingat saya di perangkat ini
                        </label>
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600 transition">
                        Masuk ke Dashboard
                    </button>
                </div>
            </form>
        </div>

        <div class="text-center mt-6">
            <a href="{{ route('public.form') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                &larr; Kembali ke Form Pengajuan Izin
            </a>
        </div>
    </div>
</div>
@endsection
