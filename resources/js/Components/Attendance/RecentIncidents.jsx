import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarDays,
    Clock3,
    UserRoundX,
} from 'lucide-react';

import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import EmptyState from '@/Components/UI/EmptyState';

function formatDate(date) {
    if (!date) {
        return '';
    }

    return new Date(`${date}T00:00:00`).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
    });
}

/**
 * Derniers retards et absences enregistrés dans l'établissement.
 *
 * @param {object} props
 * @param {object} props.incidents   Charge utile produite par BuildRecentIncidents.
 * @param {string} props.routePrefix `admin` | `censeur` | `surveillant`…
 * @param {string} [props.attendanceRoutePrefix] Préfixe des routes d'assiduité si différent.
 * @param {string} [props.className] Classes appliquées à la carte racine.
 */
export default function RecentIncidents({
    incidents,
    routePrefix = 'admin',
    attendanceRoutePrefix,
    className = 'mt-6',
}) {
    if (!incidents) {
        return null;
    }

    const attendancePrefix = attendanceRoutePrefix ?? routePrefix;
    const items = incidents.items ?? [];

    return (
        <Card className={`overflow-hidden ${className}`}>
            <Card.Header
                title="Derniers retards & absences"
                description={
                    items.length > 0
                        ? `${incidents.late_count} retard${incidents.late_count > 1 ? 's' : ''} · ${incidents.absent_count} absence${incidents.absent_count > 1 ? 's' : ''}`
                        : undefined
                }
                actions={
                    <Link
                        href={route(`${attendancePrefix}.attendance.index`, {
                            tab: 'liste',
                        })}
                        className="inline-flex items-center gap-1.5 text-sm font-medium transition-opacity hover:opacity-80"
                        style={{ color: 'var(--color-primary)' }}
                    >
                        Voir tout
                        <ArrowRight className="h-4 w-4" />
                    </Link>
                }
            />

            <Card.Body className="p-0">
                {items.length === 0 ? (
                    <div className="p-6">
                        <EmptyState
                            title="Aucun retard ni absence"
                            description="Les retards et absences enregistrés apparaîtront ici."
                        />
                    </div>
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {items.map((incident) => (
                            <li key={incident.id}>
                                <Link
                                    href={route(
                                        `${attendancePrefix}.attendance.students.show`,
                                        incident.student.id
                                    )}
                                    className="group flex items-center justify-between gap-4 px-4 py-3 transition-colors hover:bg-slate-50 sm:px-6"
                                >
                                    <div className="flex min-w-0 items-center gap-3">
                                        <div
                                            className={
                                                incident.status === 'late'
                                                    ? 'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600'
                                                    : 'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600'
                                            }
                                        >
                                            {incident.status === 'late' ? (
                                                <Clock3 className="h-5 w-5" />
                                            ) : (
                                                <UserRoundX className="h-5 w-5" />
                                            )}
                                        </div>

                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-semibold text-slate-800 group-hover:text-primary">
                                                {incident.student.name}
                                            </p>
                                            <p className="mt-0.5 truncate text-xs text-slate-500">
                                                {incident.class_name ||
                                                    'Classe inconnue'}
                                                {incident.student.matricule
                                                    ? ` · ${incident.student.matricule}`
                                                    : ''}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex shrink-0 items-center gap-3">
                                        {incident.status === 'late' && (
                                            <span className="hidden text-xs font-medium text-amber-700 sm:inline">
                                                {incident.delay_minutes} min
                                            </span>
                                        )}

                                        <Badge
                                            tone={
                                                incident.status === 'late'
                                                    ? 'amber'
                                                    : 'red'
                                            }
                                        >
                                            {incident.status === 'late'
                                                ? 'Retard'
                                                : 'Absence'}
                                        </Badge>

                                        <span className="hidden items-center gap-1 text-xs text-slate-500 sm:inline-flex">
                                            <CalendarDays className="h-3.5 w-3.5" />
                                            {formatDate(
                                                incident.attendance_date
                                            )}
                                        </span>

                                        <ArrowRight className="h-4 w-4 text-slate-300 transition-all group-hover:translate-x-1 group-hover:text-primary" />
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </Card.Body>
        </Card>
    );
}
