<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Gérez les membres, abonnements, présences et paiements de votre salle de sport avec ARENA GYM.">
    <meta name="theme-color" content="#0B0F14">
    <meta property="og:type" content="website">
    <meta property="og:title" content="ARENA GYM — Gestion de salles de sport">
    <meta property="og:description" content="Les outils essentiels pour gérer votre salle de sport, réunis dans un espace dédié.">
    <meta property="og:image" content="{{ asset('images/arena-icon-512.png') }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta name="turnstile-site-key" content="{{ config('services.turnstile.site_key') }}">
    <meta name="turnstile-enabled" content="{{ app()->isProduction() || filled(config('services.turnstile.site_key')) || filled(config('services.turnstile.secret_key')) ? 'true' : 'false' }}">
    <meta name="turnstile-ready" content="{{ filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key')) ? 'true' : 'false' }}">
    <link rel="icon" href="/images/arena-icon-512.png" type="image/png">
    <link rel="canonical" href="{{ url('/') }}">
    <link rel="preload" href="/images/arena-icon-512.png" as="image" type="image/png" fetchpriority="high">
    <title>ARENA GYM — Gestion de salles de sport</title>
    <style>
        html, body { margin: 0; min-height: 100%; background: #0b0f14; color: #f4f6f8; font-family: 'Inter', sans-serif; }
        #elitefit-root { min-height: 100vh; }
        #elitefit-fallback { padding: 3rem 1.5rem; text-align: center; max-width: 32rem; margin: 0 auto; }
        #elitefit-fallback h1 { color: #ff7a00; font-size: 1.25rem; letter-spacing: 0.2em; font-weight: 700; }
        #elitefit-fallback p { color: #a2acb7; font-size: 0.9rem; line-height: 1.6; margin-top: 1rem; }
        #elitefit-fallback code { display: block; margin-top: 1.5rem; padding: 1rem; background: #151b22; border: 1px solid #2a3540; border-radius: 8px; color: #ff7a00; font-size: 0.8rem; }
    </style>
    @php
        $hasViteBuild = file_exists(public_path('build/manifest.json'));
        $hasViteDev = file_exists(public_path('hot'));
    @endphp
    @if ($hasViteBuild || $hasViteDev)
        @viteReactRefresh
        @vite(['resources/css/elitefit.css', 'resources/js/elitefit/main.jsx'])
    @endif
</head>
<body>
    <div id="elitefit-root">
        <div id="elitefit-fallback">
            <img src="/images/arena-icon-512.png" width="56" height="56" alt="" fetchpriority="high">
            <h1>ARENA GYM</h1>
            @if (! $hasViteBuild && ! $hasViteDev)
                <p>Les fichiers de l’interface ne sont pas compilés. Lancez une commande ci-dessous, puis rechargez la page.</p>
                <code>npm run build</code>
                <code style="margin-top: 0.5rem;">ou : npm run dev</code>
            @else
                <p>Chargement de l’application…</p>
            @endif
        </div>
    </div>
</body>
</html>
