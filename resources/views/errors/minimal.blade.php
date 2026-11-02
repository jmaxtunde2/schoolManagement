@php
    $status = $status ?? 500;
    $title = $title ?? 'Une erreur est survenue';
    $message = $message ?? "La page n'a pas pu être affichée correctement.";
    $hint = $hint ?? null;
    $branding = $branding ?? [];
    $primary = $branding['primary_color'] ?? '#1E40AF';
    $secondary = $branding['secondary_color'] ?? '#0F766E';
    $brandName = ! empty($branding['id'])
        ? ($branding['short_name'] ?? $branding['name'] ?? 'CoriSchool')
        : 'CoriSchool';
    $logoUrl = $branding['logo_url'] ?? null;
    $supportEmail = $branding['email'] ?? null;
    $whatsapp = $supportWhatsapp ?? null;
    $whatsappDigits = $whatsapp ? preg_replace('/\D+/', '', $whatsapp) : null;
    $whatsappMessage = $supportMessage ?? 'Bonjour, je souhaite signaler une erreur sur CoriSchool.';
    $isNotFound = $status === 404;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $status }} — {{ $title }} | CoriSchool</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <style>
        :root {
            --brand: {{ $primary }};
            --brand-2: {{ $secondary }};
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: #0f172a;
            background: #f8fafc;
            font-family: Instrument Sans, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .background {
            position: fixed;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .glow {
            position: absolute;
            border-radius: 9999px;
            filter: blur(90px);
        }

        .glow-primary {
            top: -14rem;
            left: -14rem;
            width: 34rem;
            height: 34rem;
            background: var(--brand);
            opacity: .10;
        }

        .glow-secondary {
            right: -16rem;
            bottom: -16rem;
            width: 38rem;
            height: 38rem;
            background: var(--brand-2);
            opacity: .08;
        }

        .grid {
            position: absolute;
            inset: 0;
            opacity: .035;
            background-image:
                linear-gradient(to right, #64748b 1px, transparent 1px),
                linear-gradient(to bottom, #64748b 1px, transparent 1px);
            background-size: 48px 48px;
        }

        .shell {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 80rem;
            margin: 0 auto;
            padding: 24px 20px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: inherit;
            text-decoration: none;
        }

        .logo {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: #fff;
            font-size: 14px;
            font-weight: 900;
            letter-spacing: -.04em;
            box-shadow: 0 10px 25px -10px color-mix(in srgb, var(--brand) 60%, transparent);
            background: linear-gradient(145deg, var(--brand), var(--brand-2));
        }

        .logo img { width: 100%; height: 100%; object-fit: cover; }

        .brand-name {
            display: block;
            margin: 0;
            font-size: 17px;
            font-weight: 900;
            letter-spacing: -.02em;
            color: #020617;
        }

        .brand-tagline {
            display: block;
            margin: 2px 0 0;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .header-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: rgba(255, 255, 255, .8);
            color: #334155;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        }

        .main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px 0 40px;
        }

        .card {
            width: 100%;
            max-width: 65rem;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .8);
            border-radius: 32px;
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 35px 70px -30px rgba(15, 23, 42, .28);
            display: grid;
            grid-template-columns: .92fr 1.08fr;
            backdrop-filter: blur(18px);
        }

        .panel {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 34rem;
            padding: 40px;
            color: #fff;
            background:
                linear-gradient(150deg,
                    color-mix(in srgb, var(--brand) 96%, #0f172a),
                    color-mix(in srgb, var(--brand-2) 72%, #0f172a));
        }

        .circle {
            position: absolute;
            border: 1px solid rgba(255, 255, 255, .10);
            border-radius: 9999px;
        }

        .circle-one { width: 20rem; height: 20rem; top: -7rem; right: -7rem; }
        .circle-two { width: 12rem; height: 12rem; top: -2rem; right: -2rem; }
        .circle-three { width: 24rem; height: 24rem; bottom: -10rem; left: -10rem; }

        .badge {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 6px 12px;
            border: 1px solid rgba(255, 255, 255, .15);
            border-radius: 9999px;
            background: rgba(255, 255, 255, .10);
            color: rgba(255, 255, 255, .82);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .18em;
            text-transform: uppercase;
        }

        .status {
            position: relative;
            z-index: 1;
            margin: 0;
            font-size: clamp(5rem, 11vw, 8rem);
            line-height: .86;
            font-weight: 900;
            letter-spacing: -.07em;
            color: rgba(255, 255, 255, .96);
        }

        .panel-footer {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, .55);
            font-size: 11px;
            font-weight: 700;
        }

        .dot {
            width: 6px;
            height: 6px;
            border-radius: 9999px;
            background: #6ee7b7;
        }

        .content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 48px;
        }

        .eyebrow {
            margin: 0 0 8px;
            color: var(--brand);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            color: #020617;
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            line-height: 1.1;
            letter-spacing: -.03em;
        }

        .message {
            margin: 16px 0 0;
            color: #475569;
            font-size: 16px;
            line-height: 1.7;
        }

        .hint {
            margin: 14px 0 0;
            padding: 12px 16px;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            background: #f8fafc;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 30px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            transition: transform .15s ease, opacity .15s ease, background .15s ease;
        }

        .btn:hover { transform: translateY(-1px); }

        .btn-primary {
            background: var(--brand);
            color: #fff;
            box-shadow: 0 15px 30px -12px color-mix(in srgb, var(--brand) 60%, transparent);
        }

        .btn-secondary {
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #334155;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        }

        .btn-secondary:hover { background: #f8fafc; }

        .links {
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid #f1f5f9;
        }

        .links-title {
            margin: 0 0 10px;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .link-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .quick-link {
            padding: 11px 12px;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
        }

        .quick-link:hover {
            border-color: #e2e8f0;
            background: #fff;
            color: #0f172a;
        }

        .support {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid #f1f5f9;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
        }

        .support-links { display: flex; flex-wrap: wrap; gap: 8px; }

        .support-link {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            text-decoration: none;
        }

        .support-link:hover { background: #f8fafc; }

        .footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 8px 0 16px;
            color: #94a3b8;
            font-size: 11px;
        }

        .footer a { color: #64748b; font-weight: 700; text-decoration: none; }
        .footer a:hover { color: #334155; }

        @media (max-width: 900px) {
            .card { grid-template-columns: 1fr; }
            .panel { min-height: 17rem; padding: 28px; }
            .status { font-size: 5rem; }
            .content { padding: 30px 26px; }
        }

        @media (max-width: 560px) {
            .shell { padding: 16px; }
            .header-home { display: none; }
            .panel { min-height: 13rem; padding: 24px; }
            .status { font-size: 4rem; }
            .content { padding: 26px 20px; }
            .link-grid { grid-template-columns: 1fr; }
            .support { align-items: flex-start; flex-direction: column; }
            .footer { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="background" aria-hidden="true">
        <div class="glow glow-primary"></div>
        <div class="glow glow-secondary"></div>
        <div class="grid"></div>
    </div>

    <div class="shell">
        <header class="header">
            <a href="/" class="brand">
                <span class="logo">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="">
                    @else
                        CO
                    @endif
                </span>
                <span>
                    <span class="brand-name">{{ $brandName }}</span>
                    <span class="brand-tagline">Plateforme scolaire numérique</span>
                </span>
            </a>

            <a href="/" class="header-home">Accueil</a>
        </header>

        <main class="main">
            <div class="card">
                <section class="panel" aria-hidden="true">
                    <span class="circle circle-one"></span>
                    <span class="circle circle-two"></span>
                    <span class="circle circle-three"></span>

                    <span class="badge">Code {{ $status }}</span>

                    <p class="status">{{ $status }}</p>

                    <div class="panel-footer">
                        <span class="dot"></span>
                        CoriSchool · Plateforme scolaire
                    </div>
                </section>

                <section class="content">
                    <p class="eyebrow">Oups, quelque chose s’est mal passé</p>
                    <h1>{{ $title }}</h1>
                    <p class="message">{{ $message }}</p>

                    @if ($hint)
                        <p class="hint">{{ $hint }}</p>
                    @endif

                    <div class="actions">
                        <a href="/" class="btn btn-primary">Retour à l’accueil</a>

                        @if (in_array($status, [401, 419], true))
                            <a href="/login" class="btn btn-secondary">Se connecter</a>
                        @elseif (in_array($status, [408, 429, 500, 502, 503, 504], true))
                            <a href="{{ request()->fullUrl() }}" class="btn btn-secondary">Réessayer</a>
                        @endif
                    </div>

                    @if ($isNotFound)
                        <div class="links">
                            <p class="links-title">Pages utiles</p>
                            <div class="link-grid">
                                <a href="/" class="quick-link">Accueil</a>
                                <a href="/ecoles" class="quick-link">Nos écoles</a>
                                <a href="/banque-epreuves" class="quick-link">Banque d’épreuves</a>
                            </div>
                        </div>
                    @endif

                    @if ($whatsappDigits || $supportEmail)
                        <div class="support">
                            <span>Besoin d’aide ?</span>
                            <div class="support-links">
                                @if ($whatsappDigits)
                                    <a
                                        class="support-link"
                                        href="https://wa.me/{{ $whatsappDigits }}?text={{ rawurlencode($whatsappMessage) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >WhatsApp</a>
                                @endif

                                @if ($supportEmail)
                                    <a class="support-link" href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
                                @endif
                            </div>
                        </div>
                    @endif
                </section>
            </div>
        </main>

        <footer class="footer">
            <span>© {{ date('Y') }} CoriSchool — Plateforme scolaire numérique</span>
            <span>Une solution <a href="https://coriyase.com" target="_blank" rel="noopener noreferrer">Coriyase Technologies</a></span>
        </footer>
    </div>
</body>
</html>
