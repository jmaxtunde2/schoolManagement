import { useMemo } from 'react';

import EmptyState from '@/Components/UI/EmptyState';

/**
 * Histogramme groupé des retards et absences.
 *
 * Rendu en SVG pur (aucune librairie de graphiques) : deux barres par période,
 * adapté à un affichage sur mobile grâce à un défilement horizontal.
 *
 * @param {object} props
 * @param {object} props.evolution Charge utile produite par BuildAttendanceEvolution.
 */
export default function EvolutionChart({ evolution }) {
    const buckets = evolution?.buckets ?? [];
    const max = Math.max(evolution?.max ?? 0, 1);

    const geometry = useMemo(() => {
        const height = 240;
        const padding = { top: 16, right: 16, bottom: 44, left: 34 };
        const plotHeight = height - padding.top - padding.bottom;

        return { height, padding, plotHeight };
    }, []);

    if (buckets.length === 0) {
        return (
            <EmptyState
                title="Aucune donnée sur cette période"
                description="Modifiez la période ou la granularité pour afficher l'évolution des retards et absences."
            />
        );
    }

    const { height, padding, plotHeight } = geometry;
    const groupWidth = 48;
    const plotWidth = Math.max(buckets.length * groupWidth, 320);
    const width = plotWidth + padding.left + padding.right;
    const barWidth = Math.min(16, groupWidth * 0.3);

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
            {/* Légende + totaux */}
            <div className="mb-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                <span className="inline-flex items-center gap-2 text-slate-600">
                    <span className="h-3 w-3 rounded-sm bg-red-500" />
                    Absences
                    <span className="font-semibold text-slate-800">
                        {evolution.totals.absences}
                    </span>
                </span>

                <span className="inline-flex items-center gap-2 text-slate-600">
                    <span className="h-3 w-3 rounded-sm bg-amber-500" />
                    Retards
                    <span className="font-semibold text-slate-800">
                        {evolution.totals.retards}
                    </span>
                </span>

                {evolution.totals.delay_minutes > 0 && (
                    <span className="text-xs text-slate-500">
                        {evolution.totals.delay_minutes} minutes de retard cumulées
                    </span>
                )}
            </div>

            <div className="overflow-x-auto">
                <svg
                    width={width}
                    height={height}
                    role="img"
                    aria-label="Évolution des retards et absences"
                    className="max-w-none"
                >
                    {/* Lignes de repère */}
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
                                {tick.value}
                            </text>
                        </g>
                    ))}

                    {buckets.map((bucket, index) => {
                        const groupX =
                            padding.left + index * groupWidth + groupWidth / 2;
                        const absenceHeight =
                            (bucket.absences / max) * plotHeight;
                        const retardHeight = (bucket.retards / max) * plotHeight;

                        return (
                            <g key={bucket.key}>
                                {/* Absences */}
                                <rect
                                    x={groupX - barWidth - 2}
                                    y={padding.top + plotHeight - absenceHeight}
                                    width={barWidth}
                                    height={absenceHeight}
                                    rx="2"
                                    fill="#ef4444"
                                >
                                    <title>
                                        {bucket.label} · {bucket.absences} absence(s)
                                    </title>
                                </rect>

                                {/* Retards */}
                                <rect
                                    x={groupX + 2}
                                    y={padding.top + plotHeight - retardHeight}
                                    width={barWidth}
                                    height={retardHeight}
                                    rx="2"
                                    fill="#f59e0b"
                                >
                                    <title>
                                        {bucket.label} · {bucket.retards} retard(s)
                                    </title>
                                </rect>

                                {/* Étiquette de période */}
                                <text
                                    x={groupX}
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
