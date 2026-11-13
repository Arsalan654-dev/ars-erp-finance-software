<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('erp.app_name')) — {{ config('erp.app_name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('erp-assets/ars-erp-icon.png') }}">
    <link rel="stylesheet" href="{{ asset('erp-assets/kanakku-theme.css') }}?v=1">
    <script src="{{ asset('erp-assets/tailwind.js') }}?v=2"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            crimson: '#0B1F3A',
                            red: '#0B1F3A',
                            coral: '#10B981',
                            orange: '#059669',
                            amber: '#F59E0B',
                        },
                        ink: { DEFAULT: '#111111', light: '#151515', lighter: '#181818' },
                        paper: { DEFAULT: '#F8F8F7', alt: '#FAFAF9' },
                    },
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'], display: ['Poppins', 'Inter', 'sans-serif'] },
                    borderRadius: { card: '14px', btn: '9px', modal: '16px' },
                    boxShadow: {
                        soft: '0 1px 3px rgba(17,17,17,0.04), 0 4px 20px rgba(17,17,17,0.05)',
                    },
                }
            }
        }
    </script>
    <style>
        /* ===== Default role theme variables (admin brand) — auth pages ===== */
        :root {
            --role-grad-1: #0B1F3A;
            --role-grad-2: #10B981;
            --role-grad-3: #059669;
            --role-grad-4: #F59E0B;
            --role-accent: #10B981;
            --role-accent-strong: #0B1F3A;
            --role-accent-soft: rgba(16,185,129,0.14);
        }
        /* Role-aware accent utilities */
        .role-text { color: var(--role-accent); }
        .role-hover-text:hover { color: var(--role-accent); }
        .role-border { border-color: var(--role-accent); }
        .role-border-soft { border-color: var(--role-accent-soft); }
        .role-ring { box-shadow: 0 0 0 3px var(--role-accent-soft); }
        .role-bg { background-color: var(--role-accent); }
        .role-bg-soft { background-color: var(--role-accent-soft); }
        .role-accent { accent-color: var(--role-accent); }
        .role-glow:hover { box-shadow: 0 0 22px var(--role-accent-soft); }
        .role-focus:focus {
            outline: none;
            border-color: var(--role-accent);
            box-shadow: 0 0 0 3px var(--role-accent-soft);
        }
        .role-focus-border:focus { border-color: var(--role-accent); }
        .role-shadow { box-shadow: 0 10px 20px -4px var(--role-accent-soft); }
        .consultiser-gradient {
            background-image: linear-gradient(135deg, var(--role-grad-1) 0%, var(--role-grad-2) 40%, var(--role-grad-3) 70%, var(--role-grad-4) 100%);
        }
        .consultiser-gradient-text {
            background-image: linear-gradient(135deg, var(--role-grad-1) 0%, var(--role-grad-2) 40%, var(--role-grad-3) 70%, var(--role-grad-4) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .custom-scroll::-webkit-scrollbar { width: 5px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 4px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }

        /* ===== Scroll Reveal Animations ===== */
        .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1), transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .reveal.reveal-visible {
            opacity: 1;
            transform: translateY(0);
        }
        .reveal-left {
            opacity: 0;
            transform: translateX(-24px);
            transition: opacity 0.5s cubic-bezier(0.16, 1, 0.3, 1), transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .reveal-left.reveal-visible {
            opacity: 1;
            transform: translateX(0);
        }
        .reveal-right {
            opacity: 0;
            transform: translateX(24px);
            transition: opacity 0.5s cubic-bezier(0.16, 1, 0.3, 1), transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .reveal-right.reveal-visible {
            opacity: 1;
            transform: translateX(0);
        }
        .reveal-scale {
            opacity: 0;
            transform: scale(0.95);
            transition: opacity 0.5s cubic-bezier(0.16, 1, 0.3, 1), transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .reveal-scale.reveal-visible {
            opacity: 1;
            transform: scale(1);
        }

        /* ===== Hover micro-interactions ===== */
        .hover-lift {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        .hover-glow-brand:hover {
            box-shadow: 0 0 20px var(--role-accent-soft);
        }
        [x-cloak] { display: none !important; }

        /* ===== Pre-loader Skeleton ===== */
        #app-preloader {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 22px;
            background: #111111;
            transition: opacity 0.4s ease, visibility 0.4s ease;
        }
        #app-preloader.loaded {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        .preloader-logo {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            padding: 12px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            animation: preloader-pulse 1.6s ease-in-out infinite;
        }
        .preloader-brand {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.02em;
        }
        .preloader-bar {
            width: 180px;
            height: 4px;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            overflow: hidden;
        }
        .preloader-bar-fill {
            height: 100%;
            border-radius: 999px;
            background-image: linear-gradient(90deg, var(--role-grad-1) 0%, var(--role-grad-2) 40%, var(--role-grad-3) 70%, var(--role-grad-4) 100%);
            animation: preloader-slide 1.4s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        }
        @keyframes preloader-pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.85; }
        }
        @keyframes preloader-slide {
            0% { width: 20%; }
            50% { width: 90%; }
            100% { width: 20%; }
        }
    </style>
    @stack('styles')
</head>
<body class="h-full font-sans antialiased bg-paper">

    {{-- Pre-loader Skeleton --}}
    <div id="app-preloader">
        <div class="preloader-logo">
            <img alt="ARS ERP Finance Software" class="w-full h-full object-contain" src="{{ asset('erp-assets/ars-erp-icon.png') }}"/>
        </div>
        <div class="text-center">
            <div class="preloader-brand">ARS ERP <span class="consultiser-gradient-text">Finance Software</span></div>
            <div class="text-[11px] text-white/30 mt-1 tracking-widest uppercase">Loading…</div>
        </div>
        <div class="preloader-bar"><div class="preloader-bar-fill"></div></div>
    </div>
    <script>
        (function() {
            var preloader = document.getElementById('app-preloader');
            if (!preloader) return;
            var minShown = Date.now() + 400;
            function hide() {
                var delay = Math.max(0, minShown - Date.now());
                setTimeout(function() { preloader.classList.add('loaded'); }, delay);
            }
            if (document.readyState === 'complete') { hide(); }
            else { window.addEventListener('load', hide); }
            setTimeout(function() { preloader.classList.add('loaded'); }, 4000);
        })();
    </script>

    {{-- Remove any lingering dark class from old stored preference --}}
    <script>
        document.documentElement.classList.remove('dark');
        localStorage.removeItem('erp_theme');
    </script>

    @yield('content')

    {{-- Scroll Reveal Observer --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('reveal-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

            document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale').forEach(el => {
                observer.observe(el);
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
