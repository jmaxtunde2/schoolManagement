import { useMemo, useState } from 'react';
import { Head } from '@inertiajs/react';
import {
    BarChart3,
    Download,
    FileBadge,
    FileSpreadsheet,
    FileText,
    GraduationCap,
    Landmark,
    ListChecks,
    Printer,
    School,
    Users,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Button from '@/Components/UI/Button';
import EmptyState from '@/Components/UI/EmptyState';

const generalDocuments = [
    {
        key: 'students',
        icon: Users,
        tone: 'text-sky-600 bg-sky-50',
        title: 'Liste générale des élèves',
        description: 'Tous les élèves inscrits, triés par classe et par nom.',
        route: 'documents.students',
    },
    {
        key: 'classes',
        icon: School,
        tone: 'text-emerald-600 bg-emerald-50',
        title: 'Liste des classes',
        description: 'Effectif de chaque classe de l’établissement.',
        route: 'documents.classes',
    },
    {
        key: 'statistics',
        icon: BarChart3,
        tone: 'text-indigo-600 bg-indigo-50',
        title: 'Rapport statistique',
        description: 'Effectifs, moyennes par classe et par matière.',
        route: 'documents.statistics',
    },
];

function DocumentTile({ icon: Icon, tone, title, description, href }) {
    return (
        <div className="flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <span className={`inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ${tone}`}>
                <Icon className="h-5 w-5" />
            </span>
            <div className="min-w-0 flex-1">
                <p className="font-semibold text-slate-900">{title}</p>
                <p className="mt-0.5 text-sm text-slate-500">{description}</p>
            </div>
            <Button
                as="a"
                href={href}
                target="_blank"
                rel="noopener"
                variant="secondary"
                size="sm"
                className="shrink-0"
            >
                <Download className="h-4 w-4" />
                Générer
            </Button>
        </div>
    );
}

export default function DocumentsIndex({
    classes = [],
    students = [],
    canViewFinance = false,
    scope_note = null,
    routePrefix = 'admin',
}) {
    const [studentId, setStudentId] = useState('');
    const [classId, setClassId] = useState('');

    const selectedStudent = useMemo(
        () => students.find((student) => String(student.id) === String(studentId)) ?? null,
        [students, studentId],
    );

    const documents = useMemo(() => {
        const items = [...generalDocuments];
        if (canViewFinance) {
            items.push({
                key: 'finance',
                icon: Landmark,
                tone: 'text-amber-600 bg-amber-50',
                title: 'Rapport financier',
                description: 'Recettes, dépenses et impayés de l’exercice.',
                route: 'documents.finance',
            });
        }
        return items;
    }, [canViewFinance]);

    return (
        <AuthenticatedLayout title="Centre de documents">
            <Head title="Centre de documents" />

            <div className="space-y-6">
                <div>
                    <p className="text-xs font-bold uppercase tracking-wider text-primary">Documents</p>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                        Centre de documents
                    </h1>
                    <p className="mt-1 max-w-3xl text-sm text-slate-500">
                        Générez les documents officiels de l’établissement avec son en-tête et son identité visuelle.
                        Les documents s’ouvrent dans un nouvel onglet et peuvent être imprimés ou enregistrés.
                    </p>
                    {scope_note && <p className="mt-1 text-xs text-slate-400">{scope_note}</p>}
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <Card.Header
                            title={
                                <span className="flex items-center gap-2">
                                    <FileBadge className="h-4 w-4 text-primary" />
                                    Documents individuels
                                </span>
                            }
                            description="Certificat ou attestation de scolarité pour un élève."
                        />
                        <Card.Body className="space-y-4">
                            {students.length === 0 ? (
                                <EmptyState
                                    title="Aucun élève"
                                    description="Aucun élève actif dans votre périmètre."
                                />
                            ) : (
                                <>
                                    <div>
                                        <label className="mb-1 block text-sm font-medium text-slate-700">Élève</label>
                                        <select
                                            value={studentId}
                                            onChange={(event) => setStudentId(event.target.value)}
                                            className="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                        >
                                            <option value="">— Sélectionner un élève —</option>
                                            {students.map((student) => (
                                                <option key={student.id} value={student.id}>
                                                    {student.name}
                                                    {student.class_name ? ` · ${student.class_name}` : ''}
                                                    {student.matricule ? ` (${student.matricule})` : ''}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div className="flex flex-wrap gap-2">
                                        <Button
                                            as="a"
                                            href={
                                                selectedStudent
                                                    ? route(`${routePrefix}.documents.certificate`, selectedStudent.id)
                                                    : undefined
                                            }
                                            target="_blank"
                                            rel="noopener"
                                            variant="primary"
                                            size="sm"
                                            disabled={!selectedStudent}
                                            className={!selectedStudent ? 'pointer-events-none opacity-60' : ''}
                                        >
                                            <Printer className="h-4 w-4" />
                                            Certificat de scolarité
                                        </Button>
                                        <Button
                                            as="a"
                                            href={
                                                selectedStudent
                                                    ? route(`${routePrefix}.documents.attestation`, selectedStudent.id)
                                                    : undefined
                                            }
                                            target="_blank"
                                            rel="noopener"
                                            variant="secondary"
                                            size="sm"
                                            disabled={!selectedStudent}
                                            className={!selectedStudent ? 'pointer-events-none opacity-60' : ''}
                                        >
                                            <FileText className="h-4 w-4" />
                                            Attestation de scolarité
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Card.Body>
                    </Card>

                    <Card>
                        <Card.Header
                            title={
                                <span className="flex items-center gap-2">
                                    <ListChecks className="h-4 w-4 text-primary" />
                                    Documents de classe
                                </span>
                            }
                            description="Liste des élèves d’une classe."
                        />
                        <Card.Body className="space-y-4">
                            {classes.length === 0 ? (
                                <EmptyState title="Aucune classe" description="Aucune classe dans votre périmètre." />
                            ) : (
                                <>
                                    <div>
                                        <label className="mb-1 block text-sm font-medium text-slate-700">Classe</label>
                                        <select
                                            value={classId}
                                            onChange={(event) => setClassId(event.target.value)}
                                            className="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                        >
                                            <option value="">— Sélectionner une classe —</option>
                                            {classes.map((classRoom) => (
                                                <option key={classRoom.id} value={classRoom.id}>
                                                    {classRoom.name}
                                                    {classRoom.level ? ` · ${classRoom.level}` : ''}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <Button
                                        as="a"
                                        href={classId ? route(`${routePrefix}.documents.class-list`, classId) : undefined}
                                        target="_blank"
                                        rel="noopener"
                                        variant="primary"
                                        size="sm"
                                        disabled={!classId}
                                        className={!classId ? 'pointer-events-none opacity-60' : ''}
                                    >
                                        <Download className="h-4 w-4" />
                                        Liste des élèves
                                    </Button>
                                </>
                            )}
                        </Card.Body>
                    </Card>
                </div>

                <Card>
                    <Card.Header
                        title={
                            <span className="flex items-center gap-2">
                                <FileSpreadsheet className="h-4 w-4 text-primary" />
                                Documents généraux
                            </span>
                        }
                        description="Listes et rapports de l’établissement."
                    />
                    <Card.Body className="grid gap-3 sm:grid-cols-2">
                        {documents.map((document) => (
                            <DocumentTile
                                key={document.key}
                                icon={document.icon}
                                tone={document.tone}
                                title={document.title}
                                description={document.description}
                                href={route(`${routePrefix}.${document.route}`)}
                            />
                        ))}
                    </Card.Body>
                </Card>

                <Card>
                    <Card.Header
                        title={
                            <span className="flex items-center gap-2">
                                <GraduationCap className="h-4 w-4 text-primary" />
                                Bulletins & reçus
                            </span>
                        }
                        description="Ces documents disposent de leurs écrans dédiés."
                    />
                    <Card.Body className="flex flex-wrap gap-3">
                        <Button as="a" href={route(`${routePrefix}.report-cards.index`)} variant="secondary" size="sm">
                            <FileText className="h-4 w-4" />
                            Bulletins scolaires
                        </Button>
                        {canViewFinance && (
                            <Button
                                as="a"
                                href={route(`${routePrefix}.contributions.index`)}
                                variant="secondary"
                                size="sm"
                            >
                                <Landmark className="h-4 w-4" />
                                Paiements & contributions
                            </Button>
                        )}
                    </Card.Body>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
