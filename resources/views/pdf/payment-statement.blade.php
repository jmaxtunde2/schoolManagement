<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Relevé de scolarité</title>
    <style>
        @page { margin: 24px 28px 28px; }
        * { box-sizing: border-box; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .school-head { border-bottom: 2px solid #0f766e; padding-bottom: 12px; width: 100%; }
        .school-head td { vertical-align: middle; }
        .logo { max-height: 64px; max-width: 90px; }
        .school-name { color: #0f3d38; font-size: 17px; font-weight: bold; }
        .muted { color: #596579; font-size: 9px; line-height: 1.5; }
        .title { margin: 15px 0 4px; text-align: center; color: #0f3d38; font-size: 18px; font-weight: bold; }
        .subtitle { margin: 0 0 14px; text-align: center; color: #53627a; font-size: 10px; }
        .identity { width: 100%; margin: 12px 0; border-collapse: collapse; }
        .identity td { border: 1px solid #cbd3df; padding: 7px 8px; width: 50%; }
        .label { color: #64748b; font-size: 8px; text-transform: uppercase; }
        .value { margin-top: 3px; font-size: 10px; font-weight: bold; }
        table.payments { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .payments th { background: #e6f2f0; color: #0f3d38; font-size: 8px; text-align: left; }
        .payments th, .payments td { border: 1px solid #cbd3df; padding: 7px 6px; }
        .payments td.number, .payments th.number { text-align: right; white-space: nowrap; }
        .summary { width: 100%; margin-top: 12px; border-collapse: collapse; }
        .summary td { width: 33.33%; border: 1px solid #cbd3df; padding: 10px; text-align: center; }
        .summary .main-value { margin-top: 3px; color: #0f3d38; font-size: 15px; font-weight: bold; }
        .signatures { width: 100%; margin-top: 34px; border-collapse: collapse; }
        .signatures td { width: 50%; height: 58px; vertical-align: top; text-align: center; }
        .sign-line { margin: 38px 12px 0; border-top: 1px solid #7b8799; padding-top: 5px; color: #596579; font-size: 8px; }
        .footer { margin-top: 12px; border-top: 1px solid #d5dce5; padding-top: 6px; color: #7b8799; font-size: 7px; text-align: right; }
    </style>
</head>
<body>
    <table class="school-head">
        <tr>
            <td style="width: 100px;">
                @if ($logoAbsolutePath)
                    <img class="logo" src="{{ $logoAbsolutePath }}" alt="Logo de l'école">
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

    <h1 class="title">Relevé de scolarité</h1>
    <p class="subtitle">Historique des versements de frais de scolarité</p>

    <table class="identity">
        <tr>
            <td><div class="label">Parent / Responsable</div><div class="value">{{ $parent->name }}</div></td>
            <td><div class="label">Téléphone</div><div class="value">{{ $parent->phone ?: '—' }}</div></td>
        </tr>
    </table>

    <table class="payments">
        <thead>
            <tr>
                <th>Date</th>
                <th>Élève</th>
                <th>Année</th>
                <th>Moyen</th>
                <th>Référence</th>
                <th class="number">Montant (FCFA)</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($payments as $payment)
            <tr>
                <td>{{ optional($payment->paid_at)->format('d/m/Y') }}</td>
                <td>{{ $payment->contribution?->student?->full_name ?? '—' }}</td>
                <td>{{ $payment->contribution?->academicYear?->name ?? '—' }}</td>
                <td>{{ $payment->methodLabel() }}</td>
                <td>{{ $payment->reference ?: ($payment->receipt_number ?: '—') }}</td>
                <td class="number">{{ number_format((int) $payment->amount, 0, ',', ' ') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align: center; color: #64748b;">Aucun versement enregistré.</td></tr>
        @endforelse
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td><div class="label">Total dû</div><div class="main-value">{{ number_format((int) $totals['due'], 0, ',', ' ') }}</div></td>
            <td><div class="label">Total versé</div><div class="main-value">{{ number_format((int) $totals['paid'], 0, ',', ' ') }}</div></td>
            <td><div class="label">Reste à payer</div><div class="main-value">{{ number_format((int) $totals['remaining'], 0, ',', ' ') }}</div></td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td><div class="sign-line">Le parent</div></td>
            <td><div class="sign-line">Comptabilité · Cachet de l'établissement</div></td>
        </tr>
    </table>

    <div class="footer">Document émis le {{ $generatedAt->format('d/m/Y à H:i') }}</div>
</body>
</html>
