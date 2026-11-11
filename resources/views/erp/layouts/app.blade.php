<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('erp.app_name')) — {{ config('erp.app_name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">

    <link rel="icon" type="image/png" href="{{ asset('erp-assets/consultisor-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('erp-assets/kanakku-theme.css') }}?v=8">

    {{-- Tailwind CDN — kept for compatibility with all module views --}}
    <script src="{{ asset('erp-assets/tailwind.js') }}?v=2"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { crimson: '#A91E3B', red: '#C91F32', coral: '#F43F1E', orange: '#FF6A1A', amber: '#FF9D18' },
                        ink: { DEFAULT: '#111111', light: '#151515', lighter: '#181818' },
                        paper: { DEFAULT: '#F8F8F7', alt: '#FAFAF9' },
                    },
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'], display: ['Poppins', 'Inter', 'sans-serif'] },
                    borderRadius: { card: '14px', btn: '9px', modal: '16px' },
                    boxShadow: { soft: '0 1px 3px rgba(17,17,17,0.04), 0 4px 20px rgba(17,17,17,0.05)' },
                }
            }
        }
    </script>
    <style>
        /* ===== Kanakku restyle overrides ===== */
        body.kanakku-theme .bg-white, body.kanakku-theme .shadow-soft, body.kanakku-theme .rounded-card {
            border-radius: 0.625rem;
        }
        body.kanakku-theme .shadow-soft {
            box-shadow: 0 4px 20px rgba(20, 10, 50, 0.06);
        }
        body.kanakku-theme .border-gray-200 { border-color: #e8e8f0; }
        body.kanakku-theme .border-gray-100 { border-color: #f0f0f5; }
        body.kanakku-theme .text-gray-500 { color: #6f7180; }
        body.kanakku-theme .text-gray-400 { color: #9aa0ae; }
        body.kanakku-theme .bg-gray-50 { background-color: #f8f9fc; }
        body.kanakku-theme .bg-gray-100 { background-color: #f3f4f8; }
        body.kanakku-theme .bg-gray-200 { background-color: #e4e6ed; }

        body.kanakku-theme table.w-full {
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
        }
        body.kanakku-theme table.w-full thead th {
            background: #f8f9fc;
            color: #6f7180;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 12px 16px;
            border-bottom: 1px solid #e8e8f0;
            white-space: nowrap;
        }
        body.kanakku-theme table.w-full tbody td {
            padding: 13px 16px;
            border-bottom: 1px solid #f0f0f5;
            color: #3f3f4a;
            vertical-align: middle;
        }
        body.kanakku-theme table.w-full tbody tr:hover td { background: #f8f9fc; }

        body.kanakku-theme .btn { border-radius: 8px; font-weight: 600; }

        body.kanakku-theme input[type="text"], body.kanakku-theme input[type="email"], body.kanakku-theme input[type="password"], body.kanakku-theme input[type="number"], body.kanakku-theme input[type="date"], body.kanakku-theme select, body.kanakku-theme textarea {
            border-radius: 9px !important;
            font-size: 13px !important;
        }
    </style>

    {{-- Tabler icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">

    <script src="{{ asset('erp-assets/alpine.min.js') }}" defer></script>

    <style>
        /* ===== Role-based theme (CSS variables) ===== */
        :root, [data-role="admin"] {
            --role-grad-1: #C91F32;   --role-grad-2: #F43F1E;   --role-grad-3: #FF6A1A;   --role-grad-4: #FF9D18;
            --role-accent: #F43F1E;   --role-accent-strong: #C91F32;   --role-accent-soft: rgba(244,63,30,0.14);
        }
        [data-role="hr"],
        [data-role="finance"],
        [data-role="csr"],
        [data-role="associate"] {
            --role-grad-1: #C91F32;   --role-grad-2: #F43F1E;   --role-grad-3: #FF6A1A;   --role-grad-4: #FF9D18;
            --role-accent: #F43F1E;   --role-accent-strong: #C91F32;   --role-accent-soft: rgba(244,63,30,0.14);
        }
        .consultiser-gradient {
            background-image: linear-gradient(135deg, var(--role-grad-1) 0%, var(--role-grad-2) 40%, var(--role-grad-3) 70%, var(--role-grad-4) 100%);
        }
        .consultiser-gradient-text {
            background-image: linear-gradient(135deg, var(--role-grad-1) 0%, var(--role-grad-2) 40%, var(--role-grad-3) 70%, var(--role-grad-4) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .role-text { color: var(--role-accent); }
        .role-hover-text:hover { color: var(--role-accent); }
        .role-bg { background-color: var(--role-accent); }
        .role-bg-soft { background-color: var(--role-accent-soft); }
        .role-ring { --tw-ring-color: var(--role-accent); }
        .role-border { border-color: var(--role-accent); }

        /* Preloader */
        #app-preloader { position: fixed; inset: 0; z-index: 9999; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 20px; background: #fff; transition: opacity 0.35s ease, visibility 0.35s ease; }
        #app-preloader.loaded { opacity: 0; visibility: hidden; pointer-events: none; }
        .preloader-logo { width: 64px; height: 64px; border-radius: 16px; padding: 12px; background: var(--gray-50); border: 1px solid var(--border-color); animation: preloader-pulse 1.6s ease-in-out infinite; }
        .preloader-brand { font-size: 17px; font-weight: 700; color: #14152a; letter-spacing: -0.02em; }
        .preloader-brand .consultiser-gradient-text { background-image: linear-gradient(135deg, var(--role-grad-1), var(--role-grad-2), var(--role-grad-3)); }
        .preloader-bar { width: 180px; height: 4px; border-radius: 999px; background: var(--gray-100); overflow: hidden; }
        .preloader-bar-fill { height: 100%; border-radius: 999px; background-image: linear-gradient(90deg, var(--role-grad-1), var(--role-grad-2), var(--role-grad-3)); animation: preloader-slide 1.4s cubic-bezier(0.4,0,0.2,1) infinite; }
        @keyframes preloader-pulse { 0%,100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.06); opacity: 0.85; } }
        @keyframes preloader-slide { 0% { width: 20%; } 50% { width: 90%; } 100% { width: 20%; } }

        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="kanakku-theme" data-role="{{ auth()->check() ? (match (auth()->user()->roles->pluck('name')->first()) { 'hr' => 'hr', 'finance' => 'finance', 'csr_manager' => 'csr', 'team_associate' => 'associate', default => 'admin' }) : 'admin' }}">

    {{-- Pre-loader Skeleton --}}
    <div id="app-preloader">
        <div class="preloader-logo">
            <img alt="Consultiser" class="w-full h-full object-contain" src="{{ asset('erp-assets/consultisor-logo.png') }}"/>
        </div>
        <div class="text-center">
            <div class="preloader-brand">Consultiser <span class="consultiser-gradient-text">ERP</span></div>
            <div class="text-[11px] text-gray-400 mt-1 tracking-widest uppercase" style="font-size:11px;color:#9aa0ae;letter-spacing:0.12em;">Loading workspace…</div>
        </div>
        <div class="preloader-bar"><div class="preloader-bar-fill"></div></div>
    </div>
    <script>
        (function() {
            var preloader = document.getElementById('app-preloader');
            if (!preloader) return;
            var minShown = Date.now() + 500;
            function hide() {
                var delay = Math.max(0, minShown - Date.now());
                setTimeout(function() { preloader.classList.add('loaded'); }, delay);
            }
            if (document.readyState === 'complete') hide();
            else window.addEventListener('load', hide);
            setTimeout(function() { preloader.classList.add('loaded'); }, 4000);
        })();
    </script>

    {{-- Remove any lingering dark class --}}
    <script>
        document.documentElement.classList.remove('dark');
        localStorage.removeItem('erp_theme');
    </script>

    <div class="main-wrapper" x-data="erpShell()" x-init="init()">
        {{-- Sidebar Overlay (mobile) --}}
        <div class="ks-sidebar-overlay" x-show="mobileOpen" x-on:click="mobileOpen = false" x-cloak style="display:none;"></div>

        {{-- ===== Sidebar ===== --}}
        <aside class="ks-sidebar" :class="{'ks-sidebar-collapsed': collapsed, 'ks-mobile-open': mobileOpen}">
            <div class="ks-sidebar-logo">
                <a href="{{ route('erp.dashboard') }}" class="d-flex align-items-center gap-2" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
                    <img alt="Consultiser" src="{{ asset('erp-assets/consultisor-logo.png') }}"/>
                    <span class="ks-logo-text ks-collapsed-hide">Consultiser <span class="consultiser-gradient-text">ERP</span></span>
                </a>
                <button class="ks-sidebar-close lg-show" x-on:click="mobileOpen = false" aria-label="Close sidebar" style="display:none;">
                    <i class="ti ti-x" style="font-size:20px;"></i>
                </button>
            </div>

            <div class="ks-sidebar-scroll">
                @php $nav = erp_navigation(); @endphp
                @foreach($nav as $section)
                    @if($section['heading'] ?? false)
                        <h6 class="ks-menu-title ks-collapsed-hide" style="margin:0;">{{ $section['heading'] }}</h6>
                    @endif
                    @foreach($section['items'] ?? [$section] as $item)
                        @continue(empty($item['label'] ?? null))
                        @php
                            $active = request()->routeIs($item['route'] ?? '') || (($item['routes'] ?? []) && request()->routeIs(...$item['routes']));
                            $route = $item['route'] ?? '#';
                        @endphp
                        <a href="{{ $route !== '#' ? route($route) : '#' }}" class="ks-menu-item {{ $active ? 'active' : '' }}" title="{{ $item['label'] }}">
                            <span class="ks-ico">{!! $item['icon'] ?? '' !!}</span>
                            <span class="ks-collapsed-hide">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                @endforeach
            </div>

            <div class="ks-sidebar-footer">
                <div class="ks-user-box ks-collapsed-hide">
                    <div class="ks-user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
                    <div class="flex-grow-1" style="min-width:0;flex:1;">
                        <p class="ks-user-name">{{ auth()->user()->name ?? '' }}</p>
                        <p class="ks-user-role">{{ str_replace('_',' ', auth()->user()->roles->pluck('name')->first() ?? '') }}</p>
                    </div>
                </div>
                <div style="margin-top:8px;">
                    <form method="POST" action="{{ route('erp.logout') }}">
                        @csrf
                        <button type="submit" class="ks-menu-item" style="width:100%;background:none;border:none;cursor:pointer;font-family:inherit;text-align:left;">
                            <span class="ks-ico"><i class="ti ti-logout" style="font-size:20px;"></i></span>
                            <span class="ks-collapsed-hide">Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- ===== Topbar ===== --}}
        <header class="ks-topbar" :class="{'ks-topbar-collapsed': collapsed}">
            <div class="ks-top-left">
                <button class="ks-hamburger ks-hamburger-desktop" x-on:click="collapsed = !collapsed" aria-label="Toggle sidebar">
                    <i class="ti ti-menu-2" style="font-size:20px;"></i>
                </button>
                <button class="ks-hamburger ks-hamburger-mobile" x-on:click="mobileOpen = !mobileOpen" aria-label="Open menu">
                    <i class="ti ti-menu-2" style="font-size:20px;"></i>
                </button>
                <div class="ks-topbar-search">
                    <i class="ti ti-search" style="font-size:15px;"></i>
                    <input type="text" placeholder="Search…" aria-label="Search">
                </div>
            </div>

            <div class="ks-topbar-right">
                {{-- Notifications --}}
                <div class="ks-dropdown" x-ref="notifDropdown">
                    <button class="ks-icon-btn" x-on:click="toggleDropdown('notif')">
                        <i class="ti ti-bell" style="font-size:19px;"></i>
                        @if(($unreadCount ?? 0) > 0)
                            <span class="ks-dot"></span>
                        @endif
                    </button>
                    <div class="ks-dropdown-menu ks-notif-menu" x-show="openDropdown === 'notif'" x-cloak x-on:click.outside="openDropdown = null">
                        <div class="ks-notif-head">Notifications <a href="{{ route('erp.notifications') }}" style="font-size:12px;font-weight:600;color:var(--role-accent);text-decoration:none;">View all</a></div>
                        @php $notifs = auth()->user()->notifications()->latest()->limit(5)->get(); @endphp
                        @forelse($notifs as $n)
                            <a href="{{ $n->link ?: route('erp.notifications') }}" class="ks-notif-item" style="display:flex;gap:12px;padding:12px 16px;text-decoration:none;border-bottom:1px solid var(--gray-100);">
                                <div class="ks-notif-ico role-bg-soft role-text" style="background:var(--role-accent-soft);color:var(--role-accent);">
                                    <i class="ti ti-bell" style="font-size:15px;"></i>
                                </div>
                                <div style="min-width:0;flex:1;">
                                    <p style="margin:0;font-size:13px;font-weight:600;color:var(--gray-900);">{{ $n->title }}</p>
                                    <small style="color:var(--gray-400);font-size:11px;">{{ $n->created_at->diffForHumans() }}</small>
                                </div>
                            </a>
                        @empty
                            <div style="padding:22px;text-align:center;color:var(--gray-400);font-size:13px;">No notifications</div>
                        @endforelse
                    </div>
                </div>

                {{-- Profile --}}
                <div class="ks-dropdown" x-ref="profileDropdown">
                    <div class="ks-topbar-profile" x-on:click="toggleDropdown('profile')" style="display:flex;align-items:center;gap:10px;">
                        <div class="ks-user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
                        <div class="ks-topbar-name">
                            <b>{{ auth()->user()->name ?? '' }}</b>
                            <span>{{ str_replace('_',' ', auth()->user()->roles->pluck('name')->first() ?? '') }}</span>
                        </div>
                        <i class="ti ti-chevron-down" style="font-size:14px;color:var(--gray-400);"></i>
                    </div>
                    <div class="ks-dropdown-menu" x-show="openDropdown === 'profile'" x-cloak x-on:click.outside="openDropdown = null">
                        <a href="{{ route('erp.profile') }}" class="ks-dropdown-item"><i class="ti ti-user" style="font-size:16px;"></i> My Profile</a>
                        <a href="{{ route('erp.sessions') }}" class="ks-dropdown-item"><i class="ti ti-shield-lock" style="font-size:16px;"></i> My Sessions</a>
                        <a href="{{ route('erp.password.change') }}" class="ks-dropdown-item"><i class="ti ti-key" style="font-size:16px;"></i> Change Password</a>
                        <div class="ks-dropdown-divider"></div>
                        <form method="POST" action="{{ route('erp.logout') }}">
                            @csrf
                            <button type="submit" class="ks-dropdown-item"><i class="ti ti-logout" style="font-size:16px;"></i> Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- ===== Page content ===== --}}
        <main class="ks-page-wrapper" :class="{'ks-page-collapsed': collapsed}">
            {{-- Flash messages --}}
            @if(session('success'))
                <div class="ks-alert ks-alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="ks-alert ks-alert-error">{{ session('error') }}</div>
            @endif
            @if(session('info'))
                <div class="ks-alert ks-alert-info">{{ session('info') }}</div>
            @endif
            @if($errors->any())
                <div class="ks-alert ks-alert-error">
                    <ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        function erpShell() {
            return {
                collapsed: false,
                mobileOpen: false,
                openDropdown: null,
                init() {
                    // Close mobile sidebar on resize to desktop
                    const desktopLg = window.matchMedia('(min-width: 992px)');
                    desktopLg.addEventListener('change', e => {
                        if (e.matches && this.mobileOpen) {
                            this.mobileOpen = false;
                        }
                        if (!e.matches && this.collapsed) {
                            this.collapsed = false;
                        }
                    });

                    // Escape closes dropdowns and sidebar
                    document.addEventListener('keydown', e => {
                        if (e.key === 'Escape') {
                            this.openDropdown = null;
                            if (this.mobileOpen) {
                                this.mobileOpen = false;
                            }
                        }
                    });

                    // Click outside sidebar closes it on mobile
                    document.addEventListener('click', e => {
                        if (!this.mobileOpen) return;
                        const sidebar = document.querySelector('.ks-sidebar');
                        const topbar = document.querySelector('.ks-topbar');
                        if (sidebar && !sidebar.contains(e.target) && topbar && !topbar.contains(e.target)) {
                            this.mobileOpen = false;
                        }
                    });
                },
                toggleDropdown(name) {
                    this.openDropdown = this.openDropdown === name ? null : name;
                }
            }
        }
    </script>

    @stack('scripts')

    {{-- Permission Denied Modal --}}
    @if(session('permission_denied'))
    <div x-data="{ show: true }" x-show="show" x-cloak style="position:fixed;inset:0;z-index:9999;" @keydown.escape.window="show = false">
        <div style="position:absolute;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);" @click="show = false"></div>
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:20px;">
            <div @click.away="show = false" style="background:#fff;border-radius:16px;box-shadow:0 25px 60px rgba(0,0,0,0.25);width:100%;max-width:380px;padding:40px 32px 32px;text-align:center;position:relative;">
                <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#FEE2E2,#FECACA);display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">
                    <svg style="width:36px;height:36px;color:#DC2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0h.01m-.01 0H12zm0-6V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <h3 style="font-size:20px;font-weight:700;color:#111;margin-bottom:10px;">Access Denied</h3>
                <p style="font-size:14px;color:#6f7180;margin-bottom:0;line-height:1.6;">You do not have permission to access this.<br>Please contact your administrator.</p>
                <div style="margin-top:28px;">
                    <button @click="show = false" style="width:100%;padding:12px 24px;border-radius:10px;background:linear-gradient(120deg,#C91F32,#FF6A1A);color:#fff;font-size:15px;font-weight:600;border:none;cursor:pointer;box-shadow:0 4px 14px rgba(201,31,50,0.3);">Got it</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</body>
</html>
