import { Head, Link, router, usePage } from '@inertiajs/react';
import { Award, BookOpenCheck, FileText, Filter, GraduationCap, Printer } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Select from '@/Components/UI/Select';

export default function AcademicResults({ classes, periods, filters, results, routePrefix }) {
    const { auth } = usePage().props;
    const canGenerate = auth.user.role !== 'teacher';

    function applyFilters(event) {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        router.get(route(`${routePrefix}.academic.results.index`), Object.fromEntries(data.entries()), {
            preserveState: true,
            replace: true,
        });
    }

    function generate(studentId = null) {
        router.post(route(`${routePrefix}.academic.report-cards.generate`), {
            class_id: filters.class_id,
            period_id: filters.period_id,
            student_id: studentId,
        }, { preserveScroll: true });
    }

    return (
        <AuthenticatedLayout title="Résultats académiques">
            <Head title="Résultats académiques" />
            <div className="space-y-6">
                <div>
                    <p className="text-xs font-semibold uppercase text-emerald-700">Calcul académique</p>
                    <h1 className="mt-1 text-2xl font-bold text-slate-950">Résultats par classe</h1>
                    <p className="mt-1 text-sm text-slate-500">Seules les évaluations validées entrent dans le calcul.</p>
                </div>

                <Card>
                    <Card.Body>
                        <form onSubmit={applyFilters} className="grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
                            <Select name="class_id" defaultValue={filters.class_id ?? ''} required>
                                <option value="">Sélectionner une classe</option>
                                {classes.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                            </Select>
                            <Select name="period_id" defaultValue={filters.period_id ?? ''} required>
                                <option value="">Sélectionner une période</option>
                                {periods.map((item) => <option key={item.id} value={item.id}>{item.academic_year} · {item.name}</option>)}
                            </Select>
                            <Button type="submit" variant="secondary"><Filter className="h-4 w-4" />Afficher les résultats</Button>
                        </form>
                    </Card.Body>
                </Card>

                {!results ? (
                    <Card className="p-12 text-center"><BookOpenCheck className="mx-auto h-9 w-9 text-slate-300" /><p className="mt-3 font-semibold text-slate-800">Choisissez une classe et une période</p><p className="mt-1 text-sm text-slate-500">Les moyennes seront calculées à partir des notes validées.</p></Card>
                ) : results.students.length === 0 ? (
                    <Card className="p-12 text-center"><p className="font-semibold text-slate-800">Aucun élève actif dans cette classe.</p></Card>
                ) : (
                    <Card className="overflow-hidden">
                        <Card.Header title={`${results.class_name} · ${results.period_name}`} description={`${results.students.length} élève(s) · ${results.ranked_students} résultat(s) calculable(s)`} actions={canGenerate ? <Button size="sm" onClick={() => generate()} disabled={results.ranked_students === 0}><FileText className="h-4 w-4" />Générer les bulletins</Button> : null} />
                        <Card.Body className="p-0">
                            <div className="overflow-x-auto px-4 sm:px-5">
                                <table className="min-w-full text-sm">
                                    <thead className="text-left text-xs font-semibold uppercase text-slate-500"><tr><th className="py-3 pr-4">Élève</th><th className="py-3 pr-4 text-right">Moyenne générale</th><th className="py-3 pr-4 text-center">Rang</th><th className="py-3 pr-4 text-center">Matières</th><th className="py-3 pr-4 text-center">Évaluations</th><th className="py-3 pr-4">Résultat</th><th className="py-3 text-right">Bulletin</th></tr></thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {results.students.map((student) => (
                                            <tr key={student.student_id} className="align-top">
                                                <td className="py-4 pr-4"><p className="font-semibold text-slate-900">{student.student_name}</p><p className="mt-0.5 text-xs text-slate-500">{student.matricule || '—'}</p></td>
                                                <td className="py-4 pr-4 text-right font-bold text-slate-900">{student.general_average === null ? '—' : `${student.general_average.toFixed(2)} / 20`}</td>
                                                <td className="py-4 pr-4 text-center">{student.rank ? <span className="inline-flex items-center gap-1 font-semibold text-amber-700"><Award className="h-4 w-4" />{student.rank} / {student.ranked_students}</span> : '—'}</td>
                                                <td className="py-4 pr-4 text-center">{student.subjects.length}</td>
                                                <td className="py-4 pr-4 text-center">{student.evaluation_count}</td>
                                                <td className="py-4 pr-4">{student.general_average === null ? <Badge tone="slate">Sans résultat</Badge> : <Badge tone="green">Calculé</Badge>}</td>
                                                <td className="py-4 text-right">{student.report_card ? <Link href={route(`${routePrefix}.report-cards.show`, student.report_card.id)} className="text-xs font-semibold text-emerald-700">V{student.report_card.version} · Voir</Link> : canGenerate && student.general_average !== null ? <button type="button" onClick={() => generate(student.student_id)} className="text-xs font-semibold text-emerald-700">Générer</button> : '—'}</td>
                                                <td className="hidden"><span>{student.attendance.absences} abs.</span></td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            <div className="space-y-4 border-t border-slate-100 bg-slate-50/70 p-4 sm:p-5">
                                {results.students.filter((student) => student.general_average !== null).map((student) => (
                                    <details key={student.student_id} className="group border-b border-slate-200 pb-3 last:border-0">
                                        <summary className="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-semibold text-slate-700"><span className="flex items-center gap-2"><GraduationCap className="h-4 w-4 text-slate-400" />Détail des matières · {student.student_name}</span><span className="text-xs font-normal text-slate-500">{student.subjects.length} matière(s) · assiduité {student.attendance.absences} abs. / {student.attendance.late_count} ret.</span></summary>
                                        <div className="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                            {student.subjects.map((subject) => <div key={subject.subject_id} className="flex items-center justify-between gap-3 border-l-2 border-emerald-600 bg-white px-3 py-2 text-xs"><span><span className="block font-semibold text-slate-800">{subject.subject_name}</span><span className="text-slate-500">{subject.evaluation_count} évaluation(s) · coef. {subject.coefficient}</span></span><span className="font-bold text-slate-900">{subject.average.toFixed(2)} / 20</span></div>)}
                                        </div>
                                    </details>
                                ))}
                            </div>
                        </Card.Body>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}