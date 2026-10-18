import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    BookMarked,
    Clock,
    Download,
    Eye,
    FileText,
    Filter,
    Lock,
    RotateCcw,
    Search,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Badge from '@/Components/UI/Badge';
import EmptyState from '@/Components/UI/EmptyState';
import Pagination from '@/Components/UI/Pagination';
import { difficultyBar, difficultyTones } from '@/Utils/examBank';

export default function ExamBankIndex({ resources, filters, levels, subjects, types, difficulties, routePrefix }) {
    const [search, setSearch] = useState(filters.search || '');
    const r = (name, params) => route(`${routePrefix}.exam-bank.${name}`, params);

    const updateFilters = (patch) => {
        const next = { ...filters, ...patch };
        Object.keys(next).forEach((key) => {
            if (next[key] === '' || next[key] === null || next[key] === undefined) delete next[key];
        });
        router.get(r('index'), next, { preserveState: true, preserveScroll: true, replace: true });
    };

    const hasFilters = Object.values(filters).some(Boolean);

    return (
        <AuthenticatedLayout title="Banque d'épreuves">
            <Head title="Banque d'épreuves" />

            <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-600 p-6 text-white shadow-lg">
                <div className="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10" />
                <div className="relative">
                    <div className="mb-2 flex items-center gap-2 text-indigo-100">
                        <BookMarked className="h-5 w-5" />
                        <span className="text-sm font-medium">Ressources pédagogiques</span>
                    </div>
                    <h1 className="text-2xl font-bold sm:text-3xl">Banque d'épreuves</h1>
                    <p className="mt-2 max-w-2xl text-sm text-indigo-100 sm:text-base">
                        Recherchez et utilisez des épreuves et exercices classés par niveau et matière.
                    </p>
                </div>
            </div>

            <Card className="mt-6">
                <Card.Body>
                    <form
                        className="rounded-xl border border-slate-200 bg-slate-50/70 p-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            updateFilters({ search });
                        }}
                    >
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Rechercher une épreuve..."
                                className="pl-10"
                            />
                        </div>

                        <div className="mt-3 grid grid-cols-2 gap-3 lg:grid-cols-4">
                            <Select value={filters.level || ''} onChange={(e) => updateFilters({ level: e.target.value })}>
                                <option value="">Tous les niveaux</option>
                                {levels.map((level) => (
                                    <option key={level.id} value={level.id}>{level.name}</option>
                                ))}
                            </Select>
                            <Select value={filters.subject || ''} onChange={(e) => updateFilters({ subject: e.target.value })}>
                                <option value="">Toutes les matières</option>
                                {subjects.map((subject) => (
                                    <option key={subject.id} value={subject.id}>{subject.name}</option>
                                ))}
                            </Select>
                            <Select value={filters.type || ''} onChange={(e) => updateFilters({ type: e.target.value })}>
                                <option value="">Tous les types</option>
                                {types.map((type) => (
                                    <option key={type.value} value={type.value}>{type.label}</option>
                                ))}
                            </Select>
                            <Select value={filters.difficulty || ''} onChange={(e) => updateFilters({ difficulty: e.target.value })}>
                                <option value="">Toutes difficultés</option>
                                {difficulties.map((difficulty) => (
                                    <option key={difficulty.value} value={difficulty.value}>{difficulty.label}</option>
                                ))}
                            </Select>
                        </div>

                        <div className="mt-3 flex flex-wrap items-center justify-between gap-2">
                            <p className="text-sm text-slate-500">
                                <span className="font-semibold text-slate-700">{resources.total}</span> ressource(s)
                            </p>
                            <div className="flex items-center gap-2">
                                <Button type="submit" size="sm" className="gap-1.5">
                                    <Filter className="h-4 w-4" /> Filtrer
                                </Button>
                                {hasFilters && (
                                    <button
                                        type="button"
                                        onClick={() => router.get(r('index'))}
                                        className="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700"
                                    >
                                        <RotateCcw className="h-4 w-4" /> Réinitialiser
                                    </button>
                                )}
                            </div>
                        </div>
                    </form>
                </Card.Body>
            </Card>

            {resources.data.length === 0 ? (
                <div className="mt-6">
                    <EmptyState
                        icon={BookMarked}
                        title="Aucune épreuve trouvée"
                        description="Aucune ressource ne correspond à votre recherche pour le moment."
                    />
                </div>
            ) : (
                <div className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {resources.data.map((resource) => (
                        <div key={resource.id} className="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <div className="flex flex-wrap items-center gap-2 border-b border-slate-100 px-4 py-3">
                                {resource.subject && <Badge tone="blue">{resource.subject}</Badge>}
                                {resource.level && <Badge tone="slate">{resource.level}</Badge>}
                                <Badge tone="amber" className="ml-auto">{resource.type_label}</Badge>
                            </div>

                            <div className="flex flex-1 flex-col p-4">
                                <h3 className="line-clamp-2 font-semibold text-slate-900">{resource.title}</h3>
                                <p className="mt-2 line-clamp-3 flex-1 text-sm text-slate-500">
                                    {resource.description || 'Aucune description fournie.'}
                                </p>

                                <div className="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                    <Badge tone={difficultyTones[resource.difficulty] ?? 'slate'}>{resource.difficulty_label}</Badge>
                                    {resource.duration_minutes && (
                                        <span className="inline-flex items-center gap-1">
                                            <Clock className="h-3.5 w-3.5" /> {resource.duration_minutes} min
                                        </span>
                                    )}
                                    {resource.has_answer_key && (
                                        <span className="inline-flex items-center gap-1 text-emerald-600">
                                            <Lock className="h-3.5 w-3.5" /> Corrigé
                                        </span>
                                    )}
                                </div>

                                <div className={`mt-3 h-1.5 rounded-full bg-gradient-to-r ${difficultyBar[resource.difficulty] ?? 'from-slate-400 to-slate-500'}`} />
                            </div>

                            <div className="flex items-center gap-2 border-t border-slate-100 px-4 py-3">
                                <Link href={r('show', resource.id)} className="flex-1">
                                    <Button variant="secondary" size="sm" className="w-full gap-1.5">
                                        <Eye className="h-4 w-4" /> Voir
                                    </Button>
                                </Link>
                                {resource.primary_document_id ? (
                                    <Button
                                        as="a"
                                        href={r('download', [resource.id, resource.primary_document_id])}
                                        size="sm"
                                        className="gap-1.5"
                                    >
                                        <Download className="h-4 w-4" /> Télécharger
                                    </Button>
                                ) : (
                                    <span className="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-3 py-2 text-xs text-slate-400">
                                        <FileText className="h-3.5 w-3.5" /> Aucun fichier
                                    </span>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            <Pagination meta={resources} />
        </AuthenticatedLayout>
    );
}
