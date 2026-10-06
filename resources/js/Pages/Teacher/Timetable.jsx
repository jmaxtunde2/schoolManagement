import { Head, router } from '@inertiajs/react';
import { CalendarDays, GraduationCap } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import WeekGrid, {
    AcademicYearSelect,
} from '@/Components/Timetable/WeekGrid';

export default function Timetable({
    slots = [],
    days = [],
    academicYears = [],
    selectedAcademicYearId,
}) {
    function selectYear(value) {
        router.get(
            route('teacher.timetable'),
            { academic_year_id: value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['slots', 'selectedAcademicYearId'],
            }
        );
    }

    // Volume horaire de la semaine, calculé sur les créneaux renvoyés par le serveur.
    const weeklyHours = slots.reduce((total, slot) => {
        const [startHour, startMinute] = slot.starts_at
            .split(':')
            .map(Number);
        const [endHour, endMinute] = slot.ends_at.split(':').map(Number);

        const start = startHour * 60 + startMinute;
        const end = endHour * 60 + endMinute;

        return total + Math.max(0, end - start) / 60;
    }, 0);

    const classNames = [...new Set(slots.map((slot) => slot.class_name))].filter(
        Boolean
    );

    return (
        <AuthenticatedLayout title="Mon emploi du temps">
            <Head title="Mon emploi du temps" />

            <div className="space-y-6">
                {/* Hero */}
                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-700 via-teal-700 to-cyan-800 p-6 text-white shadow-lg">
                    <div className="relative z-10">
                        <div className="mb-2 flex items-center gap-2 text-emerald-100">
                            <CalendarDays className="h-5 w-5" />

                            <span className="text-sm font-medium">
                                Espace enseignant
                            </span>
                        </div>

                        <h1 className="text-2xl font-bold sm:text-3xl">
                            Mon emploi du temps
                        </h1>

                        <p className="mt-2 max-w-2xl text-sm leading-6 text-emerald-50/90 sm:text-base">
                            Vos cours de la semaine, par jour et par heure.
                            L'emploi du temps est mis à jour par la direction.
                        </p>
                    </div>

                    <div className="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10" />
                </div>

                {/* Filtres */}
                <Card>
                    <Card.Body>
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <AcademicYearSelect
                                academicYears={academicYears}
                                selectedId={selectedAcademicYearId}
                                onSelect={selectYear}
                            />

                            <dl className="grid grid-cols-2 gap-4 sm:flex sm:gap-8">
                                <div>
                                    <dt className="text-xs font-medium uppercase tracking-wide text-slate-400">
                                        Cours / semaine
                                    </dt>

                                    <dd className="mt-1 text-lg font-bold text-slate-900">
                                        {slots.length}
                                    </dd>
                                </div>

                                <div>
                                    <dt className="text-xs font-medium uppercase tracking-wide text-slate-400">
                                        Heures / semaine
                                    </dt>

                                    <dd className="mt-1 text-lg font-bold text-slate-900">
                                        {Number(weeklyHours.toFixed(2))} h
                                    </dd>
                                </div>

                                <div>
                                    <dt className="text-xs font-medium uppercase tracking-wide text-slate-400">
                                        Classes
                                    </dt>

                                    <dd className="mt-1 text-lg font-bold text-slate-900">
                                        {classNames.length}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </Card.Body>
                </Card>

                {/* Grille */}
                <Card>
                    <Card.Header
                        title="Vue hebdomadaire"
                        description={
                            classNames.length > 0
                                ? `Classes concernées : ${classNames.join(', ')}.`
                                : 'Lundi → samedi, pour l’année scolaire sélectionnée.'
                        }
                    />

                    <Card.Body>
                        <WeekGrid
                            slots={slots}
                            days={days}
                            emptyTitle="Aucun cours planifié"
                            emptyDescription="La direction n'a pas encore publié votre emploi du temps pour cette année."
                        />
                    </Card.Body>
                </Card>

                {classNames.length > 0 && (
                    <Card>
                        <Card.Body>
                            <h2 className="flex items-center gap-2 text-sm font-semibold text-slate-900">
                                <GraduationCap className="h-4 w-4 text-slate-400" />
                                Vos classes cette semaine
                            </h2>

                            <ul className="mt-3 flex flex-wrap gap-2">
                                {classNames.map((name) => (
                                    <li
                                        key={name}
                                        className="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700"
                                    >
                                        {name}
                                    </li>
                                ))}
                            </ul>
                        </Card.Body>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
