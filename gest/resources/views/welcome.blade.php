<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ARENA') }} | Gestion de salle de sport</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700;800&family=Teko:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        :root {
            --arena-bg: #090a0d;
            --arena-panel: #111319;
            --arena-panel-2: #171b22;
            --arena-soft: #1d222a;
            --arena-line: rgba(255,255,255,0.08);
            --arena-text: #f5f7fb;
            --arena-muted: #a6acb8;
            --arena-gold: #ff7a1b;
            --arena-orange: #ff5a1d;
            --arena-success: #10b981;
            --arena-surface: #f3f5f8;
            --arena-card: #ffffff;
            --arena-light-text: #1a1f27;
            --shadow-soft: 0 30px 60px rgba(0,0,0,0.24);
        }

        * { box-sizing: border-box; }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: 'Barlow', sans-serif;
            background: var(--arena-bg);
            color: var(--arena-text);
        }

        img {
            max-width: 100%;
            display: block;
        }

        a { text-decoration: none; }

        .brand-font {
            font-family: 'Teko', sans-serif;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .site-shell {
            background: radial-gradient(circle at top left, rgba(255, 122, 27, 0.18), transparent 28%), var(--arena-bg);
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 30;
            background: rgba(9, 10, 13, 0.78);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--arena-line);
        }

        .topbar .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 82px;
        }

        .brand {
            font-size: 2.8rem;
            line-height: 1;
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .brand-mark {
            width: 22px;
            height: 22px;
            background: linear-gradient(135deg, var(--arena-gold), var(--arena-orange));
            clip-path: polygon(0 0, 100% 0, 100% 100%, 0 80%);
            border-radius: 6px;
            display: inline-block;
            transform: skewY(-12deg);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            color: rgba(255,255,255,0.82);
            font-size: 0.96rem;
            transition: color .2s ease;
        }

        .nav-links a:hover {
            color: #fff;
        }

        .btn-primary-arena {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.9rem 1.5rem;
            border-radius: 12px;
            font-weight: 700;
            letter-spacing: 0.03em;
            background: linear-gradient(135deg, var(--arena-orange), var(--arena-gold));
            border: none;
            color: #fff;
            box-shadow: 0 18px 32px rgba(255, 90, 29, 0.35);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .btn-primary-arena:hover {
            transform: translateY(-2px);
            box-shadow: 0 22px 38px rgba(255, 90, 29, 0.4);
            color: #fff;
        }

        .btn-secondary-arena {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.9rem 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            background: rgba(255,255,255,0.02);
            transition: background .2s ease, transform .2s ease;
        }

        .btn-secondary-arena:hover {
            background: rgba(255,255,255,0.06);
            transform: translateY(-2px);
            color: #fff;
        }

        .hero {
            position: relative;
            overflow: hidden;
            padding: 4.2rem 0 2.5rem;
            background:
                linear-gradient(90deg, rgba(9,10,13,0.94), rgba(9,10,13,0.78) 44%, rgba(9,10,13,0.44)),
                url('https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=1400&q=80') center/cover no-repeat;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 2.5rem;
            align-items: center;
        }

        .eyebrow {
            color: var(--arena-gold);
            font-size: 0.8rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .hero h1 {
            margin: 0 0 1.2rem;
            font-family: 'Teko', sans-serif;
            font-size: clamp(4rem, 8vw, 8rem);
            line-height: 0.88;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #fff;
        }

        .hero p.lead {
            max-width: 650px;
            margin: 0 0 2rem;
            color: rgba(255,255,255,0.8);
            font-size: 1.15rem;
            line-height: 1.7;
        }

        .cta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2.2rem;
        }

        .mini-meta {
            display: flex;
            gap: 1.2rem;
            flex-wrap: wrap;
            color: rgba(255,255,255,0.8);
            font-size: 0.95rem;
        }

        .mini-meta span {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .mini-meta span::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--arena-gold);
            display: inline-block;
        }

        .hero-side {
            display: flex;
            justify-content: center;
        }

        .dashboard-panel {
            width: min(100%, 470px);
            background: rgba(17, 19, 25, 0.9);
            border: 1px solid var(--arena-line);
            border-radius: 22px;
            box-shadow: var(--shadow-soft);
            padding: 1.2rem;
        }

        .panel-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .panel-tag {
            padding: 0.45rem 0.7rem;
            border-radius: 999px;
            background: rgba(255, 122, 27, 0.12);
            color: var(--arena-gold);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .tiny-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--arena-success);
            box-shadow: 0 0 12px rgba(16,185,129,0.8);
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.9rem;
            margin-bottom: 1rem;
        }

        .mini-stat {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--arena-line);
            border-radius: 16px;
            padding: 0.9rem;
        }

        .mini-stat small {
            display: block;
            color: var(--arena-muted);
            margin-bottom: 0.4rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .mini-stat strong {
            font-size: 1.6rem;
            font-weight: 800;
        }

        .mini-stat .positive {
            color: var(--arena-success);
            font-size: 0.78rem;
            font-weight: 700;
        }

        .chart-box {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--arena-line);
            border-radius: 16px;
            padding: 0.9rem;
            min-height: 170px;
        }

        .bars {
            margin-top: 1rem;
            display: flex;
            align-items: end;
            gap: 0.45rem;
            height: 90px;
        }

        .bars span {
            flex: 1;
            background: linear-gradient(180deg, rgba(255,122,27,0.98), rgba(255,122,27,0.5));
            border-radius: 10px 10px 0 0;
        }

        .metrics-strip {
            margin-top: 2rem;
            background: rgba(255,255,255,0.03);
            border-top: 1px solid var(--arena-line);
            border-bottom: 1px solid var(--arena-line);
        }

        .metrics-inner {
            display: grid;
            grid-template-columns: repeat(4, minmax(0,1fr));
            gap: 1rem;
            padding: 1.2rem 0;
        }

        .metric-item {
            text-align: center;
        }

        .metric-item strong {
            display: block;
            font-size: clamp(1.4rem, 3vw, 2.5rem);
            font-family: 'Teko', sans-serif;
            line-height: 1;
        }

        .metric-item span {
            display: block;
            color: var(--arena-muted);
            font-size: 0.82rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-top: 0.35rem;
        }

        .section {
            padding: 6rem 0;
        }

        .section-light {
            background: var(--arena-surface);
            color: var(--arena-light-text);
        }

        .section-header {
            max-width: 700px;
            margin-bottom: 2.5rem;
        }

        .section-header .eyebrow {
            color: var(--arena-gold);
        }

        .section-header h2 {
            margin: 0;
            font-family: 'Teko', sans-serif;
            font-size: clamp(2.8rem, 5vw, 4.2rem);
            line-height: 0.95;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: var(--arena-light-text);
        }

        .section-header p {
            margin-top: 1rem;
            color: rgba(26,31,39,0.72);
            font-size: 1.05rem;
            line-height: 1.7;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0,1fr));
            gap: 1.25rem;
        }

        .feature-card {
            background: var(--arena-card);
            border: 1px solid rgba(22, 26, 35, 0.08);
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: 0 16px 34px rgba(15,17,23,0.06);
            min-height: 220px;
        }

        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(255,122,27,0.15), rgba(255,122,27,0.08));
            color: var(--arena-orange);
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            margin: 0 0 0.8rem;
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--arena-light-text);
        }

        .feature-card p {
            margin: 0;
            color: rgba(26,31,39,0.7);
            line-height: 1.7;
        }

        .showcase {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 2rem;
            align-items: center;
        }

        .showcase-card {
            background: linear-gradient(180deg, rgba(17,19,25,0.94), rgba(17,19,25,1));
            border: 1px solid var(--arena-line);
            border-radius: 26px;
            padding: 1.4rem;
            box-shadow: var(--shadow-soft);
        }

        .screen-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.15rem;
            color: rgba(255,255,255,0.8);
        }

        .screen-header strong {
            color: #fff;
        }

        .dots {
            display: flex;
            gap: 0.4rem;
        }

        .dots span {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .dot-red { background: #ff5c57; }
        .dot-yellow { background: #f6c949; }
        .dot-green { background: #39d353; }

        .stats-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .stats-list .entry {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--arena-line);
            border-radius: 14px;
            padding: 0.9rem 0.8rem;
        }

        .stats-list .entry .label {
            display: block;
            color: var(--arena-muted);
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 0.35rem;
        }

        .stats-list .entry strong {
            font-size: 1.4rem;
        }

        .side-stack {
            display: grid;
            gap: 1rem;
        }

        .side-card {
            background: var(--arena-card);
            border-radius: 20px;
            padding: 1.3rem;
            box-shadow: 0 16px 34px rgba(15,17,23,0.06);
            color: var(--arena-light-text);
        }

        .side-card h3 {
            margin: 0 0 0.9rem;
            font-weight: 800;
            font-size: 1.3rem;
        }

        .timeline {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 0.8rem;
        }

        .timeline li {
            display: flex;
            gap: 0.7rem;
            align-items: center;
            padding-bottom: 0.45rem;
            border-bottom: 1px solid rgba(26,31,39,0.08);
        }

        .timeline li:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .check {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(16,185,129,0.12);
            color: var(--arena-success);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .cta-band {
            padding-top: 0;
            padding-bottom: 5rem;
        }

        .cta-box {
            background: linear-gradient(135deg, rgba(255,122,27,0.12), rgba(255,255,255,0.04));
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 28px;
            padding: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
            box-shadow: var(--shadow-soft);
        }

        .cta-box h3 {
            font-family: 'Teko', sans-serif;
            font-size: clamp(2.5rem, 5vw, 4rem);
            line-height: 0.95;
            text-transform: uppercase;
            margin: 0 0 0.7rem;
        }

        .cta-box p {
            margin: 0;
            color: rgba(255,255,255,0.76);
            max-width: 680px;
        }

        .footer {
            border-top: 1px solid var(--arena-line);
            padding: 1.5rem 0 2.5rem;
            color: var(--arena-muted);
            background: #090a0d;
        }

        [data-anim] {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity .7s ease, transform .7s ease;
        }

        [data-anim].in {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 991.98px) {
            .hero-grid,
            .showcase,
            .feature-grid,
            .metrics-inner {
                grid-template-columns: 1fr 1fr;
            }

            .feature-grid {
                grid-template-columns: repeat(2, minmax(0,1fr));
            }
        }

        @media (max-width: 767.98px) {
            .topbar .container {
                flex-wrap: wrap;
                justify-content: center;
                padding-top: 1rem;
                padding-bottom: 1rem;
            }

            .nav-links {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
                padding-top: 0.6rem;
            }

            .hero {
                padding-top: 3rem;
            }

            .hero-grid,
            .feature-grid,
            .showcase,
            .metrics-inner,
            .stats-list {
                grid-template-columns: 1fr;
            }

            .cta-box {
                flex-direction: column;
                align-items: flex-start;
            }

            .hero h1 {
                font-size: 4.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="site-shell">
        <header class="topbar">
            <div class="container">
                <a href="{{ url('/') }}" class="brand brand-font" aria-label="ARENA">
                    <span class="brand-mark"></span>
                    <span>ARENA</span>
                </a>

                <nav aria-label="Navigation principale">
                    <ul class="nav-links">
                        <li><a href="#solutions">Solutions</a></li>
                        <li><a href="#fonctionnalites">Fonctionnalités</a></li>
                        <li><a href="#tarifs">Tarifs</a></li>
                        <li><a href="{{ route('gyms.register') }}" class="btn-primary-arena">S'inscrire</a></li>
                    </ul>
                </nav>
            </div>
        </header>

        <main>
            <section class="hero">
                <div class="container hero-grid">
                    <div data-anim>
                        <div class="eyebrow">Une plateforme complète pour toutes les équipes de votre salle</div>
                        <h1>ARENA</h1>
                        <p class="lead">La gestion de votre salle de sport, en toute simplicité. Suivez les membres, les abonnements, les paiements, les cours et les revenus depuis un seul espace.</p>
                        <div class="cta-row">
                            <a href="{{ route('gyms.register') }}" class="btn-primary-arena">Créer ma salle</a>
                            <a href="{{ url('/admin/login') }}" class="btn-secondary-arena">Connexion</a>
                        </div>
                        <div class="mini-meta">
                            <span>Membres</span>
                            <span>Abonnements</span>
                            <span>Paiements</span>
                        </div>
                    </div>

                    <div class="hero-side" data-anim>
                        <div class="dashboard-panel">
                            <div class="panel-top">
                                <div class="panel-tag">Dashboard</div>
                                <span class="tiny-dot" aria-label="En ligne"></span>
                            </div>

                            <div class="stat-grid">
                                <div class="mini-stat">
                                    <small>Recettes</small>
                                    <strong>4.2K</strong>
                                    <div class="positive">+18.2%</div>
                                </div>
                                <div class="mini-stat">
                                    <small>Membres</small>
                                    <strong>486</strong>
                                    <div class="positive">+8.1%</div>
                                </div>
                                <div class="mini-stat">
                                    <small>Abonnés</small>
                                    <strong>128</strong>
                                    <div class="positive">+11.7%</div>
                                </div>
                                <div class="mini-stat">
                                    <small>Check-ins</small>
                                    <strong>312</strong>
                                    <div class="positive">+6.4%</div>
                                </div>
                            </div>

                            <div class="chart-box">
                                <div class="screen-header">
                                    <strong>Revenus</strong>
                                    <span>30 jours</span>
                                </div>
                                <div class="bars" aria-label="Graphique de revenus">
                                    <span style="height:42%;"></span>
                                    <span style="height:55%;"></span>
                                    <span style="height:48%;"></span>
                                    <span style="height:68%;"></span>
                                    <span style="height:62%;"></span>
                                    <span style="height:84%;"></span>
                                    <span style="height:78%;"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="metrics-strip">
                <div class="container metrics-inner">
                    <div class="metric-item" data-anim>
                        <strong>120+</strong>
                        <span>Salles</span>
                    </div>
                    <div class="metric-item" data-anim>
                        <strong>98.7%</strong>
                        <span>Satisfaction</span>
                    </div>
                    <div class="metric-item" data-anim>
                        <strong>4.9/5</strong>
                        <span>Évaluation</span>
                    </div>
                    <div class="metric-item" data-anim>
                        <strong>24/7</strong>
                        <span>Support</span>
                    </div>
                </div>
            </section>

            <section class="section section-light" id="solutions">
                <div class="container">
                    <div class="section-header" data-anim>
                        <div class="eyebrow">Une plateforme complète</div>
                        <h2>Tout ce qu’il faut pour piloter votre salle</h2>
                        <p>Des outils pensée pour les managers, coachs, réceptionnistes et membres, dans un environnement simple et rapide.</p>
                    </div>

                    <div class="feature-grid">
                        <article class="feature-card" data-anim>
                            <div class="feature-icon">👤</div>
                            <h3>Super Admin</h3>
                            <p>Gérez plusieurs salles, suivez les performances globales et pilotez votre activité SaaS depuis un seul tableau de bord.</p>
                        </article>

                        <article class="feature-card" data-anim>
                            <div class="feature-icon">🏋️</div>
                            <h3>Admin Salle</h3>
                            <p>Contrôlez les membres, les formules, les paiements, les inscriptions et les alertes d’abonnement en quelques clics.</p>
                        </article>

                        <article class="feature-card" data-anim>
                            <div class="feature-icon">📲</div>
                            <h3>Réception</h3>
                            <p>Faites un accueil rapide, vérifiez les check-ins et centralisez les demandes clients dans une interface fluide.</p>
                        </article>

                        <article class="feature-card" data-anim>
                            <div class="feature-icon">💪</div>
                            <h3>Membre</h3>
                            <p>Suivez vos accès, vos abonnements et votre progression avec une expérience simple et moderne.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="section" id="fonctionnalites">
                <div class="container showcase">
                    <div class="showcase-card" data-anim>
                        <div class="screen-header">
                            <strong>Gestion des membres</strong>
                            <div class="dots" aria-hidden="true">
                                <span class="dot-red"></span>
                                <span class="dot-yellow"></span>
                                <span class="dot-green"></span>
                            </div>
                        </div>

                        <div class="stats-list">
                            <div class="entry">
                                <span class="label">Membres</span>
                                <strong>486</strong>
                            </div>
                            <div class="entry">
                                <span class="label">Abonnés</span>
                                <strong>128</strong>
                            </div>
                            <div class="entry">
                                <span class="label">Visites</span>
                                <strong>312</strong>
                            </div>
                            <div class="entry">
                                <span class="label">Retards</span>
                                <strong>12</strong>
                            </div>
                        </div>
                    </div>

                    <div class="side-stack" data-anim>
                        <div class="side-card">
                            <h3>Pourquoi les salles choisissent ARENA</h3>
                            <ul class="timeline">
                                <li><span class="check">✓</span> <span>Suivi des abonnements et des expirations</span></li>
                                <li><span class="check">✓</span> <span>Visualisation rapide des revenus et des performances</span></li>
                                <li><span class="check">✓</span> <span>Réduction du temps administratif pour l’équipe</span></li>
                            </ul>
                        </div>

                        <div class="side-card">
                            <h3>Modules clés</h3>
                            <ul class="timeline">
                                <li><span class="check">✓</span> <span>Abonnements & formules</span></li>
                                <li><span class="check">✓</span> <span>Paiements & relances</span></li>
                                <li><span class="check">✓</span> <span>Check-in & présence</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section section-light" id="tarifs">
                <div class="container">
                    <div class="section-header" data-anim>
                        <div class="eyebrow">Simple et évolutif</div>
                        <h2>Une solution pensée pour grandir</h2>
                        <p>Commencez avec une salle, puis déployez plusieurs clubs avec des accès distincts selon votre organisation.</p>
                    </div>

                    <div class="feature-grid">
                        <article class="feature-card" data-anim>
                            <div class="feature-icon">📊</div>
                            <h3>Suivi métier</h3>
                            <p>Analysez la fréquentation, les revenus et les performances de votre activité en temps réel.</p>
                        </article>

                        <article class="feature-card" data-anim>
                            <div class="feature-icon">💳</div>
                            <h3>Paiements</h3>
                            <p>Centralisez les encaissements, les statuts et les relances pour les membres en retard.</p>
                        </article>

                        <article class="feature-card" data-anim>
                            <div class="feature-icon">🧭</div>
                            <h3>Multi-salles</h3>
                            <p>Super admin et admins locataires se partagent les responsabilités sans confusion.</p>
                        </article>

                        <article class="feature-card" data-anim>
                            <div class="feature-icon">⚡</div>
                            <h3>Rapide</h3>
                            <p>Une interface claire, rapide et pensée pour un usage quotidien sans friction.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="section cta-band">
                <div class="container">
                    <div class="cta-box" data-anim>
                        <div>
                            <div class="eyebrow">Prêt à lancer votre salle ?</div>
                            <h3>Votre salle, <br>en mieux.</h3>
                        </div>
                        <div>
                            <p>Centralisez la gestion de votre club, automatisez les tâches répétitives et donnez à votre équipe la visibilité nécessaire pour se concentrer sur la performance.</p>
                        </div>
                        <a href="{{ route('gyms.register') }}" class="btn-primary-arena">Démarrer maintenant</a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="footer">
            <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="brand-font" style="font-size:2rem; color:#fff;">ARENA</div>
                <div>© {{ date('Y') }} ARENA. Gestion de salle de sport.</div>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const animatedBlocks = document.querySelectorAll('[data-anim]');

            if (!('IntersectionObserver' in window)) {
                animatedBlocks.forEach((block) => block.classList.add('in'));
                return;
            }

            const observer = new IntersectionObserver((entries, obs) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in');
                        obs.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12 });

            animatedBlocks.forEach((block) => observer.observe(block));
        });
    </script>
</body>
</html>
