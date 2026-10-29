import { useMemo, useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    Building2,
    ChevronDown,
    ChevronRight,
    ChevronUp,
    Filter,
    Globe,
    GraduationCap,
    MapPin,
    Search,
    SlidersHorizontal,
    Users,
    X,
    XCircle,
    BookOpen,
    MapPin as MapPinIcon,
    Layers,
} from 'lucide-react';

import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Button from '@/Components/UI/Button';
import Badge from '@/Components/UI/Badge';
import Pagination from '@/Components/UI/Pagination';
import EmptyState from '@/Components/UI/EmptyState';
import { Skeleton } from '@/Components/UI/Skeleton';
import { formatLongDate } from '@/Utils/date';

const translations = {
    fr: {
        title: 'Trouver une école',
        subtitle: 'Découvrez les établissements partenaires de CoriSchool près de chez vous.',
        searchPlaceholder: 'Rechercher par nom, ville...',
        filters: 'Filtres',
        clearFilters: 'Réinitialiser',
        schoolType: 'Type d\'établissement',
        allTypes: 'Tous les types',
        country: 'Pays',
        allCountries: 'Tous les pays',
        city: 'Ville',
        allCities: 'Toutes les villes',
        quartier: 'Quartier',
        allQuartiers: 'Tous les quartiers',
        program: 'Programme',
        allPrograms: 'Tous les programmes',
        minStudents: 'Élèves minimum',
        sortBy: 'Trier par',
        sortName: 'Nom (A-Z)',
        sortNameDesc: 'Nom (Z-A)',
        sortStudents: 'Nb d\'élèves (croissant)',
        sortStudentsDesc: 'Nb d\'élèves (décroissant)',
        sortTeachers: 'Nb d\'enseignants (croissant)',
        sortTeachersDesc: 'Nb d\'enseignants (décroissant)',
        sortClasses: 'Nb de classes (croissant)',
        sortClassesDesc: 'Nb de classes (décroissant)',
        sortNewest: 'Plus récentes',
        results: 'résultat(s)',
        noResults: 'Aucun établissement trouvé',
        noResultsDesc: 'Essayez de modifier vos critères de recherche ou de réinitialiser les filtres.',
        students: 'élèves',
        teachers: 'enseignants',
        classes: 'classes',
        founded: 'Fondée le',
        viewSchool: 'Voir l\'école',
        bilingual: 'Bilingue (FR/EN)',
        bilingualDesc: 'Écoles enseignant en français et en anglais',
    },
};

const schoolTypeLabels = {
    fr: {
        maternelle: 'Maternelle',
        primaire: 'Primaire',
        college: 'Collège',
        lycee: 'Lycée',
        superieur: 'Supérieur',
        groupe_scolaire: 'Groupe scolaire',
        autre: 'Autre',
    },
    en: {
        maternelle: 'Preschool',
        primaire: 'Primary',
        college: 'Middle School',
        lycee: 'High School',
        superieur: 'Higher Education',
        groupe_scolaire: 'School Group',
        autre: 'Other',
    },
};

const EASE = [0.22, 1, 0.36, 1];
const fadeUp = {
    hidden: { opacity: 0, y: 30 },
    visible: { opacity: 1, y: 0, transition: { duration: 0.6, ease: EASE } },
};
const stagger = {
    hidden: {},
    visible: { transition: { staggerChildren: 0.08, delayChildren: 0.1 } },
};
const viewport = { once: true, amount: 0.15 };

function SectionHeading({ eyebrow, title, description, centered = true }) {
    return (
        <motion.div
            variants={fadeUp}
            initial="hidden"
            whileInView="visible"
            viewport={viewport}
            className={`max-w-3xl ${centered ? 'mx-auto text-center' : ''}`}
        >
            <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">
                <Globe className="h-3.5 w-3.5" />
                {eyebrow}
            </div>
            <h2 className="text-3xl font-black tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">
                {title}
            </h2>
            {description && (
                <p className="mt-5 text-base leading-7 text-slate-600 sm:text-lg">
                    {description}
                </p>
            )}
        </motion.div>
    );
}

function LanguageSwitcher({ locale, onChange }) {
    return (
        <div className="relative">
            <Select
                value={locale}
                onChange={(e) => onChange(e.target.value)}
                className="w-36"
            >
                <option value="fr">Français</option>
                <option value="en">English</option>
            </Select>
        </div>
    );
}

function FilterSidebar({ filters, options, onFilterChange, locale, t, hasActiveFilters, onClearFilters }) {
    const [showFilters, setShowFilters] = useState(false);

    return (
        <aside className="lg:w-64 lg:sticky lg:top-24 lg:self-start lg:max-h-[calc(100vh-8rem)] lg:overflow-y-auto">
            <div className="lg:hidden mb-4 flex items-center justify-between">
                <h3 className="text-lg font-semibold text-slate-900">{t.filters}</h3>
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setShowFilters(!showFilters)}
                    className="gap-2"
                >
                    <Filter className="h-4 w-4" />
                    {showFilters ? t.clearFilters : t.filters}
                </Button>
            </div>

            <AnimatePresence>
                {showFilters || !showFilters ? (
                    <motion.div
                        initial={{ height: 0, opacity: 0 }}
                        animate={{ height: 'auto', opacity: 1 }}
                        exit={{ height: 0, opacity: 0 }}
                        transition={{ duration: 0.3, ease: EASE }}
                        className="overflow-hidden"
                    >
                        <Card className="lg:border lg:rounded-xl lg:shadow-sm">
                            <Card.Header className="hidden lg:block pb-0">
                                <h3 className="text-lg font-semibold text-slate-900">{t.filters}</h3>
                            </Card.Header>
                            <Card.Body className="space-y-6 p-0 lg:p-6">
                                {/* Search */}
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        <Search className="inline h-4 w-4 mr-1" /> {t.searchPlaceholder.split('...')[0]}
                                    </label>
                                    <div className="relative">
                                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" strokeWidth={2} />
                                        <Input
                                            value={filters.search}
                                            onChange={(e) => onFilterChange('search', e.target.value)}
                                            placeholder={t.searchPlaceholder}
                                            className="pl-10"
                                        />
                                    </div>
                                </div>

                                {/* Bilingual Filter */}
                                <div>
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={filters.bilingual}
                                            onChange={(e) => onFilterChange('bilingual', e.target.checked)}
                                            className="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                        />
                                        <BookOpen className="h-4 w-4 text-emerald-600 shrink-0" />
                                        <span className="text-sm font-medium text-slate-700">{t.bilingual}</span>
                                    </label>
                                    <p className="mt-1 text-xs text-slate-500">{t.bilingualDesc}</p>
                                </div>

                                {/* School Type */}
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        <GraduationCap className="inline h-4 w-4 mr-1" /> {t.schoolType}
                                    </label>
                                    <Select
                                        value={filters.school_type}
                                        onChange={(e) => onFilterChange('school_type', e.target.value)}
                                        className="w-full"
                                    >
                                        <option value="">{t.allTypes}</option>
                                        {Object.entries(options.school_types).map(([key, label]) => (
                                            <option key={key} value={key}>
                                                {schoolTypeLabels[locale]?.[key] || label}
                                            </option>
                                        ))}
                                    </Select>
                                </div>

                                {/* Country */}
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        <Globe className="inline h-4 w-4 mr-1" /> {t.country}
                                    </label>
                                    <Select
                                        value={filters.country}
                                        onChange={(e) => onFilterChange('country', e.target.value)}
                                        className="w-full"
                                    >
                                        <option value="">{t.allCountries}</option>
                                        {options.countries.map((country) => (
                                            <option key={country} value={country}>{country}</option>
                                        ))}
                                    </Select>
                                </div>

                                {/* City */}
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        <MapPinIcon className="inline h-4 w-4 mr-1" /> {t.city}
                                    </label>
                                    <Select
                                        value={filters.city}
                                        onChange={(e) => onFilterChange('city', e.target.value)}
                                        className="w-full"
                                        disabled={options.cities.length === 0}
                                    >
                                        <option value="">{t.allCities}</option>
                                        {options.cities.map((city) => (
                                            <option key={city} value={city}>{city}</option>
                                        ))}
                                    </Select>
                                </div>

                                {/* Quartier */}
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        <MapPinIcon className="inline h-4 w-4 mr-1" /> {t.quartier}
                                    </label>
                                    <Select
                                        value={filters.quartier}
                                        onChange={(e) => onFilterChange('quartier', e.target.value)}
                                        className="w-full"
                                        disabled={options.quartiers.length === 0}
                                    >
                                        <option value="">{t.allQuartiers}</option>
                                        {options.quartiers.map((quartier) => (
                                            <option key={quartier} value={quartier}>{quartier}</option>
                                        ))}
                                    </Select>
                                </div>

                                {/* Program */}
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        <Layers className="inline h-4 w-4 mr-1" /> {t.program}
                                    </label>
                                    <Select
                                        value={filters.program}
                                        onChange={(e) => onFilterChange('program', e.target.value)}
                                        className="w-full"
                                        disabled={options.programs.length === 0}
                                    >
                                        <option value="">{t.allPrograms}</option>
                                        {options.programs.map((program) => (
                                            <option key={program} value={program}>{program}</option>
                                        ))}
                                    </Select>
                                </div>

                                {/* Min Students */}
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        <Users className="inline h-4 w-4 mr-1" /> {t.minStudents}
                                    </label>
                                    <Input
                                        type="number"
                                        value={filters.min_students}
                                        onChange={(e) => onFilterChange('min_students', e.target.value)}
                                        placeholder="0"
                                        min="0"
                                        className="w-full"
                                    />
                                </div>

                                {/* Sort */}
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        <SlidersHorizontal className="inline h-4 w-4 mr-1" /> {t.sortBy}
                                    </label>
                                    <Select
                                        value={filters.sort}
                                        onChange={(e) => onFilterChange('sort', e.target.value)}
                                        className="w-full"
                                    >
                                        <option value="name">{t.sortName}</option>
                                        <option value="name_desc">{t.sortNameDesc}</option>
                                        <option value="students">{t.sortStudents}</option>
                                        <option value="students_desc">{t.sortStudentsDesc}</option>
                                        <option value="teachers">{t.sortTeachers}</option>
                                        <option value="teachers_desc">{t.sortTeachersDesc}</option>
                                        <option value="classes">{t.sortClasses}</option>
                                        <option value="classes_desc">{t.sortClassesDesc}</option>
                                        <option value="newest">{t.sortNewest}</option>
                                    </Select>
                                </div>

                                {hasActiveFilters && (
                                    <Button variant="outline" className="w-full" onClick={onClearFilters}>
                                        <XCircle className="h-4 w-4" />
                                        {t.clearFilters}
                                    </Button>
                                )}
                            </Card.Body>
                        </Card>
                    </motion.div>
                ) : null}
            </AnimatePresence>
        </aside>
    );
}

function SchoolCard({ school, locale, t }) {
    const [imageError, setImageError] = useState(false);

    const typeLabel = school.school_type
        ? schoolTypeLabels[locale]?.[school.school_type] || school.school_type_label
        : null;

    return (
        <Link
            href={school.public_url}
            className="group block overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:border-emerald-200 hover:shadow-xl"
        >
            <div className="relative aspect-[16/9] overflow-hidden">
                {school.hero_image_url && !imageError ? (
                    <img
                        src={school.hero_image_url}
                        alt={`${school.name} - ${school.city || ''}`}
                        className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        loading="lazy"
                        onError={() => setImageError(true)}
                    />
                ) : (
                    <div className="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200">
                        <Building2 className="h-12 w-12 text-slate-300" strokeWidth={1.5} />
                    </div>
                )}
                <div className="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent" />
                <div className="absolute top-3 left-3 flex flex-col gap-1.5">
                    {typeLabel && (
                        <Badge variant="outline" className="bg-white/90 backdrop-blur text-slate-900 border-slate-200">
                            {typeLabel}
                        </Badge>
                    )}
                    {school.is_bilingual && (
                        <Badge variant="secondary" className="bg-emerald-600 text-white">
                            <BookOpen className="h-3 w-3 mr-1" />
                            Bilingue
                        </Badge>
                    )}
                </div>
            </div>

            <div className="p-5 space-y-3">
                <h3 className="font-bold text-slate-900 group-hover:text-emerald-600 transition-colors line-clamp-1">
                    {school.name}
                </h3>

                {(school.city || school.country) && (
                    <div className="flex items-center gap-2 text-sm text-slate-500">
                        <MapPinIcon className="h-3.5 w-3.5 shrink-0" />
                        <span className="truncate">
                            {school.city ? `${school.city}, ` : ''}{school.country}
                        </span>
                    </div>
                )}

                {school.quartier && (
                    <div className="flex items-center gap-2 text-sm text-slate-500">
                        <MapPinIcon className="h-3.5 w-3.5 shrink-0" />
                        <span className="truncate text-xs font-medium text-slate-600 bg-slate-50 px-2 py-0.5 rounded">{school.quartier}</span>
                    </div>
                )}

                {school.slogan && (
                    <p className="text-sm text-slate-600 line-clamp-2">{school.slogan}</p>
                )}

                <div className="flex flex-wrap items-center gap-3 pt-2 border-t border-slate-100">
                    <div className="flex items-center gap-1.5 text-xs text-slate-500">
                        <Users className="h-3 w-3" />
                        <span>{school.students_count} {t.students}</span>
                    </div>
                    <div className="flex items-center gap-1.5 text-xs text-slate-500">
                        <GraduationCap className="h-3 w-3" />
                        <span>{school.teachers_count} {t.teachers}</span>
                    </div>
                    <div className="flex items-center gap-1.5 text-xs text-slate-500">
                        <Building2 className="h-3 w-3" />
                        <span>{school.classes_count} {t.classes}</span>
                    </div>
                </div>

                {school.founded_date && (
                    <div className="flex items-center gap-1.5 text-xs text-slate-500">
                        <span className="h-3 w-3 rounded-full bg-emerald-100 flex items-center justify-center">
                            <GraduationCap className="h-2.5 w-2.5 text-emerald-600" />
                        </span>
                        <span>{t.founded} {formatLongDate(school.founded_date)}</span>
                    </div>
                )}

                {school.programs && school.programs.length > 0 && (
                    <div className="flex flex-wrap gap-1.5">
                        {school.programs.slice(0, 3).map((program) => (
                            <Badge key={program} variant="outline" className="text-xs bg-white border-slate-200 text-slate-600">
                                {program}
                            </Badge>
                        ))}
                        {school.programs.length > 3 && (
                            <Badge variant="outline" className="text-xs bg-white border-slate-200 text-slate-500">
                                +{school.programs.length - 3}
                            </Badge>
                        )}
                    </div>
                )}

                <div className="pt-2 flex items-center justify-between text-sm font-medium text-emerald-600 group-hover:text-emerald-700">
                    <span>{t.viewSchool}</span>
                    <ChevronRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                </div>
            </div>
        </Link>
    );
}

function SchoolSkeleton() {
    return (
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <Skeleton className="aspect-[16/9] w-full" />
            <div className="p-5 space-y-4">
                <Skeleton className="h-6 w-3/4" />
                <Skeleton className="h-4 w-1/2" />
                <Skeleton className="h-4 w-5/6" />
                <div className="flex gap-4 pt-2 border-t border-slate-100">
                    <Skeleton className="h-4 w-24" />
                    <Skeleton className="h-4 w-28" />
                    <Skeleton className="h-4 w-24" />
                </div>
            </div>
        </div>
    );
}

export default function SchoolListing({ schools, filters, options, locale }) {
    const t = translations[locale] || translations.fr;
    const [localFilters, setLocalFilters] = useState(filters);
    const [debouncedFilters, setDebouncedFilters] = useState(filters);

    const hasActiveFilters = useMemo(() => {
        return !!(
            filters.search ||
            filters.school_type ||
            filters.country ||
            filters.city ||
            filters.quartier ||
            filters.program ||
            filters.min_students ||
            filters.bilingual
        );
    }, [filters]);

    useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedFilters(localFilters);
            const params = new URLSearchParams();
            Object.entries(localFilters).forEach(([key, value]) => {
                if (value && value !== '' && value !== 'name') {
                    params.set(key, value);
                } else if (key === 'sort' && value !== 'name') {
                    params.set(key, value);
                }
            });
            if (localFilters.direction === 'desc') {
                params.set('direction', 'desc');
            }
            if (localFilters.per_page && localFilters.per_page !== 12) {
                params.set('per_page', String(localFilters.per_page));
            }
            router.get(route('public.schools.index'), Object.fromEntries(params), {
                preserveScroll: true,
            });
        }, 300);
        return () => clearTimeout(timer);
    }, [localFilters]);

    const handleFilterChange = (key, value) => {
        setLocalFilters((prev) => ({ ...prev, [key]: value }));
    };

    const handleClearFilters = () => {
        const cleared = {
            search: '',
            school_type: '',
            country: '',
            city: '',
            quartier: '',
            program: '',
            min_students: '',
            bilingual: false,
            sort: 'name',
            direction: 'asc',
            per_page: 12,
        };
        setLocalFilters(cleared);
    };

    const sortedSchools = useMemo(() => {
        return schools.data;
    }, [schools.data]);

    return (
        <>
            <Head title={t.title}>
                <meta name="description" content={t.subtitle} />
                <meta name="robots" content="index, follow" />
            </Head>

            <div className="min-h-screen bg-slate-50">
                {/* Header */}
                <header className="sticky top-0 z-40 border-b border-slate-200 bg-white/85 backdrop-blur-xl">
                    <div className="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                        <Link
                            href="/"
                            className="group flex min-w-0 items-center gap-3"
                        >
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-sm">
                                <Building2 className="h-5 w-5" strokeWidth={2} />
                            </div>
                            <span className="hidden font-bold text-slate-950 sm:block">
                                CoriSchool
                            </span>
                        </Link>

                        <nav className="hidden items-center gap-6 lg:flex">
                            <Link
                                href={route('demo.request')}
                                className="text-sm font-semibold text-slate-600 transition hover:text-emerald-600"
                            >
                                {locale === 'fr' ? 'Demander une démo' : 'Request Demo'}
                            </Link>
                            <Link
                                href={route('login')}
                                className="rounded-xl px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50"
                            >
                                {locale === 'fr' ? 'Connexion' : 'Login'}
                            </Link>
                        </nav>

                        <div className="flex items-center gap-3">
                            <LanguageSwitcher locale={locale} onChange={handleFilterChange} />
                        </div>
                    </div>
                </header>

                {/* Hero */}
                <section className="relative overflow-hidden bg-gradient-to-b from-emerald-50 via-white to-white py-16 sm:py-24 lg:py-32">
                    <div className="absolute inset-0 opacity-50" style={{ background: `radial-gradient(60% 60% at 100% 0%, #05966915, transparent 70%)` }} />
                    <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <motion.div
                            variants={fadeUp}
                            initial="hidden"
                            whileInView="visible"
                            viewport={viewport}
                            className="max-w-3xl text-center mx-auto"
                        >
                            <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">
                                <Globe className="h-3.5 w-3.5" />
                                {locale === 'fr' ? 'Annuaire des écoles' : 'School Directory'}
                            </div>
                            <h1 className="text-4xl font-black tracking-tight text-slate-950 sm:text-5xl lg:text-6xl">
                                {t.title}
                            </h1>
                            <p className="mt-5 text-base leading-7 text-slate-600 sm:text-lg max-w-2xl mx-auto">
                                {t.subtitle}
                            </p>
                        </motion.div>
                    </div>
                </section>

                {/* Search & Filters Bar */}
                <section className="bg-white border-b border-slate-200 py-4 sm:py-6">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="relative flex-1 max-w-xl">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" strokeWidth={2} />
                                <Input
                                    value={localFilters.search}
                                    onChange={(e) => handleFilterChange('search', e.target.value)}
                                    placeholder={t.searchPlaceholder}
                                    className="pl-10 h-11 text-base"
                                />
                            </div>

                            <div className="flex items-center gap-3">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setLocalFilters((prev) => ({ ...prev, sort: prev.sort === 'name' ? 'name_desc' : 'name' }))}
                                    className="hidden gap-2 sm:inline-flex"
                                >
                                    <SlidersHorizontal className="h-4 w-4" />
                                    {t.sortBy}
                                </Button>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Results */}
                <section className="py-8 sm:py-12">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="flex flex-col lg:flex-row gap-8">
                            {/* Sidebar Filters */}
                            <FilterSidebar
                                filters={localFilters}
                                options={options}
                                onFilterChange={handleFilterChange}
                                locale={locale}
                                t={t}
                                hasActiveFilters={hasActiveFilters}
                                onClearFilters={handleClearFilters}
                            />

                            {/* Schools Grid */}
                            <div className="flex-1 min-w-0">
                                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                                    <p className="text-sm text-slate-600">
                                        <span className="font-semibold text-slate-900">{schools.total}</span> {t.results}
                                    </p>
                                    <div className="flex items-center gap-2">
                                        <label className="text-sm font-medium text-slate-700">{t.sortBy}</label>
                                        <Select
                                            value={localFilters.sort}
                                            onChange={(e) => handleFilterChange('sort', e.target.value)}
                                            className="w-48"
                                        >
                                            <option value="name">{t.sortName}</option>
                                            <option value="name_desc">{t.sortNameDesc}</option>
                                            <option value="students">{t.sortStudents}</option>
                                            <option value="students_desc">{t.sortStudentsDesc}</option>
                                            <option value="teachers">{t.sortTeachers}</option>
                                            <option value="teachers_desc">{t.sortTeachersDesc}</option>
                                            <option value="classes">{t.sortClasses}</option>
                                            <option value="classes_desc">{t.sortClassesDesc}</option>
                                            <option value="newest">{t.sortNewest}</option>
                                        </Select>
                                    </div>
                                </div>

                                {schools.data.length > 0 ? (
                                    <>
                                        <motion.div
                                            variants={stagger}
                                            initial="hidden"
                                            animate="visible"
                                            className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                                        >
                                            {sortedSchools.map((school) => (
                                                <SchoolCard
                                                    key={school.id}
                                                    school={school}
                                                    locale={locale}
                                                    t={t}
                                                />
                                            ))}
                                        </motion.div>

                                        {schools.last_page > 1 && (
                                            <div className="mt-8 flex justify-center">
                                                <Pagination
                                                    currentPage={schools.current_page}
                                                    lastPage={schools.last_page}
                                                    baseUrl={route('public.schools.index')}
                                                    params={debouncedFilters}
                                                />
                                            </div>
                                        )}
                                    </>
                                ) : (
                                    <EmptyState
                                        icon={Building2}
                                        title={t.noResults}
                                        description={t.noResultsDesc}
                                        action={
                                            <Button variant="primary" onClick={handleClearFilters} className="gap-2">
                                                <X className="h-4 w-4" />
                                                {t.clearFilters}
                                            </Button>
                                        }
                                    />
                                )}
                            </div>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-t border-slate-200 bg-white py-12">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center text-sm text-slate-500">
                        <p>
                            {locale === 'fr'
                                ? 'CoriSchool — La plateforme de gestion scolaire moderne pour l\'Afrique'
                                : 'CoriSchool — Modern school management platform for Africa'}
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}