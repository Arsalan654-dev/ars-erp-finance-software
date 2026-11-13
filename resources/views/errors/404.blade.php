<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', '404 — Page Not Found')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="icon" type="image/png" href="{{ asset('erp-assets/ars-erp-icon.png') }}">
<link rel="stylesheet" href="{{ asset('erp-assets/kanakku-theme.css') }}?v=1">
<script src="{{ asset('erp-assets/tailwind.js') }}"></script>
<script>
tailwind.config = {
 theme: { extend: {
  colors: { brand: { crimson: '#0B1F3A', red: '#0B1F3A', coral: '#10B981', orange: '#059669', amber: '#F59E0B' }, ink: { DEFAULT: '#111111' } },
  fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
 }}
}
</script>
<style>
:root {
 --role-grad-1: #0B1F3A;
 --role-grad-2: #10B981;
 --role-grad-3: #059669;
 --role-grad-4: #F59E0B;
 --role-accent: #10B981;
}
.consultiser-gradient {
 background-image: linear-gradient(135deg, var(--role-grad-1) 0%, var(--role-grad-2) 40%, var(--role-grad-3) 70%, var(--role-grad-4) 100%);
}
</style>
</head>
<body class="h-full bg-ink flex items-center justify-center p-4 relative overflow-hidden">
 <div class="absolute -top-40 -left-40 w-[500px] h-[500px] rounded-full role-bg-soft blur-3xl" style="pointer-events:none;"></div>
 <div class="absolute -bottom-40 -right-40 w-[500px] h-[500px] rounded-full role-bg-soft blur-3xl" style="pointer-events:none;"></div>
 <div class="relative z-10 text-center px-6" style="max-width:420px;">
  <div class="consultiser-gradient" style="width:72px;height:72px;border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;padding:12px;">
   <img alt="ARS ERP Finance Software" class="w-full h-full object-contain" src="{{ asset('erp-assets/ars-erp-icon.png') }}"/>
  </div>
  <h1 style="font-size:clamp(48px,12vw,72px);font-weight:800;color:#fff;margin:0 0 8px;line-height:1;">@yield('code', '404')</h1>
  <p style="font-size:clamp(14px,3vw,18px);color:var(--gray-400);margin:0 0 32px;">@yield('message', 'The page you are looking for does not exist.')</p>
  <a href="{{ url('/erp/dashboard') }}" class="ks-btn ks-btn-primary" style="padding:14px 28px;font-size:14px;">
   Back to Dashboard <i class="ti ti-arrow-right" style="font-size:16px;margin-left:4px;"></i>
  </a>
 </div>
</body>
</html>
