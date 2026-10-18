<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 26px 28px 30px; }
        * { box-sizing: border-box; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .school-head { border-bottom: 2px solid {{ $identity['primary_color'] ?? '#1E40AF' }}; padding-bottom: 12px; width: 100%; }
        .school-head td { vertical-align: middle; }
        .logo { max-height: 60px; max-width: 90px; }
        .school-name { color: #102a54; font-size: 17px; font-weight: bold; }
        .muted { color: #596579; font-size: 9px; line-height: 1.5; }
        .title { margin: 16px 0 2px; text-align: center; color: #102a54; font-size: 17px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; }
        .subtitle { margin: 0 0 12px; text-align: center; color: #53627a; font-size: 10px; }
        .meta { width: 100%; border-collapse: collapse; margin: 12px 0 16px; }
        .meta td { border: 1px solid #cbd3df; padding: 8px 9px; }
        .meta .label { color: #64748b; font-size: 8px; text-transform: uppercase; }
        .meta .value { margin-top: 3px; font-size: 11px; font-weight: bold; }
        .section-title { margin: 16px 0 6px; padding-bottom: 4px; border-bottom: 1px solid #cbd3df; color: #1e3558; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .data th { background: #eaf0f8; color: #1e3558; font-size: 8px; text-align: left; text-transform: uppercase; }
        .data th, .data td { border: 1px solid #cbd3df; padding: 7px 8px; }
        .data td.center, .data th.center { text-align: center; }
        .footer { margin-top: 16px; border-top: 1px solid #d5dce5; padding-top: 6px; color: #7b8799; font-size: 7px; text-align: right; }
    </style>
</head>
<body>
    <table class="school-head">
        <tr>
            <td style="width: 100px;">
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
                </div>
            </td>
            <td style="text-align: right; vertical-align: top;">
                <div class="muted">Émis le {{ $generatedAt->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <h1 class="title">{{ $title }}</h1>
    @if ($subtitle)<div class="subtitle">{{ $subtitle }}</div>@endif

    @if (! empty($meta))
        <table class="meta">
            <tr>
                @foreach ($meta as $label => $value)
                    <td>
                        <div class="label">{{ $label }}</div>
                        <div class="value">{{ $value }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    @foreach ($tables as $table)
        @if (! empty($table['title']))<div class="section-title">{{ $table['title'] }}</div>@endif
        <table class="data">
            <thead>
                <tr>
                    @foreach ($table['columns'] as $column)
                        <th class="{{ ! empty($column['center']) ? 'center' : '' }}">{{ $column['label'] ?? $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($table['rows'] as $row)
                    <tr>
                        @foreach ($row as $index => $cell)
                            <td class="{{ ! empty($table['columns'][$index]['center']) ? 'center' : '' }}">{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($table['columns']) }}" class="center">Aucune donnée.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    <div class="footer">
        {{ $footer ?? '' }}
        Document généré le {{ $generatedAt->format('d/m/Y à H:i') }} — {{ $identity['name'] ?? config('app.name') }}
    </div>
</body>
</html>
