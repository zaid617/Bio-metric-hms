<!doctype html>
<html lang="en" data-bs-theme="dark-theme">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 — Access Forbidden | Body Experts</title>
    <link rel="icon" href="{{ URL::asset('build/images/favicon-32x32.png') }}" type="image/png">
    @include('layouts.head-css')
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .error-box {
            text-align: center;
            padding: 3rem 2rem;
            max-width: 520px;
        }
        .error-code {
            font-size: 7rem;
            font-weight: 800;
            line-height: 1;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .error-icon { font-size: 4rem; color: #e74c3c; margin-bottom: 1rem; }
        .btn-back { min-width: 160px; }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-icon">
            <span class="material-icons-outlined" style="font-size:inherit;">lock</span>
        </div>
        <div class="error-code">403</div>
        <h3 class="mt-3 mb-2">Access Forbidden</h3>
        <p class="text-muted mb-4">
            {{ $exception->getMessage() ?: 'You do not have permission to access this page.' }}
        </p>
        <div class="d-flex gap-2 justify-content-center flex-wrap">
            <a href="javascript:history.back()" class="btn btn-outline-secondary btn-back">
                <i class="material-icons-outlined me-1" style="vertical-align:-4px;">arrow_back</i>Go Back
            </a>
            @if(auth()->check())
                @php
                    $homeRoute = match(auth()->user()->roles->first()->name ?? '') {
                        'admin','super-admin' => route('admin.dashboard'),
                        'manager'            => route('manager.dashboard'),
                        'receptionist'       => route('receptionist.dashboard'),
                        'view-only-admin'    => route('view-only-admin.dashboard'),
                        default              => route('home'),
                    };
                @endphp
                <a href="{{ $homeRoute }}" class="btn btn-primary btn-back">
                    <i class="material-icons-outlined me-1" style="vertical-align:-4px;">home</i>Dashboard
                </a>
            @elseif(auth('doctor')->check())
                <a href="{{ route('doctor.dashboard') }}" class="btn btn-primary btn-back">
                    <i class="material-icons-outlined me-1" style="vertical-align:-4px;">home</i>Dashboard
                </a>
            @else
                <a href="{{ url('/login') }}" class="btn btn-primary btn-back">
                    <i class="material-icons-outlined me-1" style="vertical-align:-4px;">login</i>Login
                </a>
            @endif
        </div>
    </div>
</body>
</html>
