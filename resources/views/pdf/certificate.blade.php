<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 30px 34px; }
        * { box-sizing: border-box; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        .school-head { border-bottom: 2px solid {{ $identity['primary_color'] ?? '#1E40AF' }}; padding-bottom: 12px; width: 100%; }
        .school-head td { vertical-align: middle; }
        .logo { max-height: 66px; max-width: 96px; }
        .school-name { color: #102a54; font-size: 18px; font-weight: bold; }
        .muted { color: #596579; font-size: 9px; line-height: 1.5; }
        .title { margin: 30px 0 6px; text-align: center; color: #102a54; font-size: 22px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .reference { text-align: center; color: #53627a; font-size: 10px; margin-bottom: 26px; }
        .body-text { font-size: 13px; line-height: 2; text-align: justify; margin: 0 6px; }
        .identity { width: 100%; margin: 24px 0; border-collapse: collapse; }
        .identity td { border: 1px solid #cbd3df; padding: 9px 10px; width: 50%; }
        .label { color: #64748b; font-size: 8px; text-transform: uppercase; letter-spacing: .5px; }
        .value { margin-top: 4px; font-size: 11px; font-weight: bold; }
        .signatures { width: 100%; margin-top: 60px; border-collapse: collapse; }
        .signatures td { width: 50%; height: 60px; vertical-align: top; text-align: center; }
        .sign-line { margin: 46px 24px 0; border-top: 1px solid #7b8799; padding-top: 6px; color: #596579; font-size: 9px; }
        .footer { margin-top: 18px; border-top: 1px solid #d5dce5; padding-top: 6px; color: #7b8799; font-size: 7px; text-align: right; }
    </style>
</head>
<body>
    <table class="school-head">
        <tr>
            <td style="width: 104px;">
                @if ($logoAbsolutePath)
                    <img class="logo" src="{{ $logoAbsolutePath }}" alt="Logo">
                @endif
            </td>
            <td>
                <div class="school-name">{{ $identity['name'] ?? config('app.name') }}</div>
                @if (! empty($identity['slogan']))<div class="muted">{{ $identity['slogan'] }}</div>@endif
                <div class="muted">
                    {{ collect([$identity['address'] ?? null, $identity['city'] ?? null, $identity['country'] ?? null])->filter()->implode(', ') }}
                    @if (! empty($identity['phone'])) · {{ $identity['phone'] }}@endif
                    @if (! empty($identity['email'])) · {{ $identity['email'] }}@endif
                </div>
            </td>
        </tr>
    </table>

    <h1 class="title">{{ $title }}</h1>
    <div class="reference">Référence : {{ $reference }} · Émis le {{ $generatedAt->format('d/m/Y') }}</div>

    <p class="body-text">{{ $body }}</p>

    <table class="identity">
        <tr>
            <td><div class="label">Élève</div><div class="value">{{ $student->full_name }}</div></td>
            <td><div class="label">Matricule</div><div class="value">{{ $student->matricule ?: '—' }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Classe</div><div class="value">{{ $student->classRoom?->name ?? '—' }}</div></td>
            <td><div class="label">Date de naissance</div><div class="value">{{ optional($student->birth_date)->format('d/m/Y') ?? '—' }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Sexe</div><div class="value">{{ $student->gender === 'F' ? 'Féminin' : ($student->gender === 'M' ? 'Masculin' : '—') }}</div></td>
            <td><div class="label">Statut</div><div class="value">{{ $student->is_active ? 'Actif' : 'Inactif' }}</div></td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td><div class="sign-line">Le parent / tuteur</div></td>
            <td><div class="sign-line">Le Chef d'établissement · Cachet</div></td>
        </tr>
    </table>

    <div class="footer">Document généré le {{ $generatedAt->format('d/m/Y à H:i') }} — {{ $identity['name'] ?? config('app.name') }}</div>
</body>
</html>
