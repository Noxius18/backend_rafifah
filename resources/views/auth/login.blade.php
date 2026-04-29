@extends('layouts.app')

@section('content')
</div>

<div class="flex items-center justify-center min-h-screen bg-cover bg-center" style="background-image: url('{{ asset("assets/bg-login.webp") }}')">
    <div class="w-full max-w-md mx-auto p-6">

        <x-ui.card class="bg-white/90 p-6 rounded-xl shadow-xl">
            <div class="flex flex-col items-center space-y-4 mb-2">
                <img class="w-28" src="{{ asset('assets/Logo_1_transparent.png') }}" alt="Logo Pesantren">
                <h1 class="text-2xl font-bold text-center">Login</h1>
            </div>

            <form method="POST" action="#" @submit="isLoading = true" x-data="{ showPassword: false, isLoading: false }">
                @csrf
                <div class="card-body space-y-4">
                    <div class="form-control">
                        <label for="username" class="input input-bordered flex items-center gap-2">
                            <x-heroicon-o-user class="w-5 h-5 text-gray-400" />
                            <x-form.input type="text" name="username" placeholder="Username" class="grow border-0 focus:outline-none" />
                        </label>
                    </div>

                    <div class="form-control">
                        <label for="password" class="input input-bordered flex items-center gap-2">
                            <x-heroicon-o-lock-closed class="w-5 h-5 text-gray-400" />
                            <x-form.input x-bind:type="showPassword ? 'text' : 'password'" name="password" placeholder="Password" class="grow border-0 focus:outline-none" />
                            <button type="button" @click="showPassword = !showPassword" class="text-gray-400 hover:text-gray-600 transition">
                                <x-heroicon-o-eye x-show="!showPassword" class="w-5 h-5" />
                                <x-heroicon-o-eye-slash x-show="showPassword" class="w-5 h-5" />
                            </button>
                        </label>
                    </div>

                    <div class="card-action pt-2">
                        <x-ui.button class="btn bg-green-500 hover:bg-green-600 text-white w-full" x-bind:disabled="isLoading" x-bind:class="{ 'loading': isLoading }">
                            <span x-show="!isLoading">Login</span>
                            <span x-show="isLoading" class="loading loading-spinner"></span>
                        </x-ui.button>
                    </div>
                </div>
            </form>
        </x-ui.card>

    </div>
</div>
</div>
@endsection