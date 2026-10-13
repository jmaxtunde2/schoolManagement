<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reçu de paiement</title>
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
        .receipt-no { margin: 0 0 14px; text-align: center; color: #53627a; font-size: 10px; }
        .amount-box { margin: 16px 0; padding: 14px; border: 2px solid #0f766e; text-align: center; }
        .amount-label { color: #64748b; font-size: 9px; text-transform: uppercase; }
        .amount-value { margin-top: 4px; color: #0f3d38; font-size: 24px; font-weight: bold; }
        table.identity { width: 100%; border-collapse: collapse; margin: 12px 0; }
        .identity td { border: 1px solid #cbd3df; padding: 7px 8px; width: 50%; }
        .label { color: #64748b; font-size: 8px; text-transform: uppercase; }
        .value { margin-top: 3px; font-size: 10px; font-weight: bold; }
        .signatures { width: 100%; margin-top: 40px; border-collapse: collapse; }
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
                </div>
            </td>
        </tr>
    </table>

    <h1 class="title">Reçu de paiement</h1>
    <p class="receipt-no">N° {{ $payment->receipt_number ?: ('REC-'.$payment->id) }} · Émis le {{ $generatedAt->format('d/m/Y') }}</p>

    <div class="amount-box">
        <div class="amount-label">Montant reçu</div>
        <div class="amount-value">{{ number_format((int) $payment->amount, 0, ',', ' ') }} FCFA</div>
    </div>

    <table class="identity">
        <tr>
            <td><div class="label">Élève</div><div class="value">{{ $contribution?->student?->full_name ?? '—' }}</div></td>
            <td><div class="label">Classe</div><div class="value">{{ $contribution?->student?->classRoom?->name ?? '—' }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Parent / Responsable</div><div class="value">{{ $payment->parent?->name ?? '—' }}</div></td>
            <td><div class="label">Année scolaire</div><div class="value">{{ $contribution?->academicYear?->name ?? '—' }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Moyen de paiement</div><div class="value">{{ $payment->methodLabel() }}</div></td>
            <td><div class="label">Référence</div><div class="value">{{ $payment->reference ?: '—' }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Date du versement</div><div class="value">{{ optional($payment->paid_at)->format('d/m/Y') }}</div></td>
            <td><div class="label">Reste à payer</div><div class="value">{{ number_format((int) ($contribution?->remaining() ?? 0), 0, ',', ' ') }} FCFA</div></td>
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
