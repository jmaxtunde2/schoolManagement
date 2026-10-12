import { Head } from '@inertiajs/react';
import {
    Activity,
    BellRing,
    BookOpenCheck,
    GraduationCap,
    UserCog,
    Wallet,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import SeriesChart from '@/Components/Performance/SeriesChart';

const categories = [
    { key: 'finance', label: 'Finance', icon: Wallet, accent: 'text-violet-600 bg-violet-50', formatter: (value) => `${Number(value ?? 0).toLocaleString('fr-FR')} F` },
    { key: 'academique', label: 'Vie académique', icon: BookOpenCheck, accent: 'text-purple-600 bg-purple-50', formatter: (value) => `${value} %` },
    { key: 'eleves', label: 'Élèves & parents', icon: GraduationCap, accent: 'text-sky-600 bg-sky-50', formatter: (value) => Number(value ?? 0).toLocaleString('fr-FR') },
    { key: 'personnel', label: 'Personnel', icon: UserCog, accent: 'text-orange-600 bg-orange-50', formatter: (value) => Number(value ?? 0).toLocaleString('fr-FR') },
    { key: 'communication', label: 'Communication', icon: BellRing, accent: 'text-amber-600 bg-amber-50', formatter: (value) => Number(value ?? 0).toLocaleString('fr-FR') },
];

const toneClasses = {
    positive: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    neutral: 'bg-amber-50 text-amber-700 ring-amber-200',
    negative: 'bg-red-50 text-red-700 ring-red-200',
};

export default function Performance({ performance }) {
    return (
        <AuthenticatedLayout title="Performance">
            <Head title="Performance" />

            <div className="space-y-6">
                <div>
                    <p className="text-xs font-bold uppercase tracking-wider text-violet-600">Aperçu</p>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Performance de l'établissement</h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Évolution mensuelle (12 derniers mois) par catégorie : finance, vie académique, élèves & parents, personnel et communication.
                    </p>
                </div>

                <div className="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    {categories.map((category) => {
                        const data = performance[category.key];
                        const Icon = category.icon;

                        return (
                            <Card key={category.key} className="overflow-hidden">
                                <Card.Header
                                    title={
                                        <span className="flex items-center gap-3">
                                            <span className={`flex h-9 w-9 items-center justify-center rounded-xl ${category.accent}`}>
                                                <Icon className="h-4.5 w-4.5" />
                                            </span>
                                            {category.label}
                                        </span>
                                    }
                                    actions={
                                        <span className="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400">
                                            <Activity className="h-3.5 w-3.5" /> 12 derniers mois
                                        </span>
                                    }
                                />

                                <Card.Body>
                                    {data.summary.length > 0 && (
                                        <div className="mb-5 flex flex-wrap gap-2">
                                            {data.summary.map((item) => (
                                                <span
                                                    key={item.label}
                                                    className={`rounded-xl px-3 py-1.5 text-xs font-semibold ring-1 ${item.tone ? toneClasses[item.tone] : 'bg-slate-100 text-slate-600 ring-slate-200'}`}
                                                >
                                                    {item.label} : <span className="font-bold">{item.value}</span>
                                                </span>
                                            ))}
                                        </div>
                                    )}

                                    <SeriesChart
                                        buckets={data.buckets}
                                        series={data.series}
                                        formatter={category.formatter}
                                        emptyTitle={`Aucune donnée pour ${category.label.toLowerCase()}`}
                                        emptyDescription="Les douze derniers mois apparaîtront dès que des données auront été saisies."
                                    />
                                </Card.Body>
                            </Card>
                        );
                    })}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}