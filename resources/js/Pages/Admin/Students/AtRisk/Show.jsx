import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ClipboardList, PlusCircle, UserRound } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Badge from '@/Components/UI/Badge';
import EmptyState from '@/Components/UI/EmptyState';

const levelMeta = {
    red: { dot: '🔴', label: 'Préoccupant', tone: 'red' },
    amber: { dot: '🟠', label: 'À surveiller', tone: 'amber' },
    green: { dot: '🟢', label: 'Correct', tone: 'green' },
    none: { dot: '⚪', label: 'Non évalué', tone: 'slate' },
};

export default function AtRiskShow({ student, assessment, followUps, actions, thresholds, routePrefix = 'admin' }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        type: 'academic',
        title: '',
        notes: '',
        status: 'planned',
        due_date: '',
    });

    const submit = (event) => {
        event.preventDefault();
        post(route(`${routePrefix}.students.follow-up.store`, student.id), {
            preserveScroll: true,
            onSuccess: () => reset('title', 'notes', 'due_date'),
        });
    };

    const performance = levelMeta[assessment?.performance_level ?? 'none'];
    const attendance = levelMeta[assessment?.attendance_level ?? 'none'];

    return (
        <AuthenticatedLayout title="Fiche de suivi">
            <Head title={`Suivi · ${student.name}`} />

            <div className="space-y-6">
                <Link
                    href={route(`${routePrefix}.students.at-risk`)}
                    className="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Retour aux élèves en difficulté
                </Link>

                <Card>
                    <Card.Body>
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div className="flex items-center gap-4">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                    <UserRound className="h-6 w-6" />
                                </div>
                                <div>
                                    <h1 className="text-xl font-bold tracking-tight text-slate-950">{student.name}</h1>
                                    <p className="text-sm text-slate-500">
                                        {student.class_name}
                                        {student.matricule ? ` · ${student.matricule}` : ''}
                                    </p>
                                </div>
                            </div>

                            <div className="flex gap-3">
                                <div className="rounded-xl border border-slate-200 px-4 py-2 text-center">
                                    <p className="text-lg">{performance.dot}</p>
                                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Performance
                                    </p>
                                    <p className="text-xs text-slate-400">{performance.label}</p>
                                </div>
                                <div className="rounded-xl border border-slate-200 px-4 py-2 text-center">
                                    <p className="text-lg">{attendance.dot}</p>
                                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Assiduité
                                    </p>
                                    <p className="text-xs text-slate-400">{attendance.label}</p>
                                </div>
                            </div>
                        </div>

                        {assessment && assessment.flags.length > 0 && (
                            <div className="mt-4 flex flex-wrap gap-2">
                                {assessment.flags.map((flag) => (
                                    <Badge key={flag} tone="amber">{flag}</Badge>
                                ))}
                            </div>
                        )}

                        {assessment && (
                            <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div className="rounded-xl bg-slate-50 p-3">
                                    <p className="text-lg font-bold text-slate-900">
                                        {assessment.average !== null ? `${assessment.average}/20` : '—'}
                                    </p>
                                    <p className="text-xs uppercase tracking-wide text-slate-500">Moyenne</p>
                                </div>
                                <div className="rounded-xl bg-slate-50 p-3">
                                    <p className="text-lg font-bold text-rose-700">{assessment.absences}</p>
                                    <p className="text-xs uppercase tracking-wide text-slate-500">Absences</p>
                                </div>
                                <div className="rounded-xl bg-slate-50 p-3">
                                    <p className="text-lg font-bold text-amber-600">{assessment.lates}</p>
                                    <p className="text-xs uppercase tracking-wide text-slate-500">Retards</p>
                                </div>
                                <div className="rounded-xl bg-slate-50 p-3">
                                    <p className="text-lg font-bold text-slate-900">
                                        {assessment.previous_average !== null ? `${assessment.previous_average}/20` : '—'}
                                    </p>
                                    <p className="text-xs uppercase tracking-wide text-slate-500">Période précédente</p>
                                </div>
                            </div>
                        )}
                    </Card.Body>
                </Card>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Card>
                        <Card.Header title="Résultats par matière" description={`Seuil de vigilance : ${thresholds.low_average}/20.`} />
                        <Card.Body className="p-0">
                            {!assessment || assessment.subjects.length === 0 ? (
                                <div className="p-6">
                                    <EmptyState title="Aucune note" description="Aucune évaluation validée pour cet élève." />
                                </div>
                            ) : (
                                <ul className="divide-y divide-slate-100">
                                    {assessment.subjects.map((subject) => (
                                        <li key={subject.subject} className="flex items-center justify-between px-6 py-3">
                                            <span className="text-sm text-slate-700">{subject.subject}</span>
                                            <span className={`text-sm font-semibold ${subject.below ? 'text-rose-600' : 'text-slate-800'}`}>
                                                {subject.average}/20
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Card.Body>
                    </Card>

                    <Card>
                        <Card.Header
                            title={
                                <span className="flex items-center gap-2">
                                    <span className="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                        <PlusCircle className="h-4 w-4" />
                                    </span>
                                    Ajouter une action de suivi
                                </span>
                            }
                        />
                        <Card.Body>
                            <form onSubmit={submit} className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <label className="block">
                                        <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Type</span>
                                        <select
                                            value={data.type}
                                            onChange={(e) => setData('type', e.target.value)}
                                            className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                        >
                                            {Object.entries(actions.types).map(([value, label]) => (
                                                <option key={value} value={value}>{label}</option>
                                            ))}
                                        </select>
                                    </label>

                                    <label className="block">
                                        <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Statut</span>
                                        <select
                                            value={data.status}
                                            onChange={(e) => setData('status', e.target.value)}
                                            className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                        >
                                            {Object.entries(actions.statuses).map(([value, label]) => (
                                                <option key={value} value={value}>{label}</option>
                                            ))}
                                        </select>
                                    </label>
                                </div>

                                <label className="block">
                                    <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Intitulé</span>
                                    <input
                                        type="text"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        placeholder="Ex. Entretien avec les parents"
                                        className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                    />
                                    {errors.title && <p className="mt-1 text-xs text-rose-600">{errors.title}</p>}
                                </label>

                                <label className="block">
                                    <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</span>
                                    <textarea
                                        rows={3}
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                    />
                                </label>

                                <label className="block">
                                    <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Échéance</span>
                                    <input
                                        type="date"
                                        value={data.due_date}
                                        onChange={(e) => setData('due_date', e.target.value)}
                                        className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                    />
                                </label>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                                >
                                    <PlusCircle className="h-4 w-4" />
                                    Enregistrer l'action
                                </button>
                            </form>
                        </Card.Body>
                    </Card>
                </div>

                <Card className="overflow-hidden">
                    <Card.Header
                        title={
                            <span className="flex items-center gap-2">
                                <span className="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                    <ClipboardList className="h-4 w-4" />
                                </span>
                                Historique des actions
                            </span>
                        }
                        description={`${followUps.length} action(s) enregistrée(s).`}
                    />
                    <Card.Body className="p-0">
                        {followUps.length === 0 ? (
                            <div className="p-6">
                                <EmptyState title="Aucune action" description="Ajoutez une première action de suivi." />
                            </div>
                        ) : (
                            <ul className="divide-y divide-slate-100">
                                {followUps.map((followUp) => (
                                    <li key={followUp.id} className="px-6 py-4">
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <div className="flex items-center gap-2">
                                                <Badge tone="slate">{followUp.type_label}</Badge>
                                                <p className="text-sm font-semibold text-slate-800">{followUp.title}</p>
                                            </div>
                                            <Badge tone={followUp.status_tone}>{followUp.status_label}</Badge>
                                        </div>
                                        {followUp.notes && <p className="mt-2 text-sm text-slate-600">{followUp.notes}</p>}
                                        <p className="mt-2 text-xs text-slate-400">
                                            {followUp.due_date ? `Échéance : ${followUp.due_date} · ` : ''}
                                            {followUp.author ? `Par ${followUp.author}` : ''}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card.Body>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
