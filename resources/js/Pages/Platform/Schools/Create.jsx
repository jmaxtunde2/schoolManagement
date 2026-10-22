import { useState, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, School, User, Mail, Lock, Calendar, MapPin, Phone, Palette, Globe, BookOpen, Layers } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';

const defaultColors = {
    primary_color: '#047857',
    secondary_color: '#0F766E',
    accent_color: '#D97706',
};

const BENIN_DEPARTMENTS = [
    'Alibori', 'Atacora', 'Atlantique', 'Borgou', 'Collines', 'Couffo',
    'Donga', 'Littoral', 'Mono', 'Oueme', 'Plateau', 'Zou'
];

const LANGUAGE_OPTIONS = [
    { value: 'fr', label: 'Français' },
    { value: 'en', label: 'English' },
    { value: 'es', label: 'Español' },
    { value: 'de', label: 'Deutsch' },
    { value: 'ar', label: 'العربية' },
];

const COMMON_PROGRAMS = [
    'Programme béninois',
    'Programme français',
    'Cambridge International',
    'IB (Baccalauréat International)',
    'Programme américain',
    'Programme nigérien',
    'Programme togolais',
    'Autre',
];

export default function SchoolCreate() {
    const [form, setForm] = useState({
        school_name: '',
        short_name: '',
        address: '',
        city: '',
        quartier: '',
        department: '',
        country: 'Bénin',
        phone: '',
        primary_color: defaultColors.primary_color,
        secondary_color: defaultColors.secondary_color,
        accent_color: defaultColors.accent_color,
        languages: ['fr'],
        programs: [],
        academic_year_name: '',
        academic_year_starts_on: '',
        academic_year_ends_on: '',
        admin_name: '',
        admin_email: '',
        admin_password: '',
        admin_password_confirmation: '',
        admin_phone: '',
    });

    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);

    const handleChange = (field, value) => {
        setForm(prev => ({ ...prev, [field]: value }));
        if (errors[field]) {
            setErrors(prev => ({ ...prev, [field]: '' }));
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.post(route('platform.schools.store'), form, {
            onError: (err) => setErrors(err),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AuthenticatedLayout title="Créer une école">
            <Head title="Nouvelle école" />

            <div className="max-w-4xl mx-auto">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <Link href={route('platform.schools.index')} className="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 mb-4">
                            <ArrowLeft className="h-4 w-4" />
                            Retour aux écoles
                        </Link>
                        <h1 className="text-2xl font-bold text-slate-900">Nouvelle école</h1>
                        <p className="mt-1 text-slate-500">
                            Créez et activez un établissement directement (équipe terrain).
                        </p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Informations école */}
                    <Card>
                        <Card.Header title="Informations de l'établissement" icon={School} />
                        <Card.Body className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">
                                        Nom complet <span className="text-red-500">*</span>
                                    </label>
                                    <Input
                                        value={form.school_name}
                                        onChange={(e) => handleChange('school_name', e.target.value)}
                                        placeholder="Ex: Lycée Moderne de Cotonou"
                                        error={errors.school_name}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">
                                        Nom court <span className="text-red-500">*</span>
                                    </label>
                                    <Input
                                        value={form.short_name}
                                        onChange={(e) => handleChange('short_name', e.target.value)}
                                        placeholder="Ex: LMC"
                                        maxLength={50}
                                        error={errors.short_name}
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">
                                    Adresse <span className="text-red-500">*</span>
                                </label>
                                <Input
                                    value={form.address}
                                    onChange={(e) => handleChange('address', e.target.value)}
                                    placeholder="Ex: Rue des Écoles, Quartier..."
                                    error={errors.address}
                                />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">
                                        Ville <span className="text-red-500">*</span>
                                    </label>
                                    <Input
                                        value={form.city}
                                        onChange={(e) => handleChange('city', e.target.value)}
                                        placeholder="Ex: Cotonou"
                                        error={errors.city}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">
                                        Quartier
                                    </label>
                                    <Input
                                        value={form.quartier}
                                        onChange={(e) => handleChange('quartier', e.target.value)}
                                        placeholder="Ex: Ganhi, Akpakpa..."
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">
                                        Département <span className="text-red-500">*</span>
                                    </label>
                                    <Select
                                        value={form.department}
                                        onChange={(e) => handleChange('department', e.target.value)}
                                        error={errors.department}
                                    >
                                        <option value="">— Sélectionner —</option>
                                        {BENIN_DEPARTMENTS.map((dept) => (
                                            <option key={dept} value={dept}>{dept}</option>
                                        ))}
                                    </Select>
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">
                                        Pays <span className="text-red-500">*</span>
                                    </label>
                                    <Select
                                        value={form.country}
                                        onChange={(e) => handleChange('country', e.target.value)}
                                        error={errors.country}
                                    >
                                        <option value="Bénin">Bénin</option>
                                        <option value="Togo">Togo</option>
                                        <option value="Niger">Niger</option>
                                        <option value="Burkina Faso">Burkina Faso</option>
                                    </Select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">
                                        Téléphone <span className="text-red-500">*</span>
                                    </label>
                                    <Input
                                        value={form.phone}
                                        onChange={(e) => handleChange('phone', e.target.value)}
                                        placeholder="+229 01 XX XX XX XX"
                                        error={errors.phone}
                                    />
                                </div>
                            </div>

                            {/* Langues & Programmes */}
                            <div className="grid gap-4 sm:grid-cols-2 border-t border-slate-100 pt-4">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1 flex items-center gap-1">
                                        <Globe className="h-4 w-4" /> Langues d'enseignement
                                    </label>
                                    <div className="flex flex-wrap gap-2">
                                        {LANGUAGE_OPTIONS.map((lang) => (
                                            <label key={lang.value} className="inline-flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    checked={form.languages.includes(lang.value)}
                                                    onChange={(e) => {
                                                        const newLangs = e.target.checked
                                                            ? [...form.languages, lang.value]
                                                            : form.languages.filter(l => l !== lang.value);
                                                        handleChange('languages', newLangs);
                                                    }}
                                                    className="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                                />
                                                <span className="text-sm text-slate-700">{lang.label}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1 flex items-center gap-1">
                                        <Layers className="h-4 w-4" /> Programmes
                                    </label>
                                    <div className="flex flex-wrap gap-2">
                                        {COMMON_PROGRAMS.map((prog) => (
                                            <label key={prog} className="inline-flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    checked={form.programs.includes(prog)}
                                                    onChange={(e) => {
                                                        const newProgs = e.target.checked
                                                            ? [...form.programs, prog]
                                                            : form.programs.filter(p => p !== prog);
                                                        handleChange('programs', newProgs);
                                                    }}
                                                    className="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                                />
                                                <span className="text-sm text-slate-700">{prog}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            <div className="border-t border-slate-100 pt-4">
                                <p className="text-sm font-medium text-slate-700 mb-3 flex items-center gap-2">
                                    <Palette className="h-4 w-4" /> Couleurs de l'établissement (optionnel)
                                </p>
                                <div className="grid gap-4 sm:grid-cols-3">
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 mb-1">
                                            Primaire
                                        </label>
                                        <div className="flex items-center gap-2">
                                            <Input
                                                type="color"
                                                value={form.primary_color}
                                                onChange={(e) => handleChange('primary_color', e.target.value)}
                                                className="h-10 w-14 p-0"
                                            />
                                            <Input
                                                value={form.primary_color}
                                                onChange={(e) => handleChange('primary_color', e.target.value)}
                                                className="text-sm font-mono"
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 mb-1">
                                            Secondaire
                                        </label>
                                        <div className="flex items-center gap-2">
                                            <Input
                                                type="color"
                                                value={form.secondary_color}
                                                onChange={(e) => handleChange('secondary_color', e.target.value)}
                                                className="h-10 w-14 p-0"
                                            />
                                            <Input
                                                value={form.secondary_color}
                                                onChange={(e) => handleChange('secondary_color', e.target.value)}
                                                className="text-sm font-mono"
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 mb-1">
                                            Accent
                                        </label>
                                        <div className="flex items-center gap-2">
                                            <Input
                                                type="color"
                                                value={form.accent_color}
                                                onChange={(e) => handleChange('accent_color', e.target.value)}
                                                className="h-10 w-14 p-0"
                                            />
                                            <Input
                                                value={form.accent_color}
                                                onChange={(e) => handleChange('accent_color', e.target.value)}
                                                className="text-sm font-mono"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Card.Body>
                    </Card>

                    {/* Année scolaire */}
                    <Card>
                        <Card.Header title="Année scolaire initiale" icon={Calendar} />
                        <Card.Body className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">
                                        Nom de l'année <span className="text-red-500">*</span>
                                    </label>
                                    <Input
                                        value={form.academic_year_name}
                                        onChange={(e) => handleChange('academic_year_name', e.target.value)}
                                        placeholder="Ex: 2026-2027"
                                        error={errors.academic_year_name}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">
                                        Début <span className="text-red-500">*</span>
                                    </label>
                                    <Input
                                        type="date"
                                        value={form.academic_year_starts_on}
                                        onChange={(e) => handleChange('academic_year_starts_on', e.target.value)}
                                        error={errors.academic_year_starts_on}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">
                                        Fin <span className="text-red-500">*</span>
                                    </label>
                                    <Input
                                        type="date"
                                        value={form.academic_year_ends_on}
                                        onChange={(e) => handleChange('academic_year_ends_on', e.target.value)}
                                        error={errors.academic_year_ends_on}
                                    />
                                </div>
                            </div>
                        </Card.Body>
                    </Card>

                    {/* Compte administrateur */}
                    <Card>
                        <Card.Header title="Compte administrateur" icon={User} />
                        <Card.Body className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">
                                        Nom complet <span className="text-red-500">*</span>
                                    </label>
                                    <Input
                                        value={form.admin_name}
                                        onChange={(e) => handleChange('admin_name', e.target.value)}
                                        placeholder="Ex: Jean Kossi"
                                        error={errors.admin_name}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">
                                        E-mail <span className="text-red-500">*</span>
                                    </label>
                                    <Input
                                        type="email"
                                        value={form.admin_email}
                                        onChange={(e) => handleChange('admin_email', e.target.value)}
                                        placeholder="direction@ecole.test"
                                        error={errors.admin_email}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">
                                        Mot de passe <span className="text-red-500">*</span>
                                    </label>
                                    <Input
                                        type="password"
                                        value={form.admin_password}
                                        onChange={(e) => handleChange('admin_password', e.target.value)}
                                        placeholder="Min. 8 caractères"
                                        error={errors.admin_password}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">
                                        Confirmation <span className="text-red-500">*</span>
                                    </label>
                                    <Input
                                        type="password"
                                        value={form.admin_password_confirmation}
                                        onChange={(e) => handleChange('admin_password_confirmation', e.target.value)}
                                        placeholder="Confirmez le mot de passe"
                                        error={errors.admin_password_confirmation}
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">
                                    Téléphone (optionnel)
                                </label>
                                <Input
                                    value={form.admin_phone}
                                    onChange={(e) => handleChange('admin_phone', e.target.value)}
                                    placeholder="+229 01 XX XX XX XX"
                                />
                            </div>
                        </Card.Body>
                    </Card>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-3">
                        <Link href={route('platform.schools.index')}>
                            <Button type="button" variant="secondary">
                                <ArrowLeft className="h-4 w-4" />
                                Annuler
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing} className="gap-2">
                            {processing ? 'Création...' : 'Créer et activer l\'école'}
                        </Button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}