import { useMemo } from 'react';

import EmptyState from '@/Components/UI/EmptyState';

/**
 * Histogramme des montants par mois (dépenses, ventes, encaissements).
 *
 * Rendu en SVG pur (aucune librairie de graphiques), avec défilement horizontal
 * sur mobile.
 *
 * @param {object} props
 * @param {object} props.evolution Charge utile produite par BuildMoneyEvolution.
 * @param {string} props.color Couleur des barres.
 * @param {string} props.legend Intitulé affiché dans la légende.
 * @param {string} [props.emptyTitle]
 * @param {string} [props.emptyDescription]
 */
export default function MoneyEvolutionChart({
    evolution,
    color = '#059669',
    legend = 'Montant',
    emptyTitle = 'Aucune donnée sur la période',
    emptyDescription = 'Les douze derniers mois seront représentés dès que des données auront été saisies.',
}) {
    const buckets = evolution?.buckets ?? [];
    const max = Math.max(evolution?.max ?? 0, 1);
    const totals = evolution?.totals ?? { value: 0, count: 0 };

    const money = (value) => `${Number(value ?? 0).toLocaleString('fr-FR')} F`;

    const geometry = useMemo(() => {
        const height = 240;
        const padding = { top: 16, right: 16, bottom: 44, left: 52 };
        const plotHeight = height - padding.top - padding.bottom;

        return { height, padding, plotHeight };
    }, []);

    if (buckets.length === 0) {
        return (
            <EmptyState
                title={emptyTitle}
                description={emptyDescription}
            />
        );
    }

    const { height, padding, plotHeight } = geometry;
    const groupWidth = 56;
    const plotWidth = Math.max(buckets.length * groupWidth, 320);
    const width = plotWidth + padding.left + padding.right;
    const barWidth = Math.min(28, groupWidth * 0.45);

    const ticks = 4;
    const yTicks = Array.from({ length: ticks + 1 }, (_, index) => {
        const value = Math.round((max / ticks) * index);

        return {
            value,
            y: padding.top + plotHeight - (value / max) * plotHeight,
        };
    });

    return (
        <div>
            <div className="mb-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                <span className="inline-flex items-center gap-2 text-slate-600">
                    <span className="h-3 w-3 rounded-sm" style={{ backgroundColor: color }} />
                    {legend}
                    <span className="font-semibold text-slate-800">{money(totals.value)}</span>
                </span>

                {totals.count > 0 && (
                    <span className="text-xs text-slate-500">
                        {totals.count} opération{totals.count > 1 ? 's' : ''} sur la période
                    </span>
                )}
            </div>

            <div className="overflow-x-auto">
                <svg
                    width={width}
                    height={height}
                    role="img"
                    aria-label={legend}
                    className="max-w-none"
                >
                    {yTicks.map((tick) => (
                        <g key={tick.value}>
                            <line
                                x1={padding.left}
                                x2={width - padding.right}
                                y1={tick.y}
                                y2={tick.y}
                                stroke="#e2e8f0"
                                strokeWidth="1"
                            />
                            <text
                                x={padding.left - 8}
                                y={tick.y + 4}
                                textAnchor="end"
                                className="fill-slate-400 text-[10px]"
                            >
                                {money(tick.value)}
                            </text>
                        </g>
                    ))}

                    {buckets.map((bucket, index) => {
                        const barHeight = (bucket.value / max) * plotHeight;
                        const x = padding.left + index * groupWidth + (groupWidth - barWidth) / 2;

                        return (
                            <g key={bucket.key}>
                                <rect
                                    x={x}
                                    y={padding.top + plotHeight - barHeight}
                                    width={barWidth}
                                    height={barHeight}
                                    rx="2"
                                    fill={color}
                                >
                                    <title>
                                        {bucket.label} · {money(bucket.value)} · {bucket.count} opération(s)
                                    </title>
                                </rect>

                                <text
                                    x={padding.left + index * groupWidth + groupWidth / 2}
                                    y={height - padding.bottom + 16}
                                    textAnchor="middle"
                                    className="fill-slate-500 text-[10px]"
                                >
                                    {bucket.label}
                                </text>
                            </g>
                        );
                    })}
                </svg>
            </div>
        </div>
    );
}