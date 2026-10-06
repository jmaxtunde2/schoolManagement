import { Head, router } from '@inertiajs/react';
import { CalendarDays, GraduationCap, Hash, Users } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import WeekGrid, {
    AcademicYearSelect,
} from '@/Components/Timetable/WeekGrid';

export default function Timetable({
    student = {},
    slots = [],
    days = [],
    academicYears = [],
    selectedAcademicYearId,
}) {
    function selectYear(value) {
        router.get(
            route('parent.children.timetable', student.id),
            { academic_year_id: value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['slots', 'selectedAcademicYearId'],
            }
        );
    }

    return (
        <AuthenticatedLayout title="Emploi du temps">
            <Head title={`Emploi du temps — ${student.name ?? ''}`} />

            <div className="space-y-6">
                {/* Hero */}
                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-700 via-teal-700 to-cyan-800 p-6 text-white shadow-lg">
                    <div className="relative z-10 flex flex-col gap-5 sm:flex-row sm:items-center">
                        <div className="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white/15 backdrop-blur-sm">
                            {student.photo_url ? (
                                <img
                                    src={student.photo_url}
                                    alt=""
                                    className="h-full w-full object-cover"
                                />
                            ) : (
                                <GraduationCap className="h-8 w-8" />
                            )}
                        </div>

                        <div className="min-w-0">
                            <div className="mb-1 flex items-center gap-2 text-emerald-100">
                                <CalendarDays className="h-4 w-4" />

                                <span className="text-sm font-medium">
                                    Espace parent
                                </span>
                            </div>

                            <h1 className="truncate text-2xl font-bold sm:text-3xl">
                                {student.name ?? 'Emploi du temps'}
                            </h1>

                            <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-emerald-50/90">
                                {student.class_name && (
                                    <span className="inline-flex items-center gap-1.5">
                                        <Users className="h-4 w-4" />
                                        {student.class_name}
                                    </span>
                                )}

                                {student.matricule && (
                                    <span className="inline-flex items-center gap-1.5">
                                        <Hash className="h-4 w-4" />
                                        {student.matricule}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10" />
                </div>

                {/* Filtres */}
                <Card>
                    <Card.Body>
                        <AcademicYearSelect
                            academicYears={academicYears}
                            selectedId={selectedAcademicYearId}
                            onSelect={selectYear}
                        />
                    </Card.Body>
                </Card>

                {/* Grille */}
                <Card>
                    <Card.Header
                        title="Cours de la semaine"
                        description={`Emploi du temps de la classe de ${
                            student.class_name ?? 'votre enfant'
                        }.`}
                    />

                    <Card.Body>
                        <WeekGrid
                            slots={slots}
                            days={days}
                            emptyTitle="Aucun cours planifié"
                            emptyDescription="L'emploi du temps de cette année n'a pas encore été publié par l'établissement."
                        />
                    </Card.Body>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
