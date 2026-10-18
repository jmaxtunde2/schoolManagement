import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, BookOpen, Layers, Pencil, Plus, Trash2 } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Checkbox from '@/Components/UI/Checkbox';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import EmptyState from '@/Components/UI/EmptyState';
import Field from '@/Components/UI/Field';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import Table from '@/Components/UI/Table';

const emptyLevel = { name: '', code: '', position: '', is_active: true };
const emptySubject = { name: '', code: '', color: '', position: '', is_active: true };

export default function ExamBankReferences({ levels = [], subjects = [] }) {
    const [editingLevel, setEditingLevel] = useState(null);
    const [showLevelForm, setShowLevelForm] = useState(false);
    const [levelToDelete, setLevelToDelete] = useState(null);
    const [deletingLevel, setDeletingLevel] = useState(false);

    const [editingSubject, setEditingSubject] = useState(null);
    const [showSubjectForm, setShowSubjectForm] = useState(false);
    const [subjectToDelete, setSubjectToDelete] = useState(null);
    const [deletingSubject, setDeletingSubject] = useState(false);

    const levelForm = useForm(emptyLevel);
    const subjectForm = useForm(emptySubject);

    function openLevelCreate() {
        levelForm.reset();
        setEditingLevel(null);
        setShowLevelForm(true);
    }

    function openLevelEdit(level) {
        levelForm.setData({
            name: level.name,
            code: level.code ?? '',
            position: level.position ?? '',
            is_active: level.is_active,
        });
        setEditingLevel(level);
        setShowLevelForm(true);
    }

    function submitLevel(e) {
        e.preventDefault();
        const options = { onSuccess: () => { setShowLevelForm(false); setEditingLevel(null); levelForm.reset(); } };
        if (editingLevel) {
            levelForm.put(route('platform.exam-bank.levels.update', editingLevel.id), options);
        } else {
            levelForm.post(route('platform.exam-bank.levels.store'), options);
        }
    }

    function confirmDeleteLevel() {
        if (!levelToDelete) return;
        setDeletingLevel(true);
        router.delete(route('platform.exam-bank.levels.destroy', levelToDelete.id), {
            onFinish: () => { setDeletingLevel(false); setLevelToDelete(null); },
        });
    }

    function openSubjectCreate() {
        subjectForm.reset();
        setEditingSubject(null);
        setShowSubjectForm(true);
    }

    function openSubjectEdit(subject) {
        subjectForm.setData({
            name: subject.name,
            code: subject.code ?? '',
            color: subject.color ?? '',
            position: subject.position ?? '',
            is_active: subject.is_active,
        });
        setEditingSubject(subject);
        setShowSubjectForm(true);
    }

    function submitSubject(e) {
        e.preventDefault();
        const options = { onSuccess: () => { setShowSubjectForm(false); setEditingSubject(null); subjectForm.reset(); } };
        if (editingSubject) {
            subjectForm.put(route('platform.exam-bank.subjects.update', editingSubject.id), options);
        } else {
            subjectForm.post(route('platform.exam-bank.subjects.store'), options);
        }
    }

    function confirmDeleteSubject() {
        if (!subjectToDelete) return;
        setDeletingSubject(true);
        router.delete(route('platform.exam-bank.subjects.destroy', subjectToDelete.id), {
            onFinish: () => { setDeletingSubject(false); setSubjectToDelete(null); },
        });
    }

    const levelColumns = [
        {
            key: 'name',
            header: 'Niveau',
            render: (level) => (
                <div className="flex items-center gap-2">
                    <span className="font-medium text-slate-800">{level.name}</span>
                    {level.code && <span className="text-xs text-slate-400">{level.code}</span>}
                    {!level.is_active && <span className="text-xs text-slate-400">(inactif)</span>}
                </div>
            ),
        },
        { key: 'position', header: 'Ordre', render: (level) => level.position },
        {
            key: 'actions',
            header: '',
            render: (level) => (
                <div className="flex justify-end gap-1">
                    <button type="button" onClick={() => openLevelEdit(level)} className="rounded-lg p-2 text-slate-500 hover:bg-indigo-50 hover:text-indigo-600" aria-label="Modifier">
                        <Pencil className="h-4 w-4" />
                    </button>
                    <button type="button" onClick={() => setLevelToDelete(level)} className="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Supprimer">
                        <Trash2 className="h-4 w-4" />
                    </button>
                </div>
            ),
        },
    ];

    const subjectColumns = [
        {
            key: 'name',
            header: 'Matière',
            render: (subject) => (
                <div className="flex items-center gap-2">
                    <span className="font-medium text-slate-800">{subject.name}</span>
                    {subject.code && <span className="text-xs text-slate-400">{subject.code}</span>}
                    {!subject.is_active && <span className="text-xs text-slate-400">(inactive)</span>}
                </div>
            ),
        },
        { key: 'position', header: 'Ordre', render: (subject) => subject.position },
        {
            key: 'actions',
            header: '',
            render: (subject) => (
                <div className="flex justify-end gap-1">
                    <button type="button" onClick={() => openSubjectEdit(subject)} className="rounded-lg p-2 text-slate-500 hover:bg-indigo-50 hover:text-indigo-600" aria-label="Modifier">
                        <Pencil className="h-4 w-4" />
                    </button>
                    <button type="button" onClick={() => setSubjectToDelete(subject)} className="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Supprimer">
                        <Trash2 className="h-4 w-4" />
                    </button>
                </div>
            ),
        },
    ];

    return (
        <AuthenticatedLayout title="Coriyase — Platform">
            <Head title="Référentiels de la banque d'épreuves" />

            <div className="mb-6 flex items-center gap-3">
                <Link href={route('platform.exam-bank.index')}>
                    <Button variant="ghost" size="sm" className="gap-1.5">
                        <ArrowLeft className="h-4 w-4" /> Retour
                    </Button>
                </Link>
                <div>
                    <h1 className="text-xl font-bold text-slate-900">Référentiels</h1>
                    <p className="text-sm text-slate-500">Niveaux et matières disponibles pour classer la banque.</p>
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <Card>
                    <Card.Header
                        title="Niveaux"
                        description="Proposés lors du classement des ressources."
                        actions={
                            <Button size="sm" className="gap-1.5" onClick={openLevelCreate}>
                                <Plus className="h-4 w-4" /> Ajouter
                            </Button>
                        }
                    />
                    <Card.Body>
                        <Table
                            rows={levels}
                            columns={levelColumns}
                            emptyState={
                                <EmptyState
                                    icon={Layers}
                                    title="Aucun niveau"
                                    description="Ajoutez les niveaux du système scolaire."
                                    action={<Button onClick={openLevelCreate}>Créer un niveau</Button>}
                                />
                            }
                        />
                    </Card.Body>
                </Card>

                <Card>
                    <Card.Header
                        title="Matières"
                        description="Référentiel central des matières."
                        actions={
                            <Button size="sm" className="gap-1.5" onClick={openSubjectCreate}>
                                <Plus className="h-4 w-4" /> Ajouter
                            </Button>
                        }
                    />
                    <Card.Body>
                        <Table
                            rows={subjects}
                            columns={subjectColumns}
                            emptyState={
                                <EmptyState
                                    icon={BookOpen}
                                    title="Aucune matière"
                                    description="Ajoutez les matières enseignées."
                                    action={<Button onClick={openSubjectCreate}>Créer une matière</Button>}
                                />
                            }
                        />
                    </Card.Body>
                </Card>
            </div>

            <Modal
                show={showLevelForm}
                onClose={() => setShowLevelForm(false)}
                title={editingLevel ? 'Modifier le niveau' : 'Nouveau niveau'}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setShowLevelForm(false)}>Annuler</Button>
                        <Button onClick={submitLevel} loading={levelForm.processing}>
                            {editingLevel ? 'Enregistrer' : 'Créer'}
                        </Button>
                    </>
                }
            >
                <form onSubmit={submitLevel} className="space-y-5">
                    <Field label="Nom" required error={levelForm.errors.name}>
                        <Input value={levelForm.data.name} onChange={(e) => levelForm.setData('name', e.target.value)} placeholder="Ex : 3e" />
                    </Field>
                    <div className="grid grid-cols-2 gap-4">
                        <Field label="Code" error={levelForm.errors.code}>
                            <Input value={levelForm.data.code} onChange={(e) => levelForm.setData('code', e.target.value)} placeholder="Facultatif" />
                        </Field>
                        <Field label="Ordre" error={levelForm.errors.position}>
                            <Input type="number" min="0" value={levelForm.data.position} onChange={(e) => levelForm.setData('position', e.target.value)} />
                        </Field>
                    </div>
                    <Checkbox checked={levelForm.data.is_active} onChange={(e) => levelForm.setData('is_active', e.target.checked)} label="Actif" />
                </form>
            </Modal>

            <Modal
                show={showSubjectForm}
                onClose={() => setShowSubjectForm(false)}
                title={editingSubject ? 'Modifier la matière' : 'Nouvelle matière'}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setShowSubjectForm(false)}>Annuler</Button>
                        <Button onClick={submitSubject} loading={subjectForm.processing}>
                            {editingSubject ? 'Enregistrer' : 'Créer'}
                        </Button>
                    </>
                }
            >
                <form onSubmit={submitSubject} className="space-y-5">
                    <Field label="Nom" required error={subjectForm.errors.name}>
                        <Input value={subjectForm.data.name} onChange={(e) => subjectForm.setData('name', e.target.value)} placeholder="Ex : Mathématiques" />
                    </Field>
                    <div className="grid grid-cols-2 gap-4">
                        <Field label="Code" error={subjectForm.errors.code}>
                            <Input value={subjectForm.data.code} onChange={(e) => subjectForm.setData('code', e.target.value)} placeholder="Facultatif" />
                        </Field>
                        <Field label="Ordre" error={subjectForm.errors.position}>
                            <Input type="number" min="0" value={subjectForm.data.position} onChange={(e) => subjectForm.setData('position', e.target.value)} />
                        </Field>
                    </div>
                    <Checkbox checked={subjectForm.data.is_active} onChange={(e) => subjectForm.setData('is_active', e.target.checked)} label="Active" />
                </form>
            </Modal>

            <ConfirmDialog
                show={!!levelToDelete}
                onClose={() => setLevelToDelete(null)}
                onConfirm={confirmDeleteLevel}
                loading={deletingLevel}
                title="Supprimer le niveau"
                description={levelToDelete ? `Supprimer « ${levelToDelete.name} » ? Les ressources classées perdront ce niveau.` : ''}
                variant="danger"
                confirmLabel="Supprimer"
            />

            <ConfirmDialog
                show={!!subjectToDelete}
                onClose={() => setSubjectToDelete(null)}
                onConfirm={confirmDeleteSubject}
                loading={deletingSubject}
                title="Supprimer la matière"
                description={subjectToDelete ? `Supprimer « ${subjectToDelete.name} » ? Les ressources classées perdront cette matière.` : ''}
                variant="danger"
                confirmLabel="Supprimer"
            />
        </AuthenticatedLayout>
    );
}
