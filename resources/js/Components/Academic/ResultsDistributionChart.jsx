import EmptyState from '@/Components/UI/EmptyState';

/**
 * Répartition de la performance académique par mention (bar chart).
 *
 * Rendu en SVG pur (aucune librairie de graphiques) : une barre verticale
 * empilée par période ou par année académique. La hauteur de chaque barre
 * représente 100 % des élèves évalués, découpée selon les mentions
 * (excellent, bien, assez, passable, échec). Le taux de réussite (seuil de
 * 10/20) est affiché au-dessus de chaque barre.
 *
 * @param {object} props
 * @param {object} props.performance Charge utile produite par BuildResultsPerformance.
 */
const MENTIONS = {
    excellent: { label: 'Excellent', min: '16', color: '#059669' },
    bien: { label: 'Bien', min: '14', color: '#22c55e' },
    assez: { label: 'Assez bien', min: '12', color: '#eab308' },
    passable: { label: 'Passable', min: '10', color: '#f59e0b' },
    echec: { label: 'Échec', min: '< 10', color: '#ef4444' },
};

/** Ordre d'affichage de la légende (du meilleur au moins bon). */
const LEGEND = ['excellent', 'bien', 'assez', 'passable', 'echec'];

/** Ordre d'empilement dans la barre (du bas vers le haut). */
const STACK = ['echec', 'passable', 'assez', 'bien', 'excellent'];

export default function ResultsDistributionChart({ performance }) {
    const series = performance?.series ?? [];
    const withData = series.filter(
        (item) => (item.distribution?.graded ?? 0) > 0
    );

    if (withData.length === 0) {
        return (
            <EmptyState
                title="Aucune répartition disponible"
                description="Aucune période ou année ne contient encore de résultats calculables pour cette sélection."
            />
        );
    }

    const graded = withData.reduce(
        (total, item) => total + (item.distribution.graded ?? 0),
        0
    );
    const success = withData.reduce(
        (total, item) => total + (item.distribution.success ?? 0),
        0
    );
    const failure = withData.reduce(
        (total, item) => total + (item.distribution.failure ?? 0),
        0
    );
    const successRate = graded > 0 ? Math.round((success / graded) * 100) : 0;
    const failureRate = graded > 0 ? Math.round((failure / graded) * 100) : 0;

    const height = 280;
    const padding = { top: 30, right: 20, bottom: 52, left: 44 };
    const plotHeight = height - padding.top - padding.bottom;
    const groupWidth = 76;
    const plotWidth = Math.max(series.length * groupWidth, 280);
    const width = plotWidth + padding.left + padding.right;
    const barWidth = Math.min(46, groupWidth * 0.6);

    const yTicks = [0, 25, 50, 75, 100];
    const yToPx = (percent) =>
        padding.top + plotHeight - (Math.min(percent, 100) / 100) * plotHeight;

    return (
        <div>
            {/* Synthèse */}
            <div className="mb-5 grid gap-3 sm:grid-cols-3">
                <SummaryTile
                    label="Élèves évalués"
                    value={graded}
                    accent="from-slate-500 to-slate-700"
                />
                <SummaryTile
                    label="Taux de réussite"
                    value={`${successRate}%`}
                    hint={`${success} élève(s)`}
                    accent="from-emerald-500 to-teal-600"
                />
                <SummaryTile
                    label="Taux d’échec"
                    value={`${failureRate}%`}
                    hint={`${failure} élève(s)`}
                    accent="from-rose-500 to-red-600"
                />
            </div>

            {/* Légende */}
            <div className="mb-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs">
                {LEGEND.map((key) => (
                    <span
                        key={key}
                        className="inline-flex items-center gap-2 text-slate-600"
                    >
                        <span
                            className="h-3 w-3 rounded-sm"
                            style={{ backgroundColor: MENTIONS[key].color }}
                        />
                        {MENTIONS[key].label}
                        <span className="text-slate-400">
                            ({MENTIONS[key].min})
                        </span>
                    </span>
                ))}
            </div>

            {/* Bar chart */}
            <div className="overflow-x-auto">
                <svg
                    width={width}
                    height={height}
                    role="img"
                    aria-label="Répartition des élèves par mention et taux de réussite"
                    className="max-w-none"
                >
                    {/* Lignes de repère (%) */}
                    {yTicks.map((tick) => (
                        <g key={tick}>
                            <line
                                x1={padding.left}
                                x2={width - padding.right}
                                y1={yToPx(tick)}
                                y2={yToPx(tick)}
                                stroke="#e2e8f0"
                                strokeWidth="1"
                            />
                            <text
                                x={padding.left - 8}
                                y={yToPx(tick) + 4}
                                textAnchor="end"
                                className="fill-slate-400 text-[10px]"
                            >
                                {tick}%
                            </text>
                        </g>
                    ))}

                    {series.map((item, index) => {
                        const distribution = item.distribution ?? {};
                        const gradedItem = distribution.graded ?? 0;
                        const groupX =
                            padding.left + index * groupWidth + groupWidth / 2;

                        if (gradedItem === 0) {
                            return (
                                <g key={item.key}>
                                    <rect
                                        x={groupX - barWidth / 2}
                                        y={padding.top}
                                        width={barWidth}
                                        height={plotHeight}
                                        rx="4"
                                        fill="#f1f5f9"
                                    />
                                    <text
                                        x={groupX}
                                        y={padding.top + plotHeight / 2}
                                        textAnchor="middle"
                                        className="fill-slate-400 text-[10px]"
                                    >
                                        aucune donnée
                                    </text>
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
                        }

                        let cumulative = 0;

                        return (
                            <g key={item.key}>
                                {STACK.map((key) => {
                                    const value = distribution[key] ?? 0;

                                    if (value === 0) return null;

                                    const fraction = value / gradedItem;
                                    const segmentHeight = fraction * plotHeight;
                                    const y =
                                        padding.top +
                                        plotHeight -
                                        (cumulative + fraction) * plotHeight;

                                    cumulative += fraction;

                                    return (
                                        <rect
                                            key={key}
                                            x={groupX - barWidth / 2}
                                            y={y}
                                            width={barWidth}
                                            height={segmentHeight}
                                            fill={MENTIONS[key].color}
                                        >
                                            <title>
                                                {item.label} ·{' '}
                                                {MENTIONS[key].label} · {value}{' '}
                                                élève(s)
                                            </title>
                                        </rect>
                                    );
                                })}

                                {/* Taux de réussite */}
                                <text
                                    x={groupX}
                                    y={padding.top - 8}
                                    textAnchor="middle"
                                    className={`text-[11px] font-bold ${successTextClass(distribution.success_rate)}`}
                                >
                                    {distribution.success_rate ?? 0}%
                                </text>

                                {/* Libellé de la période */}
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

function SummaryTile({ label, value, hint, accent }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-4">
            <div className="flex items-center gap-3">
                <span
                    className={`h-9 w-1.5 rounded-full bg-gradient-to-b ${accent}`}
                />

                <div>
                    <p className="text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        {label}
                    </p>

                    <p className="text-xl font-bold text-slate-900">
                        {value}
                        {hint && (
                            <span className="ml-2 text-xs font-normal text-slate-400">
                                {hint}
                            </span>
                        )}
                    </p>
                </div>
            </div>
        </div>
    );
}

function successTextClass(rate) {
    if (rate === null || rate === undefined) return 'fill-slate-300';
    if (rate >= 75) return 'fill-emerald-600';
    if (rate >= 50) return 'fill-amber-600';

    return 'fill-rose-600';
}
