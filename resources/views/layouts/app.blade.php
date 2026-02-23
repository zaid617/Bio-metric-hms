<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark-theme">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | Body Experts</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ URL::asset('build/images/favicon-32x32.png') }}" type="image/png">

    {{-- Common CSS & page-specific CSS --}}
    @include('layouts.head-css')
    @stack('css')
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/notifications/css/lobibox.min.css') }}">

    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-y: auto !important;
        }

        .main-wrapper {
            min-height: 100vh;
            overflow-y: auto !important;
        }

        .sidebar-wrapper {
            height: 100vh !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
        }

        .sidebar-wrapper::-webkit-scrollbar {
            width: 8px;
        }
        .sidebar-wrapper::-webkit-scrollbar-thumb {
            background-color: #a0a0a0;
            border-radius: 4px;
        }
        .sidebar-wrapper::-webkit-scrollbar-thumb:hover {
            background-color: #888;
        }
    </style>
</head>

<body>

    {{-- Top navigation bar --}}
    @include('layouts.topbar')

    {{-- Sidebar menu --}}
    @php
        $role = null;

        // Web guard users: admin, manager, receptionist
        if(auth()->check()) {
            $role = auth()->user()->roles->first()->name ?? null;
        } 
        // Doctor guard
        elseif(auth('doctor')->check()) {
            $role = 'doctor';
        }
    @endphp

    @if($role === 'admin')
        @include('layouts.sidebar', ['role' => $role])
    @elseif($role === 'manager')
        @include('layouts.manager-sidebar', ['role' => $role])
    @elseif($role === 'doctor')
        @include('layouts.doctor-sidebar', ['role' => $role])
    @elseif($role === 'receptionist')
        @include('layouts.receptionist_sidebar', ['role' => $role])
    @else
        <div class="sidebar-wrapper">
            <p style="color:red; padding:10px;">Please login first to access the menu.</p>
        </div>
    @endif

    <!--start main wrapper-->
    <main class="main-wrapper">
        <div class="main-content">
            {{-- Page Content --}}
            @yield('content')
        </div>
    </main>

    <!--start overlay-->
    <div class="overlay btn-toggle"></div>
    <!--end overlay-->

    {{-- Extra layout elements --}}
    @include('layouts.extra')

    {{-- Common JS scripts --}}
    @include('layouts.common-scripts')

    {{-- Initialize MetisMenu & SimpleBar --}}
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var role = "{{ $role ?? 'guest' }}";
            var sidebarId = role + "-sidenav";
            var element = document.getElementById(sidebarId);

            // Initialize MetisMenu
            if (element) {
                if (window.metisMenuInstance) {
                    window.metisMenuInstance.dispose();
                }
                window.metisMenuInstance = new MetisMenu(element, { toggle: true });
            }

            // Optional: Initialize SimpleBar (agar library load ho)
            var sidebarWrapper = document.querySelector('.sidebar-wrapper[data-simplebar]');
            if (sidebarWrapper && typeof SimpleBar !== "undefined") {
                new SimpleBar(sidebarWrapper);
            }
        });
    </script>

    {{-- Page-specific scripts --}}
    @stack('script')

</body>
</html>
