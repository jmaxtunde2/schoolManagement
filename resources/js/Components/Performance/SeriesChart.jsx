import { useMemo } from 'react';

import EmptyState from '@/Components/UI/EmptyState';

/**
 * Histogramme à barres groupées (1 ou 2 séries), rendu en SVG pur.
 *
 * Alimenté par BuildPerformanceDashboard : chaque série porte des valeurs
 * alignées sur les mêmes compartiments (mois).
 *
 * @param {object} props
 * @param {Array<{key:string,label:string}>} props.buckets Compartiments (mois).
 * @param {Array<{label:string,color:string,values:number[]}>} props.series Séries alignées.
 * @param {(value:number)=>string} [props.formatter] Formatage des valeurs.
 * @param {string} [props.emptyTitle]
 * @param {string} [props.emptyDescription]
 */
export default function SeriesChart({
    buckets,
    series,
    formatter = (value) => Number(value).toLocaleString('fr-FR'),
    emptyTitle = 'Aucune donnée sur la période',
    emptyDescription = 'Les douze derniers mois apparaîtront dès que des données auront été saisies.',
}) {
    const values = series.flatMap((item) => item.values);
    const hasData = values.some((value) => Number(value) > 0);
    const max = Math.max(...values, 1);

    const geometry = useMemo(() => {
        const height = 240;
        const padding = { top: 16, right: 16, bottom: 44, left: 52 };
        const plotHeight = height - padding.top - padding.bottom;

        return { height, padding, plotHeight };
    }, []);

    if (!hasData) {
        return (
            <EmptyState
                title={emptyTitle}
                description={emptyDescription}
            />
        );
    }

    const { height, padding, plotHeight } = geometry;
    const groupWidth = 64;
    const plotWidth = Math.max(buckets.length * groupWidth, 320);
    const width = plotWidth + padding.left + padding.right;
    const barWidth = Math.min(18, (groupWidth / Math.max(series.length, 1)) * 0.5);

    const ticks = 4;
    const yTicks = Array.from({ length: ticks + 1 }, (_, index) => {
        const value = Math.round((max / ticks) * index);

        return { value, y: padding.top + plotHeight - (value / max) * plotHeight };
    });

    return (
        <div>
            <div className="mb-4 flex flex-wrap gap-x-5 gap-y-2">
                {series.map((item) => (
                    <span key={item.label} className="inline-flex items-center gap-2 text-sm text-slate-600">
                        <span className="h-3 w-3 rounded-sm" style={{ backgroundColor: item.color }} />
                        {item.label}
                    </span>
                ))}
            </div>

            <div className="overflow-x-auto">
                <svg width={width} height={height} role="img" aria-label={series.map((item) => item.label).join(' · ')} className="max-w-none">
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
                            <text x={padding.left - 8} y={tick.y + 4} textAnchor="end" className="fill-slate-400 text-[10px]">
                                {formatter(tick.value)}
                            </text>
                        </g>
                    ))}

                    {buckets.map((bucket, index) => (
                        <g key={bucket.key}>
                            {series.map((item, seriesIndex) => {
                                const value = Number(item.values[index] ?? 0);
                                const barHeight = (value / max) * plotHeight;
                                const x = padding.left
                                    + index * groupWidth
                                    + (groupWidth - barWidth * series.length) / 2
                                    + seriesIndex * barWidth;

                                return (
                                    <rect
                                        key={item.label}
                                        x={x}
                                        y={padding.top + plotHeight - barHeight}
                                        width={barWidth}
                                        height={barHeight}
                                        rx="2"
                                        fill={item.color}
                                    >
                                        <title>{bucket.label} · {item.label} · {formatter(value)}</title>
                                    </rect>
                                );
                            })}

                            <text
                                x={padding.left + index * groupWidth + groupWidth / 2}
                                y={height - padding.bottom + 16}
                                textAnchor="middle"
                                className="fill-slate-500 text-[10px]"
                            >
                                {bucket.label}
                            </text>
                        </g>
                    ))}
                </svg>
            </div>
        </div>
    );
}