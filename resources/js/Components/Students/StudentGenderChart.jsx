import { useMemo, useState } from 'react';
import { Users } from 'lucide-react';

import EmptyState from '@/Components/UI/EmptyState';
import Select from '@/Components/UI/Select';

/**
 * Répartition des élèves par sexe (filles / garçons), par classe.
 *
 * Alimenté par BuildGenderDistribution : une charge utile contenant le total de
 * l'établissement et le détail par classe. Le filtre par classe est purement
 * côté client, la charge utile étant déjà complète.
 *
 * @param {object} props
 * @param {object} props.distribution Charge utile produite par BuildGenderDistribution.
 */
const FEMALE_COLOR = '#ec4899';
const MALE_COLOR = '#3b82f6';
const UNSPECIFIED_COLOR = '#cbd5e1';

export default function StudentGenderChart({ distribution }) {
    const [classKey, setClassKey] = useState('all');

    const rows = distribution?.classes ?? [];

    const filtered = useMemo(
        () =>
            classKey === 'all'
                ? rows
                : rows.filter((row) => row.key === classKey),
        [rows, classKey]
    );

    const summary = useMemo(() => {
        const female = filtered.reduce(
            (total, row) => total + row.female,
            0
        );
        const male = filtered.reduce((total, row) => total + row.male, 0);
        const unspecified = filtered.reduce(
            (total, row) => total + row.unspecified,
            0
        );
        const students = female + male + unspecified;

        return {
            students,
            female,
            male,
            unspecified,
            female_rate:
                students > 0 ? Math.round((female / students) * 100) : 0,
            male_rate: students > 0 ? Math.round((male / students) * 100) : 0,
        };
    }, [filtered]);

    if (!distribution || (distribution.totals?.students ?? 0) === 0) {
        return (
            <EmptyState
                icon={Users}
                title="Aucun élève actif"
                description="La répartition filles / garçons apparaîtra dès que des élèves seront inscrits."
            />
        );
    }

    return (
        <div className="space-y-5">
            {/* Filtre + synthèse */}
            <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div className="grid flex-1 grid-cols-2 gap-3 sm:grid-cols-4">
                    <SummaryTile label="Élèves" value={summary.students} />
                    <SummaryTile
                        label="Filles"
                        value={summary.female}
                        hint={`${summary.female_rate}%`}
                        color={FEMALE_COLOR}
                    />
                    <SummaryTile
                        label="Garçons"
                        value={summary.male}
                        hint={`${summary.male_rate}%`}
                        color={MALE_COLOR}
                    />
                    <SummaryTile
                        label="Non renseigné"
                        value={summary.unspecified}
                        color={UNSPECIFIED_COLOR}
                    />
                </div>

                <div className="w-full lg:w-64">
                    <label className="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">
                        Filtrer par classe
                    </label>

                    <Select
                        value={classKey}
                        onChange={(event) => setClassKey(event.target.value)}
                    >
                        <option value="all">Toutes les classes</option>

                        {rows.map((row) => (
                            <option key={row.key} value={row.key}>
                                {row.name}
                            </option>
                        ))}
                    </Select>
                </div>
            </div>

            {/* Légende */}
            <div className="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs">
                <Legend color={FEMALE_COLOR} label="Filles" />
                <Legend color={MALE_COLOR} label="Garçons" />
                {summary.unspecified > 0 && (
                    <Legend
                        color={UNSPECIFIED_COLOR}
                        label="Non renseigné"
                    />
                )}
            </div>

            {/* Barres par classe */}
            <div className="space-y-3">
                {filtered.map((row) => (
                    <GenderRow key={row.key} row={row} />
                ))}
            </div>
        </div>
    );
}

function GenderRow({ row }) {
    const total = row.total;

    return (
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
            <div className="flex items-baseline justify-between gap-2 sm:w-40 sm:shrink-0 sm:flex-col sm:items-start">
                <span className="truncate text-sm font-semibold text-slate-700">
                    {row.name}
                </span>

                <span className="text-xs text-slate-400">
                    {total} élève{total > 1 ? 's' : ''}
                </span>
            </div>

            <div className="flex min-w-0 flex-1 items-center gap-3">
                <div className="flex h-6 flex-1 overflow-hidden rounded-full bg-slate-100 ring-1 ring-inset ring-slate-200">
                    {total === 0 ? (
                        <span className="flex w-full items-center justify-center text-[11px] text-slate-400">
                            aucun élève
                        </span>
                    ) : (
                        <>
                            {row.female > 0 && (
                                <div
                                    className="h-full transition-all"
                                    style={{
                                        width: `${(row.female / total) * 100}%`,
                                        backgroundColor: FEMALE_COLOR,
                                    }}
                                    title={`Filles · ${row.female}`}
                                />
                            )}

                            {row.male > 0 && (
                                <div
                                    className="h-full transition-all"
                                    style={{
                                        width: `${(row.male / total) * 100}%`,
                                        backgroundColor: MALE_COLOR,
                                    }}
                                    title={`Garçons · ${row.male}`}
                                />
                            )}

                            {row.unspecified > 0 && (
                                <div
                                    className="h-full transition-all"
                                    style={{
                                        width: `${(row.unspecified / total) * 100}%`,
                                        backgroundColor: UNSPECIFIED_COLOR,
                                    }}
                                    title={`Non renseigné · ${row.unspecified}`}
                                />
                            )}
                        </>
                    )}
                </div>

                <span className="w-24 shrink-0 text-right text-xs text-slate-500">
                    <span
                        className="font-bold"
                        style={{ color: FEMALE_COLOR }}
                    >
                        {row.female}
                    </span>
                    {' · '}
                    <span
                        className="font-bold"
                        style={{ color: MALE_COLOR }}
                    >
                        {row.male}
                    </span>
                </span>
            </div>
        </div>
    );
}

function SummaryTile({ label, value, hint, color }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-4">
            <p className="text-[11px] font-bold uppercase tracking-wide text-slate-500">
                {label}
            </p>

            <p className="mt-1 flex items-baseline gap-2 text-2xl font-bold text-slate-900">
                <span
                    style={color ? { color } : undefined}
                    className={color ? '' : 'text-slate-900'}
                >
                    {value}
                </span>

                {hint && (
                    <span className="text-xs font-semibold text-slate-400">
                        {hint}
                    </span>
                )}
            </p>
        </div>
    );
}

function Legend({ color, label }) {
    return (
        <span className="inline-flex items-center gap-2 text-slate-600">
            <span
                className="h-3 w-3 rounded-sm"
                style={{ backgroundColor: color }}
            />
            {label}
        </span>
    );
}
