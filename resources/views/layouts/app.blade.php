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

    {{-- Initialize MetisMenu & SimpleBar, then mark the active sidebar link --}}
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var role = "{{ $role ?? 'guest' }}";

            // Locate the sidebar <ul> — try role-namespaced ID, then plain "sidenav",
            // then any .metismenu element as a final fallback.
            var element = document.getElementById(role + '-sidenav')
                       || document.getElementById('sidenav')
                       || document.querySelector('ul.metismenu');

            // ── 1. Initialize MetisMenu ──
            if (element && typeof MetisMenu !== 'undefined') {
                try {
                    if (window.metisMenuInstance) { window.metisMenuInstance.dispose(); }
                } catch (e) {}
                window.metisMenuInstance = new MetisMenu(element, { toggle: true });
            }

            // ── 2. Active-link detection ──
            // Find the <a> whose href best matches the current URL, then walk up the
            // DOM adding mm-active / mm-show so the right submenu is open on load.
            (function markActiveLink() {
                var currentPath = window.location.pathname;
                var bestMatch   = null;
                var bestLen     = 0;

                document.querySelectorAll('.metismenu a[href]').forEach(function (a) {
                    // Skip javascript: void links (parent toggles)
                    if (!a.href || a.href.indexOf('javascript') === 0) return;
                    try {
                        var linkPath = new URL(a.href, window.location.origin).pathname;
                        // Exact match preferred; longest prefix match as fallback
                        var isExact  = currentPath === linkPath;
                        var isPrefix = currentPath.startsWith(linkPath) && linkPath !== '/';
                        if ((isExact || isPrefix) && linkPath.length > bestLen) {
                            bestLen   = linkPath.length;
                            bestMatch = a;
                        }
                    } catch (e) {}
                });

                if (!bestMatch) return;

                // Highlight the matched link
                bestMatch.classList.add('active');

                // Walk every ancestor <li> up to the root <ul> and mark it active
                var node = bestMatch.parentElement;
                while (node && node !== element) {
                    if (node.tagName === 'LI') {
                        node.classList.add('mm-active');
                    }
                    // Expand any <ul> sub-list that is a direct child of an active <li>
                    if (node.tagName === 'UL' && node !== element) {
                        node.classList.add('mm-show');
                        // Remove any inline height:0 that MetisMenu may have set before
                        // active detection ran, so the list is fully visible.
                        node.style.height = '';
                    }
                    node = node.parentElement;
                }
            })();

            // ── 3. Initialize SimpleBar (if available) ──
            var sidebarWrapper = document.querySelector('.sidebar-wrapper[data-simplebar]');
            if (sidebarWrapper && typeof SimpleBar !== 'undefined') {
                new SimpleBar(sidebarWrapper);
            }
        });
    </script>

    {{-- Page-specific scripts --}}
    @stack('script')

</body>
</html>
