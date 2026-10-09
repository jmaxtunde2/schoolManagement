import { Head, Link } from '@inertiajs/react';
import {
    Building2,
    ChevronRight,
    Download,
    FileSpreadsheet,
    Plus,
    School,
    Upload,
    Users,
    GraduationCap,
    BookOpen,
    CalendarDays,
    Key,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Badge from '@/Components/UI/Badge';

const typeIcons = {
    classes: Building2,
    subjects: BookOpen,
    teachers: Users,
    students: GraduationCap,
    parents: Users,
    academic_years: CalendarDays,
    academic_periods: CalendarDays,
};

const typeColors = {
    classes: 'from-blue-500 to-indigo-600',
    subjects: 'from-violet-500 to-purple-600',
    teachers: 'from-emerald-500 to-teal-600',
    students: 'from-amber-500 to-orange-600',
    parents: 'from-rose-500 to-pink-600',
    academic_years: 'from-indigo-500 to-blue-600',
    academic_periods: 'from-cyan-500 to-teal-600',
};

export default function ImportsIndex({ schools, importTypes }) {
    return (
        <AuthenticatedLayout title="Coriyase — Platform">
            <Head title="Import en masse" />

            {/* Hero */}
            <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white shadow-lg mb-6">
                <div className="absolute -right-10 -top-10 h-36 w-36 rounded-full bg-white/10" />
                <div className="absolute -bottom-20 right-28 h-48 w-48 rounded-full bg-white/5" />

                <div className="relative p-6">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-3 flex items-center gap-3">
                                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                                    <Upload className="h-5 w-5" strokeWidth={2} />
                                </div>
                                <span className="text-sm font-medium text-white/80">
                                    Administration Coriyase
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Import en masse
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-white/75 sm:text-base">
                                Importez des données par lots pour initialiser rapidement une école :
                                classes, matières, enseignants, élèves, parents, années et périodes scolaires.
                            </p>
                        </div>

                        <div className="flex items-center gap-3 rounded-2xl bg-white/15 px-5 py-4 backdrop-blur-sm">
                            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15">
                                <FileSpreadsheet className="h-5 w-5" strokeWidth={2} />
                            </div>

                            <div>
                                <p className="text-xs text-white/70">
                                    Écoles disponibles
                                </p>

                                <p className="text-2xl font-bold">
                                    {schools.length}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Liste des écoles */}
            <Card>
                <Card.Header
                    title="Sélectionnez une école"
                    description={`${schools.length} établissement(s) actif(s) — choisissez l'école à initialiser`}
                />

                <Card.Body>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {schools.map((school) => (
                            <Link
                                key={school.id}
                                href={route('platform.imports.show', school.id)}
                                className="group flex flex-col p-5 rounded-xl border border-slate-200 bg-white hover:shadow-lg hover:border-primary/50 transition-all duration-200"
                            >
                                <div className="mb-4 flex items-center justify-between">
                                    <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                        <School className="h-5 w-5" strokeWidth={2} />
                                    </div>
                                    <Badge tone="slate">{school.slug}</Badge>
                                </div>

                                <h3 className="font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors">
                                    {school.name}
                                </h3>

                                <p className="mt-2 text-sm text-slate-500">
                                    Cliquez pour importer des données
                                </p>

                                <div className="mt-4 flex items-center justify-between text-xs text-slate-400 group-hover:text-slate-600">
                                    <span>Importer</span>
                                    <ChevronRight className="h-4 w-4" strokeWidth={2} />
                                </div>
                            </Link>
                        ))}
                    </div>

                    {schools.length === 0 && (
                        <div className="py-12 text-center">
                            <School className="mx-auto h-12 w-12 text-slate-300" strokeWidth={1.5} />
                            <p className="mt-4 text-slate-500">Aucune école active</p>
                        </div>
                    )}
                </Card.Body>
            </Card>

            {/* Guide des templates */}
            <Card className="mt-6">
                <Card.Header
                    title="Templates d'import"
                    description="Téléchargez les modèles CSV pour préparer vos fichiers"
                />

                <Card.Body>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {Object.entries(importTypes).map(([key, config]) => {
                            const Icon = typeIcons[key] || FileSpreadsheet;
                            const color = typeColors[key] || 'from-slate-500 to-slate-600';

                            return (
                                <div
                                    key={key}
                                    className="p-5 rounded-xl border border-slate-200 bg-white hover:shadow-md hover:border-primary/50 transition-all"
                                >
                                    <div className="mb-3 flex h-11 w-11 items-center justify-center rounded-xl text-white"
                                        style={{ background: `linear-gradient(135deg, ${color.replace('from-', '').replace(' to-', ', ')} 100%)` }}>
                                        <Icon className="h-5 w-5" strokeWidth={2} />
                                    </div>

                                    <h4 className="font-medium text-slate-900">{config.label}</h4>
                                    <p className="mt-1 text-sm text-slate-500">{config.description}</p>

                                    <div className="mt-3 flex flex-wrap gap-1">
                                        {(config.required_columns || []).map((col) => (
                                            <Badge key={col} tone="emerald" size="sm">{col}*</Badge>
                                        ))}
                                        {(config.optional_columns || []).map((col) => (
                                            <Badge key={col} tone="slate" size="sm">{col}</Badge>
                                        ))}
                                    </div>

                                    <Button
                                        className="mt-4 w-full gap-2"
                                        variant="outline"
                                        onClick={() => window.location.href = route('platform.imports.template', key)}
                                    >
                                        <Download className="h-4 w-4" strokeWidth={2} />
                                        Télécharger template
                                    </Button>
                                </div>
                            );
                        })}
                    </div>
                </Card.Body>
            </Card>
        </AuthenticatedLayout>
    );
}