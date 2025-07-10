@extends('layouts.app')

@section('title', 'Iniciar Sesión')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <h2 class="text-center mb-4" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">
            Iniciar Sesión
        </h2>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input id="email" 
                       type="email" 
                       class="form-input @error('email') border-red-500 @enderror" 
                       name="email" 
                       value="{{ old('email') }}" 
                       required 
                       autocomplete="email" 
                       autofocus>
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Contraseña</label>
                <input id="password" 
                       type="password" 
                       class="form-input @error('password') border-red-500 @enderror" 
                       name="password" 
                       required 
                       autocomplete="current-password">
                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center;">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <span style="margin-left: 0.5rem; font-size: 0.875rem; color: #374151;">
                        Recordarme
                    </span>
                </label>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    Iniciar Sesión
                </button>
            </div>
        </form>

        <div class="text-center mt-4">
            <p class="text-sm" style="color: #6b7280;">
                ¿No tienes una cuenta? 
                <a href="{{ route('register') }}" class="text-blue-600">
                    Regístrate aquí
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
