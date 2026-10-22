import { useState } from 'react';

import { Head, Link, router, useForm } from '@inertiajs/react';

import {
    BarChart3,
    CalendarDays,
    CheckCircle2,
    ClipboardCheck,
    Clock3,
    Edit3,
    Filter,
    LineChart,
    Plus,
    Search,
    ShieldCheck,
    UserCheck,
    UserRound,
    UserX,
    XCircle,
    ArrowRight,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import Pagination from '@/Components/UI/Pagination';
import Select from '@/Components/UI/Select';
import Tabs from '@/Components/UI/Tabs';
import Textarea from '@/Components/UI/Textarea';
import EvolutionChart from '@/Components/Attendance/EvolutionChart';

const statusLabels = {
    present: 'Présent',
    absent: 'Absent',
    late: 'Retard',
    permission: 'Permission',
};

const statusTones = {
    present: 'green',
    absent: 'red',
    late: 'amber',
    permission: 'blue',
};

const justificationLabels = {
    pending: 'En attente',
    approved: 'Justifiée',
    rejected: 'Refusée',
};

const validationLabels = {
    pending: 'À valider',
    validated: 'Validé',
};

export default function AttendanceIndex({
    records,
    classes,
    filters,
    stats,
    routePrefix,
    evolution,
    initialTab = 'liste',
    canValidate = false,
}) {
    const [editing, setEditing] = useState(null);
    const [activeTab, setActiveTab] = useState(initialTab);
    const [selected, setSelected] = useState([]);
    const [chartFilters, setChartFilters] = useState({
        granularity: evolution?.granularity ?? 'day',
        start: evolution?.granularity === 'custom' ? evolution.start : '',
        end: evolution?.granularity === 'custom' ? evolution.end : '',
    });

    const editForm = useForm({
        status: 'absent',
        delay_minutes: '',
        reason: '',
        note: '',
    });

    function applyChartFilters(event) {
        event.preventDefault();

        const params = {
            ...filters,
            tab: 'evolution',
            granularity: chartFilters.granularity,
        };

        if (chartFilters.granularity === 'custom') {
            params.start = chartFilters.start || undefined;
            params.end = chartFilters.end || undefined;
        } else {
            delete params.start;
            delete params.end;
        }

        Object.keys(params).forEach((key) => {
            if (params[key] === '' || params[key] == null) {
                delete params[key];
            }
        });

        router.get(route(`${routePrefix}.attendance.index`), params, {
            only: ['evolution'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function applyFilters(event) {
        event.preventDefault();

        const formData = new FormData(event.currentTarget);
        const params = Object.fromEntries(formData.entries());

        Object.keys(params).forEach((key) => {
            if (!params[key]) {
                delete params[key];
            }
        });

        router.get(
            route(`${routePrefix}.attendance.index`),
            params,
            {
                preserveState: true,
                replace: true,
            }
        );
    }

    function openEdit(record) {
        editForm.setData({
            status: record.status,
            delay_minutes: record.delay_minutes ?? '',
            reason: record.reason ?? '',
            note: record.note ?? '',
        });

        editForm.clearErrors();
        setEditing(record);
    }

    function saveEdit(event) {
        event.preventDefault();

        editForm.put(
            route(`${routePrefix}.attendance.update`, editing.id),
            {
                preserveScroll: true,
                onSuccess: () => setEditing(null),
            }
        );
    }

    function review(record, justification, status) {
        router.post(
            route(`${routePrefix}.attendance.justify`, record.id),
            {
                justification_id: justification?.id,
                status,
            },
            {
                preserveScroll: true,
            }
        );
    }

    const selectableIds = records.data
        .filter((record) => record.can_validate)
        .map((record) => record.id);

    function toggleSelected(id) {
        setSelected((current) =>
            current.includes(id)
                ? current.filter((itemId) => itemId !== id)
                : [...current, id]
        );
    }

    function toggleSelectAll() {
        setSelected((current) =>
            current.length === selectableIds.length ? [] : selectableIds
        );
    }

    function validateRecords(recordIds) {
        if (recordIds.length === 0) {
            return;
        }

        router.post(
            route(`${routePrefix}.attendance.validate`),
            { record_ids: recordIds },
            {
                preserveScroll: true,
                onFinish: () => setSelected([]),
            }
        );
    }

    const cards = [
        {
            label: "Assiduité (ce mois)",
            value: stats.present_month,
            icon: UserCheck,
            cardClass:
                'bg-gradient-to-br from-emerald-500 to-teal-600',
        },
        {
            label: "Retards ce mois",
            value: stats.late_month,
            icon: Clock3,
            cardClass:
                'bg-gradient-to-br from-amber-400 to-orange-500',
        },
        {
            label: "Absences ce mois",
            value: stats.absent_month,
            icon: UserX,
            cardClass:
                'bg-gradient-to-br from-rose-500 to-red-600',
        },
        {
            label: "Permissions ce mois",
            value: stats.permission_month,
            icon: ShieldCheck,
            cardClass:
                'bg-gradient-to-br from-blue-500 to-indigo-600',
        },
        ...(canValidate
            ? [
                  {
                      label: 'En attente de validation',
                      value: stats.pending_validation,
                      icon: ClipboardCheck,
                      cardClass:
                          'bg-gradient-to-br from-slate-500 to-slate-700',
                  },
              ]
            : []),
    ];

    return (
        <AuthenticatedLayout title="Absences & retards">
            <Head title="Absences & retards" />

            <div className="space-y-6">

                {/* =====================================================
                    HEADER
                ====================================================== */}
                <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                    <div>
                        <div className="flex items-center gap-2">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                <ClipboardCheck className="h-5 w-5" />
                            </div>

                            <div>
                                <p className="text-xs font-bold uppercase tracking-wider text-emerald-600">
                                    Suivi de l’assiduité
                                </p>

                                <h1 className="mt-0.5 text-2xl font-bold tracking-tight text-slate-950">
                                    Absences & retards
                                </h1>
                            </div>
                        </div>

                        <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-500">
                            Consultez, filtrez et gérez les relevés d’assiduité
                            des élèves de votre établissement.
                        </p>
                    </div>

                    <Link
                        href={route(
                            `${routePrefix}.attendance.create`
                        )}
                    >
                        <Button className="w-full sm:w-auto">
                            <Plus className="h-4 w-4" />
                            Enregistrer l’assiduité
                        </Button>
                    </Link>
                </div>

                {/* =====================================================
                    KPI
                ====================================================== */}
                <div
                    className={`grid grid-cols-2 gap-4 ${
                        canValidate ? 'lg:grid-cols-5' : 'lg:grid-cols-4'
                    }`}
                >
                    {cards.map((card) => {
                        const Icon = card.icon;

                        return (
                            <Card
                                key={card.label}
                                className={`group relative overflow-hidden border-0 p-5 text-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg ${card.cardClass}`}
                            >
                                <div className="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10" />

                                <div className="absolute -bottom-10 -left-6 h-28 w-28 rounded-full bg-white/5" />

                                <div className="relative">
                                    <div className="mb-4 flex items-center justify-between">
                                        <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 shadow-sm backdrop-blur-sm">
                                            <Icon className="h-6 w-6 text-white" />
                                        </div>

                                        <ArrowRight className="h-4 w-4 text-white/50 transition-transform group-hover:translate-x-1 group-hover:text-white/80" />
                                    </div>

                                    <p className="text-3xl font-bold tracking-tight">
                                        {card.value}
                                    </p>

                                    <p className="mt-1 text-sm font-medium text-white/80">
                                        {card.label}
                                    </p>
                                </div>
                            </Card>
                        );
                    })}
                </div>

                {/* =====================================================
                    MAIN CARD
                ====================================================== */}
                <Card className="overflow-hidden">

                    <div className="px-4 pt-3 sm:px-5">
                        <Tabs
                            active={activeTab}
                            onChange={setActiveTab}
                            tabs={[
                                {
                                    key: 'evolution',
                                    label: 'Évolution',
                                },
                                {
                                    key: 'liste',
                                    label: 'Liste des relevés',
                                },
                            ]}
                        />
                    </div>

                    {activeTab === 'evolution' ? (
                        <Card.Body>
                            <form
                                onSubmit={applyChartFilters}
                                className="mb-5 space-y-4"
                            >
                                <div className="flex items-center gap-2">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                        <LineChart className="h-4 w-4" />
                                    </div>

                                    <div>
                                        <p className="text-sm font-semibold text-slate-800">
                                            Évolution des retards & absences
                                        </p>

                                        <p className="text-xs text-slate-500">
                                            Choisissez une période d’analyse.
                                        </p>
                                    </div>
                                </div>

                                <div className="grid gap-3 sm:grid-cols-3">
                                    <Select
                                        value={chartFilters.granularity}
                                        onChange={(event) =>
                                            setChartFilters((current) => ({
                                                ...current,
                                                granularity:
                                                    event.target.value,
                                            }))
                                        }
                                    >
                                        <option value="day">
                                            Par jour (14 derniers jours)
                                        </option>
                                        <option value="week">
                                            Par semaine (12 dernières)
                                        </option>
                                        <option value="month">
                                            Par mois (12 derniers)
                                        </option>
                                        <option value="custom">
                                            Période personnalisée
                                        </option>
                                    </Select>

                                    {chartFilters.granularity === 'custom' && (
                                        <>
                                            <Input
                                                type="date"
                                                value={chartFilters.start}
                                                onChange={(event) =>
                                                    setChartFilters(
                                                        (current) => ({
                                                            ...current,
                                                            start: event.target
                                                                .value,
                                                        })
                                                    )
                                                }
                                                aria-label="Date de début"
                                            />

                                            <Input
                                                type="date"
                                                value={chartFilters.end}
                                                onChange={(event) =>
                                                    setChartFilters(
                                                        (current) => ({
                                                            ...current,
                                                            end: event.target
                                                                .value,
                                                        })
                                                    )
                                                }
                                                aria-label="Date de fin"
                                            />
                                        </>
                                    )}
                                </div>

                                <div className="flex justify-end">
                                    <Button type="submit" variant="secondary">
                                        <BarChart3 className="h-4 w-4" />
                                        Analyser
                                    </Button>
                                </div>
                            </form>

                            <EvolutionChart evolution={evolution} />
                        </Card.Body>
                    ) : (
                        <Card.Body className="p-0">

                        {/* =================================================
                            FILTER TOOLBAR
                        ================================================== */}
                        <div className="border-b border-slate-100 bg-slate-50/70 p-4 sm:p-5">
                            <form
                                onSubmit={applyFilters}
                                className="space-y-4"
                            >
                                <div className="flex items-center gap-2">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                        <Filter className="h-4 w-4" />
                                    </div>

                                    <div>
                                        <p className="text-sm font-semibold text-slate-800">
                                            Filtrer les relevés
                                        </p>

                                        <p className="text-xs text-slate-500">
                                            Affinez la recherche par élève,
                                            classe, période ou statut.
                                        </p>
                                    </div>
                                </div>

                                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                                    <Input
                                        name="student"
                                        defaultValue={
                                            filters.student ?? ''
                                        }
                                        placeholder="Élève ou matricule"
                                    />

                                    <Select
                                        name="class_id"
                                        defaultValue={
                                            filters.class_id ?? ''
                                        }
                                    >
                                        <option value="">
                                            Toutes les classes
                                        </option>

                                        {classes.map((classRoom) => (
                                            <option
                                                key={classRoom.id}
                                                value={classRoom.id}
                                            >
                                                {classRoom.name}
                                            </option>
                                        ))}
                                    </Select>

                                    <Input
                                        name="date_from"
                                        type="date"
                                        defaultValue={
                                            filters.date_from ?? ''
                                        }
                                        aria-label="Date de début"
                                    />

                                    <Input
                                        name="date_to"
                                        type="date"
                                        defaultValue={
                                            filters.date_to ?? ''
                                        }
                                        aria-label="Date de fin"
                                    />

                                    <Select
                                        name="status"
                                        defaultValue={
                                            filters.status ?? ''
                                        }
                                    >
                                        <option value="">
                                            Tous les statuts
                                        </option>

                                        <option value="absent">
                                            Absent
                                        </option>

                                        <option value="late">
                                            Retard
                                        </option>

                                        <option value="present">
                                            Présent
                                        </option>

                                        <option value="permission">
                                            Permission
                                        </option>
                                    </Select>

                                    <Select
                                        name="justification"
                                        defaultValue={
                                            filters.justification ?? ''
                                        }
                                    >
                                        <option value="">
                                            Toutes justifications
                                        </option>

                                        <option value="pending">
                                            En attente
                                        </option>

                                        <option value="approved">
                                            Justifiée
                                        </option>

                                        <option value="rejected">
                                            Refusée
                                        </option>

                                        <option value="unjustified">
                                            Non justifiée
                                        </option>
                                    </Select>

                                    {canValidate && (
                                        <Select
                                            name="validation"
                                            defaultValue={
                                                filters.validation ?? ''
                                            }
                                        >
                                            <option value="">
                                                Toutes validations
                                            </option>

                                            <option value="pending">
                                                En attente de validation
                                            </option>

                                            <option value="validated">
                                                Validé
                                            </option>
                                        </Select>
                                    )}
                                </div>

                                <div className="flex flex-col gap-2 sm:flex-row sm:justify-end">
                                    <Button
                                        type="submit"
                                        variant="secondary"
                                    >
                                        <Search className="h-4 w-4" />
                                        Rechercher
                                    </Button>
                                </div>
                            </form>
                        </div>

                        {/* =================================================
                            BULK VALIDATION BAR
                        ================================================== */}
                        {canValidate && records.data.length > 0 && (
                            <div className="flex flex-col gap-3 border-b border-slate-100 bg-emerald-50/60 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                                <p className="text-xs font-semibold text-emerald-800">
                                    {selected.length === 0
                                        ? 'Sélectionnez les relevés à valider — les parents des élèves absents, en retard ou en permission seront notifiés.'
                                        : `${selected.length} relevé(s) sélectionné(s)`}
                                </p>

                                <Button
                                    type="button"
                                    disabled={selected.length === 0}
                                    onClick={() =>
                                        validateRecords(selected)
                                    }
                                >
                                    <ShieldCheck className="h-4 w-4" />
                                    Valider la sélection
                                </Button>
                            </div>
                        )}

                        {/* =================================================
                            EMPTY STATE
                        ================================================== */}
                        {records.data.length === 0 ? (
                            <div className="px-6 py-16 text-center">
                                <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                    <ClipboardCheck className="h-8 w-8" />
                                </div>

                                <p className="mt-4 text-base font-semibold text-slate-800">
                                    Aucun relevé trouvé
                                </p>

                                <p className="mx-auto mt-1 max-w-md text-sm text-slate-500">
                                    Aucun relevé d’assiduité ne correspond
                                    aux critères sélectionnés.
                                </p>

                                <Link
                                    className="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 hover:text-emerald-800"
                                    href={route(
                                        `${routePrefix}.attendance.create`
                                    )}
                                >
                                    Enregistrer un relevé
                                    <ArrowRight className="h-4 w-4" />
                                </Link>
                            </div>
                        ) : (
                            <>
                                {/* =========================================
                                    DESKTOP TABLE
                                ========================================== */}
                                <div className="hidden overflow-x-auto lg:block">
                                    <table className="min-w-full text-sm">
                                        <thead className="border-b border-slate-100 bg-slate-50/50">
                                            <tr className="text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                                {canValidate && (
                                                    <th className="w-10 px-4 py-4">
                                                        <input
                                                            type="checkbox"
                                                            checked={
                                                                selectableIds.length >
                                                                    0 &&
                                                                selected.length ===
                                                                    selectableIds.length
                                                            }
                                                            onChange={
                                                                toggleSelectAll
                                                            }
                                                            className="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                                            aria-label="Sélectionner tous les relevés en attente"
                                                        />
                                                    </th>
                                                )}

                                                <th className="px-6 py-4">
                                                    Date
                                                </th>

                                                <th className="px-4 py-4">
                                                    Élève
                                                </th>

                                                <th className="px-4 py-4">
                                                    Classe
                                                </th>

                                                <th className="px-4 py-4">
                                                    Statut
                                                </th>

                                                <th className="px-4 py-4">
                                                    Motif / retard
                                                </th>

                                                <th className="px-4 py-4">
                                                    Validation
                                                </th>

                                                <th className="px-4 py-4">
                                                    Justification
                                                </th>

                                                <th className="px-4 py-4">
                                                    Ajouté par
                                                </th>

                                                <th className="px-4 py-4">
                                                    Ajouté le
                                                </th>

                                                <th className="px-6 py-4 text-right">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody className="divide-y divide-slate-100">
                                            {records.data.map((record) => {
                                                const latestJustification =
                                                    record.justifications.at(-1);

                                                return (
                                                    <tr
                                                        key={record.id}
                                                        className="group transition-colors hover:bg-slate-50/70"
                                                    >
                                                        {canValidate && (
                                                            <td className="px-4 py-4">
                                                                <input
                                                                    type="checkbox"
                                                                    checked={selected.includes(
                                                                        record.id
                                                                    )}
                                                                    onChange={() =>
                                                                        toggleSelected(
                                                                            record.id
                                                                        )
                                                                    }
                                                                    disabled={
                                                                        !record.can_validate
                                                                    }
                                                                    className="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 disabled:opacity-40"
                                                                    aria-label={`Sélectionner le relevé de ${record.student.name}`}
                                                                />
                                                            </td>
                                                        )}

                                                        <td className="whitespace-nowrap px-6 py-4">
                                                            <div className="flex items-center gap-2">
                                                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                                                    <CalendarDays className="h-4 w-4" />
                                                                </div>

                                                                <span className="font-medium text-slate-700">
                                                                    {
                                                                        record.attendance_date
                                                                    }
                                                                </span>
                                                            </div>
                                                        </td>

                                                        <td className="px-4 py-4">
                                                            <Link
                                                                className="group/student"
                                                                href={route(
                                                                    `${routePrefix}.attendance.students.show`,
                                                                    record.student.id
                                                                )}
                                                            >
                                                                <div className="flex items-center gap-3">
                                                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">
                                                                        {record.student.name
                                                                            ?.charAt(
                                                                                0
                                                                            )
                                                                            ?.toUpperCase()}
                                                                    </div>

                                                                    <div className="min-w-0">
                                                                        <p className="font-semibold text-slate-800 group-hover/student:text-emerald-700">
                                                                            {
                                                                                record.student
                                                                                    .name
                                                                            }
                                                                        </p>

                                                                        <p className="mt-0.5 text-xs text-slate-500">
                                                                            {record
                                                                                .student
                                                                                .matricule ||
                                                                                'Sans matricule'}
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </Link>
                                                        </td>

                                                        <td className="px-4 py-4">
                                                            <span className="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-600">
                                                                {record.class_name ||
                                                                    '—'}
                                                            </span>
                                                        </td>

                                                        <td className="px-4 py-4">
                                                            <Badge
                                                                tone={
                                                                    statusTones[
                                                                        record
                                                                            .status
                                                                    ]
                                                                }
                                                            >
                                                                {statusLabels[
                                                                    record
                                                                        .status
                                                                ]}
                                                            </Badge>
                                                        </td>

                                                        <td className="px-4 py-4">
                                                            {record.status ===
                                                            'late' ? (
                                                                <div className="flex items-center gap-2">
                                                                    <Clock3 className="h-4 w-4 text-amber-500" />

                                                                    <span className="font-medium text-slate-700">
                                                                        {
                                                                            record.delay_minutes
                                                                        }{' '}
                                                                        min
                                                                    </span>
                                                                </div>
                                                            ) : (
                                                                <span className="text-slate-600">
                                                                    {record.reason ||
                                                                        record.note ||
                                                                        '—'}
                                                                </span>
                                                            )}
                                                        </td>

                                                        <td className="px-4 py-4">
                                                            <div>
                                                                <Badge
                                                                    tone={
                                                                        record.validation_status ===
                                                                        'validated'
                                                                            ? 'green'
                                                                            : 'amber'
                                                                    }
                                                                >
                                                                    {validationLabels[
                                                                        record
                                                                            .validation_status
                                                                    ] ??
                                                                        '—'}
                                                                </Badge>

                                                                {record.validator_name && (
                                                                    <p className="mt-1 max-w-48 truncate text-xs text-slate-500">
                                                                        par{' '}
                                                                        {
                                                                            record.validator_name
                                                                        }
                                                                    </p>
                                                                )}
                                                            </div>
                                                        </td>

                                                        <td className="px-4 py-4">
                                                            {latestJustification ? (
                                                                <div>
                                                                    <Badge
                                                                        tone={
                                                                            latestJustification.status ===
                                                                            'approved'
                                                                                ? 'green'
                                                                                : latestJustification.status ===
                                                                                  'rejected'
                                                                                ? 'red'
                                                                                : 'amber'
                                                                        }
                                                                    >
                                                                        {
                                                                            justificationLabels[
                                                                                latestJustification
                                                                                    .status
                                                                            ]
                                                                        }
                                                                    </Badge>

                                                                    {latestJustification.reason && (
                                                                        <p className="mt-1 max-w-48 truncate text-xs text-slate-500">
                                                                            {
                                                                                latestJustification.reason
                                                                            }
                                                                        </p>
                                                                    )}
                                                                </div>
                                                            ) : record.justified_at ? (
                                                                <Badge tone="green">
                                                                    Justifiée
                                                                </Badge>
                                                            ) : record.status ===
                                                              'absent' ? (
                                                                <Badge tone="red">
                                                                    Non justifiée
                                                                </Badge>
                                                            ) : (
                                                                <span className="text-slate-400">
                                                                    —
                                                                </span>
                                                            )}
                                                        </td>

                                                        <td className="px-4 py-4">
                                                            <div className="flex items-center gap-2">
                                                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                                                    <UserRound className="h-4 w-4" />
                                                                </div>

                                                                <span className="font-medium text-slate-700">
                                                                    {record.recorder_name ||
                                                                        '—'}
                                                                </span>
                                                            </div>
                                                        </td>

                                                        <td className="whitespace-nowrap px-4 py-4 text-slate-600">
                                                            {record.recorded_at ||
                                                                '—'}
                                                        </td>

                                                        <td className="px-6 py-4">
                                                                <div className="flex justify-end gap-2">
                                                                    {record.can_validate && (
                                                                        <button
                                                                            type="button"
                                                                            onClick={() =>
                                                                                validateRecords([
                                                                                    record.id,
                                                                                ])
                                                                            }
                                                                            className="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                                                        >
                                                                            <ShieldCheck className="h-3.5 w-3.5" />
                                                                            Valider
                                                                        </button>
                                                                    )}

                                                                    {record.can_update && (
                                                                    <button
                                                                        type="button"
                                                                        onClick={() =>
                                                                            openEdit(
                                                                                record
                                                                            )
                                                                        }
                                                                        className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700"
                                                                    >
                                                                        <Edit3 className="h-3.5 w-3.5" />
                                                                        Modifier
                                                                    </button>
                                                                )}

                                                                {record.can_justify &&
                                                                    record.status ===
                                                                        'absent' && (
                                                                        <>
                                                                            {latestJustification?.status ===
                                                                            'pending' ? (
                                                                                <>
                                                                                    <button
                                                                                        type="button"
                                                                                        onClick={() =>
                                                                                            review(
                                                                                                record,
                                                                                                latestJustification,
                                                                                                'approved'
                                                                                            )
                                                                                        }
                                                                                        className="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                                                                    >
                                                                                        <CheckCircle2 className="h-3.5 w-3.5" />
                                                                                        Justifier
                                                                                    </button>

                                                                                    <button
                                                                                        type="button"
                                                                                        onClick={() =>
                                                                                            review(
                                                                                                record,
                                                                                                latestJustification,
                                                                                                'rejected'
                                                                                            )
                                                                                        }
                                                                                        className="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-100"
                                                                                    >
                                                                                        <XCircle className="h-3.5 w-3.5" />
                                                                                        Refuser
                                                                                    </button>
                                                                                </>
                                                                            ) : (
                                                                                <button
                                                                                    type="button"
                                                                                    onClick={() =>
                                                                                        review(
                                                                                            record,
                                                                                            null,
                                                                                            'approved'
                                                                                        )
                                                                                    }
                                                                                    className="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                                                                >
                                                                                    <CheckCircle2 className="h-3.5 w-3.5" />
                                                                                    Justifier
                                                                                </button>
                                                                            )}
                                                                        </>
                                                                    )}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>

                                {/* =========================================
                                    MOBILE / TABLET CARDS
                                ========================================== */}
                                <div className="divide-y divide-slate-100 lg:hidden">
                                    {records.data.map((record) => {
                                        const latestJustification =
                                            record.justifications.at(-1);

                                        return (
                                            <div
                                                key={record.id}
                                                className="p-4 sm:p-5"
                                            >
                                                <div className="flex items-start justify-between gap-3">
                                                    <div className="flex min-w-0 items-center gap-3">
                                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-sm font-bold text-emerald-700">
                                                            {record.student.name
                                                                ?.charAt(0)
                                                                ?.toUpperCase()}
                                                        </div>

                                                        <div className="min-w-0">
                                                            <Link
                                                                href={route(
                                                                    `${routePrefix}.attendance.students.show`,
                                                                    record.student.id
                                                                )}
                                                                className="block truncate text-sm font-bold text-slate-800 hover:text-emerald-700"
                                                            >
                                                                {
                                                                    record
                                                                        .student
                                                                        .name
                                                                }
                                                            </Link>

                                                            <p className="mt-0.5 text-xs text-slate-500">
                                                                {record.class_name ||
                                                                    'Classe inconnue'}
                                                                {' · '}
                                                                {record.student
                                                                    .matricule ||
                                                                    'Sans matricule'}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <Badge
                                                        tone={
                                                            statusTones[
                                                                record.status
                                                            ]
                                                        }
                                                    >
                                                        {
                                                            statusLabels[
                                                                record.status
                                                            ]
                                                        }
                                                    </Badge>
                                                </div>

                                                <div className="mt-4 grid grid-cols-2 gap-3">
                                                    <InfoItem
                                                        icon={CalendarDays}
                                                        label="Date"
                                                        value={
                                                            record.attendance_date
                                                        }
                                                    />

                                                    <InfoItem
                                                        icon={Clock3}
                                                        label="Retard"
                                                        value={
                                                            record.status ===
                                                            'late'
                                                                ? `${record.delay_minutes} min`
                                                                : '—'
                                                        }
                                                    />
                                                </div>

                                                <div className="mt-3 grid grid-cols-2 gap-3">
                                                    <InfoItem
                                                        icon={UserRound}
                                                        label="Ajouté par"
                                                        value={
                                                            record.recorder_name ||
                                                            '—'
                                                        }
                                                    />

                                                    <InfoItem
                                                        icon={Clock3}
                                                        label="Ajouté le"
                                                        value={
                                                            record.recorded_at ||
                                                            '—'
                                                        }
                                                    />
                                                </div>

                                                {record.reason && (
                                                    <div className="mt-3 rounded-xl bg-slate-50 p-3">
                                                        <p className="text-[11px] font-bold uppercase tracking-wide text-slate-400">
                                                            Motif
                                                        </p>

                                                        <p className="mt-1 text-sm text-slate-600">
                                                            {record.reason}
                                                        </p>
                                                    </div>
                                                )}

                                                <div className="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-4">
                                                    <div className="flex flex-wrap gap-2">
                                                        <Badge
                                                            tone={
                                                                record.validation_status ===
                                                                'validated'
                                                                    ? 'green'
                                                                    : 'amber'
                                                            }
                                                        >
                                                            {validationLabels[
                                                                record
                                                                    .validation_status
                                                            ] ?? '—'}
                                                        </Badge>

                                                        {latestJustification ? (
                                                            <Badge
                                                                tone={
                                                                    latestJustification.status ===
                                                                    'approved'
                                                                        ? 'green'
                                                                        : latestJustification.status ===
                                                                          'rejected'
                                                                        ? 'red'
                                                                        : 'amber'
                                                                }
                                                            >
                                                                {
                                                                    justificationLabels[
                                                                        latestJustification
                                                                            .status
                                                                    ]
                                                                }
                                                            </Badge>
                                                        ) : record.status ===
                                                          'absent' ? (
                                                            <Badge tone="red">
                                                                Non justifiée
                                                            </Badge>
                                                        ) : null}
                                                    </div>

                                                    <div className="flex flex-wrap gap-2">
                                                        {record.can_validate && (
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    validateRecords([
                                                                        record.id,
                                                                    ])
                                                                }
                                                                className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                                                            >
                                                                <ShieldCheck className="h-3.5 w-3.5" />
                                                                Valider
                                                            </button>
                                                        )}

                                                        {record.can_update && (
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    openEdit(
                                                                        record
                                                                    )
                                                                }
                                                                className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                                                            >
                                                                <Edit3 className="h-3.5 w-3.5" />
                                                                Modifier
                                                            </button>
                                                        )}

                                                        {record.can_justify &&
                                                            record.status ===
                                                                'absent' &&
                                                            latestJustification?.status ===
                                                                'pending' && (
                                                                <>
                                                                    <button
                                                                        type="button"
                                                                        onClick={() =>
                                                                            review(
                                                                                record,
                                                                                latestJustification,
                                                                                'approved'
                                                                            )
                                                                        }
                                                                        className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                                                                    >
                                                                        <CheckCircle2 className="h-3.5 w-3.5" />
                                                                        Justifier
                                                                    </button>

                                                                    <button
                                                                        type="button"
                                                                        onClick={() =>
                                                                            review(
                                                                                record,
                                                                                latestJustification,
                                                                                'rejected'
                                                                            )
                                                                        }
                                                                        className="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700"
                                                                    >
                                                                        <XCircle className="h-3.5 w-3.5" />
                                                                        Refuser
                                                                    </button>
                                                                </>
                                                            )}
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </>
                        )}

                        {/* =================================================
                            PAGINATION
                        ================================================== */}
                        {records.data.length > 0 && (
                            <div className="border-t border-slate-100 px-5 py-4">
                                <Pagination meta={records} />
                            </div>
                        )}
                        </Card.Body>
                    )}
                </Card>
            </div>

            {/* =========================================================
                EDIT MODAL
            ========================================================== */}
            <Modal
                show={Boolean(editing)}
                onClose={() => setEditing(null)}
                title="Corriger le relevé"
                footer={
                    <>
                        <Button
                            variant="secondary"
                            onClick={() => setEditing(null)}
                        >
                            Annuler
                        </Button>

                        <Button
                            onClick={saveEdit}
                            loading={editForm.processing}
                        >
                            <CheckCircle2 className="h-4 w-4" />
                            Enregistrer
                        </Button>
                    </>
                }
            >
                <form
                    onSubmit={saveEdit}
                    className="space-y-5"
                >
                    <div className="rounded-xl bg-slate-50 p-4">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                <UserCheck className="h-5 w-5" />
                            </div>

                            <div>
                                <p className="text-sm font-bold text-slate-800">
                                    {editing?.student.name}
                                </p>

                                <p className="mt-0.5 text-xs text-slate-500">
                                    {editing?.attendance_date}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label className="mb-1.5 block text-sm font-semibold text-slate-700">
                            Statut
                        </label>

                        <Select
                            value={editForm.data.status}
                            onChange={(event) =>
                                editForm.setData(
                                    'status',
                                    event.target.value
                                )
                            }
                            error={editForm.errors.status}
                        >
                            <option value="present">
                                Présent
                            </option>

                            <option value="absent">
                                Absent
                            </option>

                            <option value="late">
                                Retard
                            </option>

                            <option value="permission">
                                Permission
                            </option>
                        </Select>
                    </div>

                    {editForm.data.status === 'late' && (
                        <label className="block text-sm font-semibold text-slate-700">
                            Minutes de retard

                            <Input
                                className="mt-1.5"
                                type="number"
                                min="0"
                                max="1440"
                                value={
                                    editForm.data.delay_minutes
                                }
                                onChange={(event) =>
                                    editForm.setData(
                                        'delay_minutes',
                                        event.target.value
                                    )
                                }
                                error={
                                    editForm.errors.delay_minutes
                                }
                            />
                        </label>
                    )}

                    {editForm.data.status === 'absent' && (
                        <label className="block text-sm font-semibold text-slate-700">
                            Motif

                            <Input
                                className="mt-1.5"
                                value={editForm.data.reason}
                                onChange={(event) =>
                                    editForm.setData(
                                        'reason',
                                        event.target.value
                                    )
                                }
                                error={editForm.errors.reason}
                            />

                            <span className="mt-1 block text-xs font-normal text-slate-400">
                                Facultatif — peut être complété lors de
                                la justification.
                            </span>
                        </label>
                    )}

                    {editForm.data.status === 'permission' && (
                        <label className="block text-sm font-semibold text-slate-700">
                            Motif de la permission

                            <Input
                                className="mt-1.5"
                                value={editForm.data.reason}
                                onChange={(event) =>
                                    editForm.setData(
                                        'reason',
                                        event.target.value
                                    )
                                }
                                error={editForm.errors.reason}
                                placeholder="Autorisation de sortie, RDV médical…"
                                maxLength={500}
                            />

                            <span className="mt-1 block text-xs font-normal text-sky-600">
                                Obligatoire — une permission est
                                considérée comme justifiée.
                            </span>
                        </label>
                    )}

                    <label className="block text-sm font-semibold text-slate-700">
                        Observation

                        <Textarea
                            className="mt-1.5"
                            value={editForm.data.note}
                            onChange={(event) =>
                                editForm.setData(
                                    'note',
                                    event.target.value
                                )
                            }
                            rows={3}
                            error={editForm.errors.note}
                        />
                    </label>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

function InfoItem({ icon: Icon, label, value }) {
    return (
        <div className="rounded-xl bg-slate-50 p-3">
            <div className="flex items-center gap-2 text-slate-400">
                <Icon className="h-3.5 w-3.5" />

                <span className="text-[10px] font-bold uppercase tracking-wide">
                    {label}
                </span>
            </div>

            <p className="mt-1 text-sm font-semibold text-slate-700">
                {value}
            </p>
        </div>
    );
}