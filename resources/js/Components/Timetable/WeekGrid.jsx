import { CalendarDays, Clock3, DoorOpen, MapPin, Users } from 'lucide-react';

import EmptyState from '@/Components/UI/EmptyState';
import { cn } from '@/Utils/cn';

/**
 * Palette des créneaux, choisie de façon déterministe à partir du sujet :
 * deux cours de la même matière partagent la même couleur d'un rendu à l'autre,
 * sans avoir à stocker quoi que ce soit en base.
 */
const TONES = [
    { card: 'border-emerald-200 bg-emerald-50', title: 'text-emerald-900', meta: 'text-emerald-700/70' },
    { card: 'border-sky-200 bg-sky-50', title: 'text-sky-900', meta: 'text-sky-700/70' },
    { card: 'border-violet-200 bg-violet-50', title: 'text-violet-900', meta: 'text-violet-700/70' },
    { card: 'border-amber-200 bg-amber-50', title: 'text-amber-900', meta: 'text-amber-700/70' },
    { card: 'border-rose-200 bg-rose-50', title: 'text-rose-900', meta: 'text-rose-700/70' },
    { card: 'border-teal-200 bg-teal-50', title: 'text-teal-900', meta: 'text-teal-700/70' },
];

function toneFor(subject) {
    const source = String(subject ?? '');

    if (!source) {
        return TONES[0];
    }

    let hash = 0;

    for (let index = 0; index < source.length; index += 1) {
        hash = (hash + source.charCodeAt(index)) % TONES.length;
    }

    return TONES[hash];
}

/**
 * Grille hebdomadaire commune aux trois vues (administration, enseignant, parent).
 *
 * Le rendu empile les créneaux par jour plutôt que de les positionner
 * proportionnellement aux heures : la lecture reste correcte sur mobile, où la
 * grille devient une simple liste par jour.
 *
 * @param {object}   props
 * @param {Array}    props.slots   Créneaux déjà ordonnés par jour puis heure.
 * @param {Array}    props.days    [{ value, label }] — Lundi → Samedi.
 * @param {Function} [props.renderSlot] Surcharge du rendu d'un créneau (boutons d'action).
 * @param {string}   [props.emptyTitle]
 * @param {string}   [props.emptyDescription]
 */
export default function WeekGrid({
    slots = [],
    days = [],
    renderSlot,
    emptyTitle = 'Aucun cours planifié',
    emptyDescription = "L'emploi du temps de cette période est vide pour le moment.",
}) {
    if (slots.length === 0) {
        return (
            <EmptyState
                icon={CalendarDays}
                title={emptyTitle}
                description={emptyDescription}
            />
        );
    }

    const byDay = days.map((day) => ({
        ...day,
        slots: slots.filter((slot) => slot.day_of_week === day.value),
    }));

    return (
        <div>
            {/* Desktop / tablette : une colonne par jour */}
            <div className="hidden gap-3 lg:grid lg:grid-cols-6">
                {byDay.map((day) => (
                    <section
                        key={day.value}
                        className="flex min-w-0 flex-col rounded-xl border border-slate-200 bg-slate-50/60"
                    >
                        <h3 className="border-b border-slate-200 px-3 py-2.5 text-center text-sm font-semibold text-slate-700">
                            {day.label}
                        </h3>

                        <div className="flex flex-1 flex-col gap-2 p-2">
                            {day.slots.length === 0 ? (
                                <p className="py-4 text-center text-xs text-slate-400">
                                    —
                                </p>
                            ) : (
                                day.slots.map((slot) =>
                                    renderSlot ? (
                                        renderSlot(slot)
                                    ) : (
                                        <SlotCard
                                            key={slot.id}
                                            slot={slot}
                                        />
                                    )
                                )
                            )}
                        </div>
                    </section>
                ))}
            </div>

            {/* Mobile : une liste par jour, jamais une grille à défilement horizontal */}
            <div className="space-y-4 lg:hidden">
                {byDay.map((day) => (
                    <section
                        key={day.value}
                        className="overflow-hidden rounded-xl border border-slate-200"
                    >
                        <h3 className="border-b border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-700">
                            {day.label}
                            <span className="ml-2 text-xs font-normal text-slate-400">
                                {day.slots.length > 0
                                    ? `${day.slots.length} cours`
                                    : 'aucun cours'}
                            </span>
                        </h3>

                        <div className="space-y-2 p-2">
                            {day.slots.length === 0 ? (
                                <p className="py-3 text-center text-xs text-slate-400">
                                    Aucun cours ce jour.
                                </p>
                            ) : (
                                day.slots.map((slot) =>
                                    renderSlot ? (
                                        renderSlot(slot)
                                    ) : (
                                        <SlotCard
                                            key={slot.id}
                                            slot={slot}
                                        />
                                    )
                                )
                            )}
                        </div>
                    </section>
                ))}
            </div>
        </div>
    );
}

/** Carte d'un créneau. `slot` suit la forme renvoyée par les contrôleurs. */
export function SlotCard({ slot, className }) {
    const tone = toneFor(slot.subject_name);

    return (
        <article
            className={cn(
                'rounded-lg border p-2.5 shadow-sm',
                tone.card,
                className
            )}
        >
            <p
                className={cn(
                    'flex items-center gap-1.5 text-xs font-semibold',
                    tone.meta
                )}
            >
                <Clock3 className="h-3.5 w-3.5 shrink-0" />
                {slot.time_range}
            </p>

            <p
                className={cn(
                    'mt-1.5 text-sm font-semibold leading-snug',
                    tone.title
                )}
            >
                {slot.subject_name ?? 'Matière non renseignée'}
            </p>

            <div className="mt-1.5 space-y-1">
                {slot.class_name && (
                    <p className="flex items-center gap-1.5 text-xs text-slate-600">
                        <Users className="h-3.5 w-3.5 shrink-0 text-slate-400" />
                        {slot.class_name}
                    </p>
                )}

                {slot.teacher_name && (
                    <p className="flex items-center gap-1.5 text-xs text-slate-600">
                        <MapPin className="h-3.5 w-3.5 shrink-0 text-slate-400" />
                        {slot.teacher_name}
                    </p>
                )}

                {slot.room && (
                    <p className="flex items-center gap-1.5 text-xs text-slate-600">
                        <DoorOpen className="h-3.5 w-3.5 shrink-0 text-slate-400" />
                        {slot.room}
                    </p>
                )}
            </div>
        </article>
    );
}

/** Sélecteur d'année scolaire, commun aux trois vues. */
export function AcademicYearSelect({
    academicYears = [],
    selectedId,
    onSelect,
    className,
}) {
    if (academicYears.length === 0) {
        return null;
    }

    return (
        <div className={cn('min-w-[200px]', className)}>
            <label
                htmlFor="timetable-academic-year"
                className="mb-1.5 block text-sm font-medium text-slate-700"
            >
                Année scolaire
            </label>

            <select
                id="timetable-academic-year"
                value={selectedId ?? ''}
                onChange={(event) => onSelect(event.target.value)}
                className="block min-h-[44px] w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
            >
                {academicYears.map((year) => (
                    <option key={year.id} value={year.id}>
                        {year.name}
                        {year.is_current ? ' (en cours)' : ''}
                    </option>
                ))}
            </select>
        </div>
    );
}
