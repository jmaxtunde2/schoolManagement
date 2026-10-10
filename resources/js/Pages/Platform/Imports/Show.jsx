import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    Building2,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
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
    X,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Badge from '@/Components/UI/Badge';
import Alert from '@/Components/UI/Alert';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';

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

const typeOrder = ['academic_years', 'academic_periods', 'classes', 'subjects', 'teachers', 'students', 'parents'];

export default function ImportsShow({ school, importTypes, import_results, success }) {
    const [showPreview, setShowPreview] = useState(false);
    const [previewData, setPreviewData] = useState([]);
    const [previewHeaders, setPreviewHeaders] = useState([]);

    const { data, setData, post, processing, errors, reset } = useForm({
        type: '',
        file: null,
    });

    function handleFileChange(event) {
        const file = event.target.files?.[0];
        if (!file) return;

        setData('file', file);

        // Preview first few rows
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const text = e.target?.result;
                const lines = text.split('\n').filter(l => l.trim());
                if (lines.length > 0) {
                    const headers = lines[0].split(',').map(h => h.trim().replace(/"/g, ''));
                    setPreviewHeaders(headers);

                    const rows = lines.slice(1, 6).map(line => {
                        const values = line.split(',').map(v => v.trim().replace(/"/g, ''));
                        const obj = {};
                        headers.forEach((h, i) => { obj[h] = values[i] || ''; });
                        return obj;
                    });
                    setPreviewData(rows);
                }
            } catch (err) {
                console.error('Preview error:', err);
            }
        };
        reader.readAsText(file);
    }

    function submitImport(event) {
        event.preventDefault();

        if (!data.type || !data.file) {
            return;
        }

        post(route('platform.imports.store', school.id), {
            forceFormData: true,
            onSuccess: () => {
                reset();
                setPreviewData([]);
                setPreviewHeaders([]);
            },
        });
    }

    function downloadTemplate(type) {
        window.location.href = route('platform.imports.template', type);
    }

    const sortedTypes = typeOrder
        .filter(key => importTypes[key])
        .map(key => ({ key, ...importTypes[key] }));

    return (
        <AuthenticatedLayout title="Coriyase — Platform">
            <Head title={`Import — ${school.name}`} />

            {/* Hero */}
            <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white shadow-lg mb-6">
                <div className="absolute -right-10 -top-10 h-36 w-36 rounded-full bg-white/10" />
                <div className="absolute -bottom-20 right-28 h-48 w-48 rounded-full bg-white/5" />

                <div className="relative p-6">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <Link
                                href={route('platform.imports.index')}
                                className="mb-4 inline-flex items-center gap-2 text-white/80 hover:text-white transition-colors"
                            >
                                <ArrowLeft className="h-5 w-5" strokeWidth={2} />
                                Retour aux imports
                            </Link>

                            <div className="mb-3 flex items-center gap-3">
                                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                                    <Upload className="h-5 w-5" strokeWidth={2} />
                                </div>
                                <span className="text-sm font-medium text-white/80">
                                    Administration Coriyase
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Import — {school.name}
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-white/75 sm:text-base">
                                {school.slug} — Téléchargez un fichier Excel/CSV pour importer des données
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {success && (
                <Alert type="success" className="mb-6" onClose={() => window.location.href = window.location.pathname}>
                    {success}
                </Alert>
            )}

            {import_results && import_results.errors && import_results.errors.length > 0 && (
                <Alert type="warning" className="mb-6">
                    <div className="font-medium mb-2">Import terminé avec des erreurs :</div>
                    <ul className="list-disc list-inside text-sm space-y-1">
                        {import_results.errors.slice(0, 5).map((err, i) => (
                            <li key={i}>{err}</li>
                        ))}
                        {import_results.errors.length > 5 && (
                            <li>... et {import_results.errors.length - 5} autre(s) erreur(s)</li>
                        )}
                    </ul>
                </Alert>
            )}

            {/* Types d'import */}
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                {sortedTypes.map(({ key, label, description, required_columns, optional_columns, sample_headers }) => {
                    const Icon = typeIcons[key] || FileSpreadsheet;
                    const color = typeColors[key] || 'from-slate-500 to-slate-600';
                    const isSelected = data.type === key;

                    return (
                        <div
                            key={key}
                            onClick={() => setData('type', isSelected ? '' : key)}
                            className={`relative cursor-pointer rounded-2xl border-2 p-5 transition-all ${
                                isSelected
                                    ? 'border-primary bg-primary/5 shadow-lg ring-2 ring-primary/20'
                                    : 'border-slate-200 hover:border-primary/50 hover:shadow-md'
                            }`}
                        >
                            <div className="absolute -top-2 -right-2">
                                {isSelected && (
                                    <CheckCircle2 className="h-5 w-5 text-primary" strokeWidth={2} />
                                )}
                            </div>

                            <div className="mb-3 flex h-11 w-11 items-center justify-center rounded-xl text-white"
                                style={{ background: `linear-gradient(135deg, ${color.replace('from-', '').replace(' to-', ', ')} 100%)` }}>
                                <Icon className="h-5 w-5" strokeWidth={2} />
                            </div>

                            <h4 className="font-semibold text-slate-900">{label}</h4>
                            <p className="mt-1 text-sm text-slate-500 line-clamp-2">{description}</p>

                            <div className="mt-3 flex flex-wrap gap-1">
                                {(required_columns || []).map((col) => (
                                    <Badge key={col} tone="emerald" size="sm">{col}*</Badge>
                                ))}
                                {(optional_columns || []).map((col) => (
                                    <Badge key={col} tone="slate" size="sm">{col}</Badge>
                                ))}
                            </div>

                            <Button
                                className="mt-3 w-full gap-2"
                                variant={isSelected ? 'primary' : 'outline'}
                                onClick={(e) => {
                                    e.stopPropagation();
                                    setData('type', key);
                                    downloadTemplate(key);
                                }}
                            >
                                <Download className="h-4 w-4" strokeWidth={2} />
                                Template
                            </Button>
                        </div>
                    );
                })}
            </div>

            {/* Formulaire d'import */}
            {data.type && (
                <Card className="mt-6">
                    <Card.Header
                        title={`Importer : ${importTypes[data.type].label}`}
                        description={importTypes[data.type].description}
                    />

                    <Card.Body>
                        <form onSubmit={submitImport} className="space-y-4">
                            {errors.type && (
                                <p className="text-sm text-red-600">{errors.type}</p>
                            )}

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-2">
                                    Fichier Excel/CSV <span className="text-red-500">*</span>
                                </label>
                                <div className="relative">
                                    <input
                                        type="file"
                                        name="file"
                                        accept=".xlsx,.xls,.csv"
                                        onChange={handleFileChange}
                                        className={`block w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-900 shadow-sm transition min-h-[44px] focus:outline-none focus:ring-2 focus:ring-offset-0 ${
                                            errors.file ? 'border-red-400 focus:ring-red-300' : 'border-slate-300 focus:ring-primary/30 focus:border-primary'
                                        }`}
                                    />
                                    {errors.file && (
                                        <p className="mt-1 text-sm text-red-600">{errors.file}</p>
                                    )}
                                </div>
                            </div>

                            {previewHeaders.length > 0 && (
                                <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <div className="flex items-center justify-between mb-2">
                                        <span className="font-medium text-slate-700">Aperçu ({previewData.length} lignes)</span>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => setShowPreview(!showPreview)}
                                        >
                                            {showPreview ? (
                                                <>
                                                    <ChevronUp className="h-4 w-4" strokeWidth={2} />
                                                    Masquer
                                                </>
                                            ) : (
                                                <>
                                                    <ChevronDown className="h-4 w-4" strokeWidth={2} />
                                                    Voir
                                                </>
                                            )}
                                        </Button>
                                    </div>

                                    {showPreview && (
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-sm">
                                                <thead>
                                                    <tr className="text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                                        {previewHeaders.map(h => (
                                                            <th key={h} className="px-3 py-2">{h}</th>
                                                        ))}
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-slate-100">
                                                    {previewData.map((row, i) => (
                                                        <tr key={i} className="hover:bg-white">
                                                            {previewHeaders.map(h => (
                                                                <td key={h} className="px-3 py-2 text-slate-700">{row[h] || '—'}</td>
                                                            ))}
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )}
                                </div>
                            )}

                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => {
                                        setData('type', '');
                                        reset();
                                        setPreviewData([]);
                                        setPreviewHeaders([]);
                                    }}
                                >
                                    <X className="h-4 w-4" strokeWidth={2} />
                                    Annuler
                                </Button>
                                <Button type="submit" loading={processing} className="gap-2">
                                    <Upload className="h-4 w-4" strokeWidth={2} />
                                    Importer
                                </Button>
                            </div>
                        </form>
                    </Card.Body>
                </Card>
            )}

            {/* Ordre recommandé */}
            <Card className="mt-6">
                <Card.Header
                    title="Ordre recommandé d'import"
                    description="Suivez cet ordre pour éviter les erreurs de référence"
                />
                <Card.Body>
                    <ol className="space-y-3">
                        {typeOrder
                            .filter(key => importTypes[key])
                            .map((key, index) => {
                                const config = importTypes[key];
                                const Icon = typeIcons[key] || FileSpreadsheet;
                                const color = typeColors[key] || 'from-slate-500 to-slate-600';

                                return (
                                    <li
                                        key={key}
                                        className="flex items-center gap-4 p-4 rounded-xl bg-slate-50"
                                    >
                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-white"
                                            style={{ background: `linear-gradient(135deg, ${color.replace('from-', '').replace(' to-', ', ')} 100%)` }}>
                                            <Icon className="h-5 w-5" strokeWidth={2} />
                                        </div>

                                        <div className="flex-1">
                                            <div className="flex items-center gap-2">
                                                <span className="text-xl font-bold text-slate-300">{index + 1}</span>
                                                <span className="font-medium text-slate-900">{config.label}</span>
                                            </div>
                                            <p className="text-sm text-slate-500">{config.description}</p>
                                        </div>

                                        <Badge tone="blue">{config.required_columns?.length || 0} requis + {config.optional_columns?.length || 0} optionnel</Badge>
                                    </li>
                                );
                            })}
                    </ol>

                    <div className="mt-4 p-4 rounded-lg bg-amber-50 border border-amber-200">
                        <div className="flex items-start gap-3">
                            <AlertCircle className="h-5 w-5 text-amber-600 mt-0.5" strokeWidth={2} />
                            <div>
                                <p className="font-medium text-amber-800">Points d'attention :</p>
                                <ul className="mt-2 list-disc list-inside text-sm text-amber-700 space-y-1">
                                    <li>Les classes doivent exister avant d'importer les élèves</li>
                                    <li>Les matières doivent exister avant d'importer les enseignants</li>
                                    <li>Les élèves doivent exister avant d'importer les parents (liaison)</li>
                                    <li>Les années scolaires doivent exister avant les périodes</li>
                                    <li>Les emails doivent être uniques (utilisés pour créer les comptes utilisateurs)</li>
                                    <li>Les mots de passe par défaut : <code className="bg-amber-100 px-1 rounded">password123</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </Card.Body>
            </Card>
        </AuthenticatedLayout>
    );
}