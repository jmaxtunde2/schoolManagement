import EmptyState from '@/Components/UI/EmptyState';

/**
 * Histogramme de la performance académique (moyenne générale).
 *
 * Rendu en SVG pur (aucune librairie de graphiques) : une barre par période ou
 * par année académique, avec défilement horizontal sur mobile.
 *
 * @param {object} props
 * @param {object} props.performance Charge utile produite par BuildResultsPerformance.
 */
export default function ResultsPerformanceChart({ performance }) {
    const series = performance?.series ?? [];
    const scaleMax = performance?.max ?? 20;

    if (series.length === 0) {
        return (
            <EmptyState
                title="Aucune donnée de performance"
                description="Aucune période ou année ne contient encore de résultats calculables pour cette sélection."
            />
        );
    }

    const height = 260;
    const padding = { top: 20, right: 20, bottom: 52, left: 40 };
    const plotHeight = height - padding.top - padding.bottom;
    const groupWidth = 64;
    const plotWidth = Math.max(series.length * groupWidth, 320);
    const width = plotWidth + padding.left + padding.right;
    const barWidth = Math.min(28, groupWidth * 0.45);

    const ticks = 4;
    const yTicks = Array.from({ length: ticks + 1 }, (_, index) => {
        const value = Math.round((scaleMax / ticks) * index);

        return {
            value,
            y: padding.top + plotHeight - (value / scaleMax) * plotHeight,
        };
    });

    const barColor = (average) => {
        if (average === null) return '#cbd5e1';
        if (average >= 14) return '#059669';
        if (average >= 12) return '#0d9488';
        if (average >= 10) return '#f59e0b';

        return '#ef4444';
    };

    return (
        <div>
            <div className="mb-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                <span className="inline-flex items-center gap-2 text-slate-600">
                    Moyenne
                    <span className="font-semibold text-slate-800">
                        {performance.overall === null
                            ? '—'
                            : `${performance.overall.toFixed(2)} / 20`}
                    </span>
                </span>

                {performance.mode === 'periods' && performance.academic_year && (
                    <span className="text-xs text-slate-500">
                        {performance.academic_year}
                    </span>
                )}

                <span className="inline-flex items-center gap-2 text-xs text-slate-500">
                    <span className="h-3 w-3 rounded-sm bg-amber-500" />
                    Seuil de 10/20
                </span>
            </div>

            <div className="overflow-x-auto">
                <svg
                    width={width}
                    height={height}
                    role="img"
                    aria-label="Performance académique par période"
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

                    {/* Seuil de 10/20 */}
                    <line
                        x1={padding.left}
                        x2={width - padding.right}
                        y1={padding.top + plotHeight - (10 / scaleMax) * plotHeight}
                        y2={padding.top + plotHeight - (10 / scaleMax) * plotHeight}
                        stroke="#f59e0b"
                        strokeWidth="1.5"
                        strokeDasharray="6 4"
                    />

                    {series.map((item, index) => {
                        const groupX =
                            padding.left + index * groupWidth + groupWidth / 2;
                        const average = item.average ?? 0;
                        const barHeight = (average / scaleMax) * plotHeight;

                        return (
                            <g key={item.key}>
                                <rect
                                    x={groupX - barWidth / 2}
                                    y={padding.top + plotHeight - barHeight}
                                    width={barWidth}
                                    height={barHeight}
                                    rx="3"
                                    fill={barColor(item.average)}
                                >
                                    <title>
                                        {item.label}
                                        {item.average === null
                                            ? ' · aucune donnée'
                                            : ` · ${item.average.toFixed(2)} / 20`}
                                    </title>
                                </rect>

                                {item.average !== null && (
                                    <text
                                        x={groupX}
                                        y={
                                            padding.top +
                                            plotHeight -
                                            barHeight -
                                            6
                                        }
                                        textAnchor="middle"
                                        className="fill-slate-600 text-[10px] font-semibold"
                                    >
                                        {item.average.toFixed(1)}
                                    </text>
                                )}

                                <text
                                    x={groupX}
                                    y={height - padding.bottom + 18}
                                    textAnchor="middle"
                                    className="fill-slate-500 text-[10px]"
                                >
                                    {item.label}
                                </text>
                            </g>
                        );
                    })}
                </svg>
            </div>
        </div>
    );
}
