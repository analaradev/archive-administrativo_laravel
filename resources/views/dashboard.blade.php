<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Dashboard</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Figtree', sans-serif;
            background-color: #f8fafc;
            margin: 0;
        }
        .dashboard-container {
            min-height: 100vh;
        }
        .header {
            background-color: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }
        .header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .user-name {
            color: #374151;
            font-weight: 500;
        }
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        .btn-danger {
            background-color: #dc2626;
            color: white;
        }
        .btn-danger:hover {
            background-color: #b91c1c;
        }
        .main-content {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .welcome-card {
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .text-center {
            text-align: center;
        }
        .text-muted {
            color: #6b7280;
        }
        .mb-4 {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <header class="header">
            <h1>Dashboard Administrativo</h1>
            <div class="user-info">
                <span class="user-name">{{ $user->name }}</span>
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Welcome Card -->
            <div class="welcome-card">
                <h2 style="margin: 0 0 1rem 0; color: #1f2937; font-size: 1.5rem;">
                    ¡Bienvenido, {{ $user->name }}!
                </h2>
                <p class="text-muted mb-4">
                    Desde aquí puedes gestionar todas las funcionalidades del sistema administrativo.
                </p>
                <div class="text-center">
                    <p class="text-muted" style="font-size: 0.875rem;">
                        <strong>Email:</strong> {{ $user->email }}<br>
                        <strong>Cuenta creada:</strong> {{ $user->created_at->format('d/m/Y H:i') }}
                    </p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">1</div>
                    <div class="stat-label">Usuarios Registrados</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number">{{ date('d') }}</div>
                    <div class="stat-label">Día del Mes</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number">{{ date('H:i') }}</div>
                    <div class="stat-label">Hora Actual</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Sistema Activo</div>
                </div>
            </div>

            <!-- Future Content Area -->
            <div class="welcome-card" style="margin-top: 2rem;">
                <h3 style="margin: 0 0 1rem 0; color: #1f2937;">
                    Próximas Funcionalidades
                </h3>
                <p class="text-muted">
                    Este dashboard se expandirá con más funcionalidades administrativas según las necesidades del proyecto.
                </p>
            </div>
        </main>
    </div>
</body>
</html>
