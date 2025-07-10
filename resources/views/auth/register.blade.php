@extends('layouts.app')

@section('title', 'Registro')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <h2 class="text-center mb-4" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">
            Crear Cuenta
        </h2>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="form-group">
                <label for="name" class="form-label">Nombre</label>
                <input id="name" 
                       type="text" 
                       class="form-input @error('name') border-red-500 @enderror" 
                       name="name" 
                       value="{{ old('name') }}" 
                       required 
                       autocomplete="name" 
                       autofocus>
                @error('name')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input id="email" 
                       type="email" 
                       class="form-input @error('email') border-red-500 @enderror" 
                       name="email" 
                       value="{{ old('email') }}" 
                       required 
                       autocomplete="email">
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
                       autocomplete="new-password">
                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
                <input id="password_confirmation" 
                       type="password" 
                       class="form-input" 
                       name="password_confirmation" 
                       required 
                       autocomplete="new-password">
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    Registrarse
                </button>
            </div>
        </form>

        <div class="text-center mt-4">
            <p class="text-sm" style="color: #6b7280;">
                ¿Ya tienes una cuenta? 
                <a href="{{ route('login') }}" class="text-blue-600">
                    Inicia sesión aquí
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
