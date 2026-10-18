import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Archive,
    BookOpen,
    BookMarked,
    CheckCircle2,
    Eye,
    FileDown,
    Filter,
    Layers,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    Settings2,
    Trash2,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Table from '@/Components/UI/Table';
import Pagination from '@/Components/UI/Pagination';
import Badge from '@/Components/UI/Badge';
import EmptyState from '@/Components/UI/EmptyState';
import StatCard from '@/Components/UI/StatCard';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import { difficultyTones, statusTones } from '@/Utils/examBank';

export default function ExamBankIndex({ resources, filters, levels, subjects, types, difficulties, statuses, stats }) {
    const [search, setSearch] = useState(filters.search || '');
    const [confirming, setConfirming] = useState(null);
    const [processing, setProcessing] = useState(false);

    const updateFilters = (patch) => {
        const next = { ...filters, ...patch };
        Object.keys(next).forEach((key) => {
            if (next[key] === '' || next[key] === null || next[key] === undefined) delete next[key];
        });
        router.get(route('platform.exam-bank.index'), next, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const hasFilters = Object.values(filters).some(Boolean);

    const changeStatus = (resource, status) => {
        router.patch(
            route('platform.exam-bank.status', resource.id),
            { status },
            { preserveScroll: true, preserveState: true },
        );
    };

    const askDelete = (resource) => {
        setConfirming(resource);
    };

    const confirmDelete = () => {
        setProcessing(true);
        router.delete(route('platform.exam-bank.destroy', confirming.id), {
            onFinish: () => {
                setProcessing(false);
                setConfirming(null);
            },
        });
    };

    const columns = [
        {
            key: 'title',
            header: 'Ressource',
            render: (resource) => (
                <div className="flex items-center gap-3">
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
                        <BookOpen className="h-4 w-4" />
                    </div>
                    <div className="min-w-0">
                        <Link
                            href={route('platform.exam-bank.show', resource.id)}
                            className="block truncate font-medium text-slate-900 hover:text-indigo-600"
                        >
                            {resource.title}
                        </Link>
                        <p className="text-xs text-slate-500">{resource.type_label}</p>
                    </div>
                </div>
            ),
        },
        {
            key: 'classification',
            header: 'Niveau / Matière',
            render: (resource) => (
                <div className="flex flex-wrap gap-1">
                    {resource.level && <Badge tone="blue">{resource.level}</Badge>}
                    {resource.subject && <Badge tone="slate">{resource.subject}</Badge>}
                    {!resource.level && !resource.subject && <span className="text-slate-400">—</span>}
                </div>
            ),
        },
        {
            key: 'difficulty',
            header: 'Difficulté',
            className: 'hidden lg:table-cell',
            render: (resource) => (
                <Badge tone={difficultyTones[resource.difficulty] ?? 'slate'}>{resource.difficulty_label}</Badge>
            ),
        },
        {
            key: 'status',
            header: 'Statut',
            render: (resource) => (
                <div className="flex flex-col gap-1">
                    <Select
                        className="min-w-[130px]"
                        value={resource.status}
                        onChange={(event) => changeStatus(resource, event.target.value)}
                    >
                        {statuses.map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </Select>
                    <Badge tone={statusTones[resource.status] ?? 'slate'} className="w-fit">
                        {resource.status_label}
                    </Badge>
                </div>
            ),
        },
        {
            key: 'files',
            header: 'Fichiers',
            className: 'hidden lg:table-cell',
            render: (resource) => (
                <div className="flex items-center gap-2 text-slate-600">
                    <span className="inline-flex items-center gap-1 text-xs">
                        <FileDown className="h-3.5 w-3.5" /> {resource.attachments_count}
                    </span>
                    {resource.has_answer_key && <Badge tone="green">Corrigé</Badge>}
                    {resource.is_free ? (
                        <Badge tone="blue">Gratuit</Badge>
                    ) : (
                        <Badge tone="amber">Payant</Badge>
                    )}
                </div>
            ),
        },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-40 text-right',
            render: (resource) => (
                <div className="flex items-center justify-end gap-1">
                    <Link href={route('platform.exam-bank.show', resource.id)}>
                        <Button variant="ghost" size="sm" aria-label="Voir">
                            <Eye className="h-4 w-4" />
                        </Button>
                    </Link>
                    <Link href={route('platform.exam-bank.edit', resource.id)}>
                        <Button variant="ghost" size="sm" aria-label="Modifier">
                            <Pencil className="h-4 w-4" />
                        </Button>
                    </Link>
                    <Button
                        variant="ghost"
                        size="sm"
                        aria-label="Supprimer"
                        className="text-red-600 hover:bg-red-50"
                        onClick={() => askDelete(resource)}
                    >
                        <Trash2 className="h-4 w-4" />
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AuthenticatedLayout title="Coriyase — Platform">
            <Head title="Banque d'épreuves" />

            <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-600 text-white shadow-lg">
                <div className="absolute -right-10 -top-10 h-36 w-36 rounded-full bg-white/10" />
                <div className="relative p-6">
                    <div className="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-3 flex items-center gap-3">
                                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                                    <BookMarked className="h-5 w-5" />
                                </div>
                                <span className="text-sm font-medium text-white/80">Administration Coriyase</span>
                            </div>
                            <h1 className="text-2xl font-bold sm:text-3xl">Banque d'épreuves</h1>
                            <p className="mt-2 max-w-2xl text-sm leading-6 text-white/75 sm:text-base">
                                Bibliothèque centralisée d'épreuves, exercices et ressources classés par niveau et matière.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Link href={route('platform.exam-bank.references')}>
                                <Button variant="secondary" className="gap-2">
                                    <Settings2 className="h-4 w-4" />
                                    Référentiels
                                </Button>
                            </Link>
                            <Link href={route('platform.exam-bank.create')}>
                                <Button variant="primary" className="gap-2 bg-white text-indigo-700 hover:bg-white/90" style={{ backgroundColor: '#fff', color: '#4338ca' }}>
                                    <Plus className="h-4 w-4" />
                                    Nouvelle ressource
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div className="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard icon={Layers} label="Total" value={stats.total} description="Ressources" className="from-indigo-500 to-violet-600" />
                <StatCard icon={CheckCircle2} label="Publiées" value={stats.published} description="Visibles des établissements" className="from-emerald-500 to-teal-600" />
                <StatCard icon={Pencil} label="Brouillons" value={stats.draft} description="En préparation" className="from-slate-500 to-slate-700" />
                <StatCard icon={Archive} label="Archivées" value={stats.archived} description="Retirées de la banque" className="from-rose-500 to-red-600" />
            </div>

            <Card className="mt-6 overflow-hidden">
                <Card.Header
                    title="Ressources de la banque"
                    description={`${resources.total} ressource(s)`}
                    actions={<Filter className="hidden h-4 w-4 text-slate-400 sm:block" />}
                />

                <Card.Body>
                    <form
                        className="mb-6 rounded-xl border border-slate-200 bg-slate-50/70 p-4"
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

                        <div className="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
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
                            <Select value={filters.status || ''} onChange={(e) => updateFilters({ status: e.target.value })}>
                                <option value="">Tous les statuts</option>
                                {statuses.map((status) => (
                                    <option key={status.value} value={status.value}>{status.label}</option>
                                ))}
                            </Select>
                        </div>

                        <div className="mt-3 flex flex-wrap items-center justify-between gap-2">
                            <p className="text-sm text-slate-500">
                                <span className="font-semibold text-slate-700">{resources.data.length}</span> résultat(s) sur cette page
                            </p>
                            <div className="flex items-center gap-2">
                                <Button type="submit" size="sm" className="gap-1.5">
                                    <Search className="h-4 w-4" /> Rechercher
                                </Button>
                                {hasFilters && (
                                    <button
                                        type="button"
                                        onClick={() => router.get(route('platform.exam-bank.index'))}
                                        className="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700"
                                    >
                                        <RotateCcw className="h-4 w-4" /> Réinitialiser
                                    </button>
                                )}
                            </div>
                        </div>
                    </form>

                    <Table
                        columns={columns}
                        rows={resources.data}
                        emptyState={
                            <EmptyState
                                icon={BookMarked}
                                title="Aucune ressource"
                                description="Ajoutez une première épreuve ou ajustez vos filtres."
                                action={
                                    <Link href={route('platform.exam-bank.create')}>
                                        <Button className="gap-2">
                                            <Plus className="h-4 w-4" /> Nouvelle ressource
                                        </Button>
                                    </Link>
                                }
                            />
                        }
                    />

                    <Pagination meta={resources} />
                </Card.Body>
            </Card>

            <ConfirmDialog
                show={confirming !== null}
                onClose={() => setConfirming(null)}
                onConfirm={confirmDelete}
                title={confirming ? `Supprimer « ${confirming.title} » ?` : 'Supprimer'}
                description="La ressource et ses fichiers seront définitivement supprimés."
                confirmLabel="Supprimer"
                variant="danger"
                loading={processing}
            />
        </AuthenticatedLayout>
    );
}
