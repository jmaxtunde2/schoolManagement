import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarDays,
    CheckCircle2,
    Clock3,
    DoorOpen,
    MapPin,
    Radio,
    Users,
} from 'lucide-react';

import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import EmptyState from '@/Components/UI/EmptyState';
import { cn } from '@/Utils/cn';

const statusMeta = {
    current: {
        tone: 'green',
        label: 'En cours',
        row: 'border-emerald-200 bg-emerald-50',
    },
    past: {
        tone: 'slate',
        label: 'Terminé',
        row: 'opacity-60',
    },
    upcoming: {
        tone: 'blue',
        label: 'À venir',
        row: '',
    },
};

function formatDate(date) {
    if (!date) {
        return '';
    }

    return new Date(`${date}T00:00:00`).toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });
}

function SlotRow({ slot }) {
    const meta = statusMeta[slot.status] ?? statusMeta.upcoming;

    return (
        <li
            className={cn(
                'flex items-start gap-3 rounded-lg border border-transparent px-3 py-2.5 transition',
                meta.row,
                slot.status !== 'current' && 'border-slate-100'
            )}
        >
            <div
                className={cn(
                    'flex shrink-0 flex-col items-center rounded-md px-2 py-1 text-center',
                    slot.status === 'current'
                        ? 'bg-emerald-600 text-white'
                        : 'bg-slate-100 text-slate-600'
                )}
            >
                <span className="text-xs font-semibold leading-none">
                    {slot.starts_at}
                </span>
                <span className="mt-0.5 text-[10px] leading-none opacity-80">
                    {slot.ends_at}
                </span>
            </div>

            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                    <p className="truncate text-sm font-semibold text-slate-800">
                        {slot.subject_name ?? 'Matière non renseignée'}
                    </p>
                    {slot.status === 'current' && (
                        <Badge tone={meta.tone}>
                            <Radio className="h-3 w-3 animate-pulse" />
                            {meta.label}
                        </Badge>
                    )}
                    {slot.status === 'past' && (
                        <Badge tone={meta.tone}>
                            <CheckCircle2 className="h-3 w-3" />
                            {meta.label}
                        </Badge>
                    )}
                </div>

                <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                    {slot.class_name && (
                        <span className="inline-flex items-center gap-1">
                            <Users className="h-3.5 w-3.5 text-slate-400" />
                            {slot.class_name}
                        </span>
                    )}
                    {slot.teacher_name && (
                        <span className="inline-flex items-center gap-1">
                            <MapPin className="h-3.5 w-3.5 text-slate-400" />
                            {slot.teacher_name}
                        </span>
                    )}
                    {slot.room_name && (
                        <span className="inline-flex items-center gap-1">
                            <DoorOpen className="h-3.5 w-3.5 text-slate-400" />
                            {slot.room_name}
                        </span>
                    )}
                </div>
            </div>
        </li>
    );
}

function PeriodColumn({ period }) {
    return (
        <div className="min-w-0">
            <div className="mb-2 flex items-center justify-between">
                <h3 className="text-sm font-semibold text-slate-700">
                    {period.label}
                    <span className="ml-2 text-xs font-normal text-slate-400">
                        {period.count > 0 ? `${period.count} cours` : 'aucun cours'}
                    </span>
                </h3>

                {period.in_progress_count > 0 && (
                    <span className="inline-flex items-center gap-1 text-xs font-medium text-emerald-700">
                        <span className="relative flex h-2 w-2">
                            <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75" />
                            <span className="relative inline-flex h-2 w-2 rounded-full bg-emerald-500" />
                        </span>
                        {period.in_progress_count} en cours
                    </span>
                )}
            </div>

            {period.slots.length === 0 ? (
                <p className="rounded-lg border border-dashed border-slate-200 px-3 py-6 text-center text-xs text-slate-400">
                    Aucun cours prévu.
                </p>
            ) : (
                <ul className="space-y-2">
                    {period.slots.map((slot) => (
                        <SlotRow key={slot.id} slot={slot} />
                    ))}
                </ul>
            )}
        </div>
    );
}

/**
 * Bref aperçu de l'exécution de l'emploi du temps du jour.
 *
 * @param {object} props
 * @param {object} props.brief       Charge utile produite par BuildTimetableBrief.
 * @param {string} props.routePrefix `admin` | `censeur`…
 */
export default function ExecutionBrief({ brief, routePrefix = 'admin' }) {
    if (!brief) {
        return null;
    }

    const periods = [brief.morning, brief.evening];

    return (
        <Card className="mt-6 overflow-hidden">
            <Card.Header
                title="Exécution de l’emploi du temps"
                description={`${formatDate(brief.date)} · ${brief.day_label}`}
                actions={
                    <Link
                        href={route(`${routePrefix}.timetables.index`)}
                        className="inline-flex items-center gap-1.5 text-sm font-medium transition-opacity hover:opacity-80"
                        style={{ color: 'var(--color-primary)' }}
                    >
                        Voir l’emploi du temps
                        <ArrowRight className="h-4 w-4" />
                    </Link>
                }
            />

            <Card.Body>
                {!brief.is_school_day || brief.total === 0 ? (
                    <EmptyState
                        icon={CalendarDays}
                        title={
                            brief.is_school_day
                                ? 'Aucun cours programmé aujourd’hui'
                                : `${brief.day_label} — pas de cours`
                        }
                        description={
                            brief.is_school_day
                                ? "L'emploi du temps ne contient aucun créneau pour ce jour."
                                : "Ce jour de la semaine n'accueille pas de cours dans l'emploi du temps."
                        }
                    />
                ) : (
                    <>
                        <div className="mb-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                            <span className="inline-flex items-center gap-1">
                                <Clock3 className="h-3.5 w-3.5" />
                                {brief.total} cours aujourd’hui
                            </span>
                            {brief.in_progress_count > 0 ? (
                                <span className="font-medium text-emerald-700">
                                    {brief.in_progress_count} en cours d’exécution
                                </span>
                            ) : (
                                <span>Aucun cours en cours d’exécution</span>
                            )}
                        </div>

                        <div className="grid gap-5 lg:grid-cols-2">
                            {periods.map((period) => (
                                <PeriodColumn key={period.label} period={period} />
                            ))}
                        </div>
                    </>
                )}
            </Card.Body>
        </Card>
    );
}
