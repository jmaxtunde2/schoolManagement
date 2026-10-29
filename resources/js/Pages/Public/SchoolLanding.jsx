import { useEffect, useMemo, useRef, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AnimatePresence, motion, useInView, useReducedMotion } from 'framer-motion';
import {
    ArrowRight,
    BookOpenCheck,
    CalendarDays,
    CheckCircle2,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Clock3,
    Coins,
    ExternalLink,
    GraduationCap,
    HeartHandshake,
    Images,
    Mail,
    MapPin,
    Menu,
    Phone,
    Quote,
    School,
    ShieldCheck,
    Sparkles,
    Target,
    UserPlus,
    X,
} from 'lucide-react';

import { formatLongDate } from '@/Utils/date';

const fallbackHero =
    'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=2200&q=85';

const EASE = [0.22, 1, 0.36, 1];

const fadeUp = {
    hidden: { opacity: 0, y: 34 },
    visible: { opacity: 1, y: 0, transition: { duration: 0.7, ease: EASE } },
};

const stagger = {
    hidden: {},
    visible: { transition: { staggerChildren: 0.1, delayChildren: 0.05 } },
};

const revealViewport = { once: true, amount: 0.2 };

/** Convertit une couleur hex (#rgb / #rrggbb) en rgba avec un canal alpha. */
function withAlpha(color, alpha) {
    const fallback = `rgba(5, 150, 105, ${alpha})`;

    if (!color || typeof color !== 'string') {
        return fallback;
    }

    const hex = color.replace('#', '');
    const full = hex.length === 3 ? hex.split('').map((char) => char + char).join('') : hex;

    if (full.length !== 6) {
        return fallback;
    }

    const value = parseInt(full, 16);

    if (Number.isNaN(value)) {
        return fallback;
    }

    const r = (value >> 16) & 255;
    const g = (value >> 8) & 255;
    const b = value & 255;

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

export default function SchoolLanding({
    school,
    stats,
    features = [],
    structuredData = null,
    testimonials = [],
    gallery = [],
    tuition = null,
    curriculum = null,
    calendar = null,
    preview = false,
    preview_dashboard_url = null,
    preinscription_url = null,
}) {
    const [menuOpen, setMenuOpen] = useState(false);
    const [lightboxItem, setLightboxItem] = useState(null);
    const [openCurriculum, setOpenCurriculum] = useState(0);
    const reduceMotion = useReducedMotion();

    const tuitionFees = useMemo(
        () => (Array.isArray(tuition?.fees) ? tuition.fees : []),
        [tuition]
    );
    const curriculumItems = useMemo(
        () => (Array.isArray(curriculum?.items) ? curriculum.items : []),
        [curriculum]
    );
    const calendarItems = useMemo(
        () => (Array.isArray(calendar?.items) ? calendar.items : []),
        [calendar]
    );

    // Regroupement des liens pour éviter une barre de navigation surchargée.
    const navGroups = useMemo(() => {
        const groups = [{ label: 'Accueil', href: '#home' }];

        const establishment = [
            { label: 'À propos', href: '#about' },
            { label: 'Notre approche', href: '#strengths' },
            { label: 'Impact', href: '#impact' },
        ];
        if (testimonials.length > 0) {
            establishment.push({ label: 'Témoignages', href: '#testimonials' });
        }
        groups.push({ label: 'Établissement', items: establishment });

        const vieScolaire = [];
        if (curriculumItems.length > 0) {
            vieScolaire.push({
                label: "Programme d'études",
                href: '#curriculum',
            });
        }
        if (calendarItems.length > 0) {
            vieScolaire.push({
                label: 'Calendrier scolaire',
                href: '#calendar',
            });
        }
        if (gallery.length > 0) {
            vieScolaire.push({ label: 'Galerie', href: '#gallery' });
        }
        if (vieScolaire.length > 0) {
            groups.push({ label: 'Vie scolaire', items: vieScolaire });
        }

        if (tuitionFees.length > 0) {
            groups.push({ label: 'Frais de scolarité', href: '#fees' });
        }

        groups.push({ label: 'Contact', href: '#contact' });

        return groups;
    }, [
        testimonials.length,
        gallery.length,
        tuitionFees.length,
        curriculumItems.length,
        calendarItems.length,
    ]);

    const flatNav = useMemo(
        () =>
            navGroups.flatMap((group) =>
                group.items ? group.items : [group]
            ),
        [navGroups]
    );

    const address = [school.address, school.city, school.country].filter(Boolean).join(', ');
    const phoneHref = school.phone?.replace(/[^+\d]/g, '');

    const primary = school.primary_color || '#059669';
    const secondary = school.secondary_color || '#2563EB';
    const accent = school.accent_color || '#D97706';

    const externalLinks = [];
    if (school.website) {
        externalLinks.push({ href: school.website, label: 'Visiter le site web' });
    }
    if (school.custom_domain_url) {
        externalLinks.push({ href: school.custom_domain_url, label: 'Site officiel' });
    }

    // Diapositives du hero : image de couverture puis photos de la galerie.
    const slides = useMemo(() => {
        const list = [];

        if (school.hero_image_url) {
            list.push(school.hero_image_url);
        }

        gallery.forEach((item) => {
            if (item.image_url && !list.includes(item.image_url)) {
                list.push(item.image_url);
            }
        });

        return (list.length > 0 ? list : [fallbackHero]).slice(0, 5);
    }, [school.hero_image_url, gallery]);

    const [activeSlide, setActiveSlide] = useState(0);

    useEffect(() => {
        if (activeSlide > slides.length - 1) {
            setActiveSlide(0);
        }
    }, [slides.length, activeSlide]);

    useEffect(() => {
        if (reduceMotion || slides.length <= 1) {
            return undefined;
        }

        const id = setInterval(() => {
            setActiveSlide((current) => (current + 1) % slides.length);
        }, 6500);

        return () => clearInterval(id);
    }, [reduceMotion, slides.length]);

    useEffect(() => {
        if (!lightboxItem) {
            return undefined;
        }

        const closeOnEscape = (event) => {
            if (event.key === 'Escape') setLightboxItem(null);
        };

        document.addEventListener('keydown', closeOnEscape);
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', closeOnEscape);
            document.body.style.overflow = '';
        };
    }, [lightboxItem]);

    const goToSlide = (index) => setActiveSlide(((index % slides.length) + slides.length) % slides.length);
    const nextSlide = () => goToSlide(activeSlide + 1);
    const previousSlide = () => goToSlide(activeSlide - 1);

    return (
        <div
            className="min-h-screen scroll-smooth bg-white text-slate-900"
            style={{
                '--school-primary': primary,
                '--school-secondary': secondary,
                '--school-accent': accent,
            }}
        >
            <Head title={school.meta_title || school.name}>
                <meta name="description" content={school.meta_description} />
                <meta name="robots" content="index, follow" />
                <meta name="theme-color" content={primary} />
                <meta property="og:type" content="website" />
                <meta property="og:site_name" content={school.name} />
                <meta property="og:locale" content="fr_FR" />
                <meta property="og:title" content={school.meta_title || school.name} />
                <meta property="og:description" content={school.meta_description} />
                <meta property="og:image" content={school.hero_image_url || fallbackHero} />
                <meta property="og:image:alt" content={`Aperçu de ${school.name}`} />
                <meta property="og:url" content={school.canonical_url} />
                <meta name="twitter:card" content="summary_large_image" />
                <meta name="twitter:title" content={school.meta_title || school.name} />
                <meta name="twitter:description" content={school.meta_description} />
                <meta name="twitter:image" content={school.hero_image_url || fallbackHero} />
                {school.logo_url && <link rel="icon" href={school.logo_url} />}
                <link rel="canonical" href={school.canonical_url} />
                {structuredData && (
                    <script
                        type="application/ld+json"
                        dangerouslySetInnerHTML={{ __html: JSON.stringify(structuredData) }}
                    />
                )}
            </Head>

            {preview && (
                <div className="relative z-40 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 bg-slate-950 px-4 py-2 text-center text-xs font-medium text-white sm:text-sm">
                    <span>Aperçu du site public — contenu non indexé</span>
                    {preview_dashboard_url && (
                        <Link
                            href={preview_dashboard_url}
                            className="inline-flex items-center gap-1 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white transition hover:bg-white/25"
                        >
                            <ArrowRight className="h-3.5 w-3.5" />
                            Retour au tableau de bord
                        </Link>
                    )}
                </div>
            )}

            <header className="sticky top-0 z-40 border-b border-slate-200/70 bg-white/85 backdrop-blur-xl">
                <motion.div
                    initial={reduceMotion ? false : { y: -24, opacity: 0 }}
                    animate={{ y: 0, opacity: 1 }}
                    transition={{ duration: 0.6, ease: EASE }}
                    className="mx-auto flex h-[78px] max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8"
                >
                    <a href="#home" className="group flex min-w-0 items-center gap-3">
                        {school.logo_url ? (
                            <span className="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white p-1 shadow-sm ring-1 ring-slate-200 transition group-hover:shadow-md">
                                <img src={school.logo_url} alt={`Logo ${school.name}`} className="h-full w-full object-contain" />
                            </span>
                        ) : (
                            <span
                                className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-white shadow-sm"
                                style={{ background: `linear-gradient(135deg, ${primary}, ${secondary})` }}
                            >
                                <GraduationCap className="h-6 w-6" />
                            </span>
                        )}
                        <span className="min-w-0">
                            <span className="block truncate text-sm font-bold tracking-tight text-slate-950 sm:text-base">{school.name}</span>
                            {school.slogan && (
                                <span className="hidden max-w-56 truncate text-xs text-slate-500 sm:block">{school.slogan}</span>
                            )}
                        </span>
                    </a>

                    <nav className="hidden items-center gap-0.5 xl:flex" aria-label="Navigation principale">
                        {navGroups.map((group) =>
                            group.items ? (
                                <NavDropdown key={group.label} group={group} color={primary} />
                            ) : (
                                <a
                                    key={group.href}
                                    href={group.href}
                                    className="group relative whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:text-slate-950"
                                >
                                    {group.label}
                                    <span
                                        className="absolute inset-x-3 -bottom-0.5 h-0.5 origin-left scale-x-0 rounded-full transition-transform duration-300 group-hover:scale-x-100"
                                        style={{ backgroundColor: primary }}
                                    />
                                </a>
                            )
                        )}
                    </nav>
                    <div className="hidden xl:block">
                        <ParentLink color={primary} />
                    </div>
                    {preinscription_url && (
                        <div className="hidden xl:block">
                            <PreinscriptionLink url={preinscription_url} color={accent} />
                        </div>
                    )}
                    <button
                        type="button"
                        onClick={() => setMenuOpen((open) => !open)}
                        className="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-slate-200 text-slate-700 transition hover:bg-slate-50 xl:hidden"
                        aria-label={menuOpen ? 'Fermer le menu' : 'Ouvrir le menu'}
                        aria-expanded={menuOpen}
                    >
                        {menuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                    </button>
                </motion.div>

                <AnimatePresence>
                    {menuOpen && (
                        <motion.nav
                            initial={{ height: 0, opacity: 0 }}
                            animate={{ height: 'auto', opacity: 1 }}
                            exit={{ height: 0, opacity: 0 }}
                            transition={{ duration: 0.3, ease: EASE }}
                            className="overflow-hidden border-t border-slate-100 bg-white xl:hidden"
                            aria-label="Navigation mobile"
                        >
                            <div className="mx-auto grid max-w-7xl gap-1 px-4 py-3 sm:grid-cols-2">
                                {navGroups.map((group) =>
                                    group.items ? (
                                        <div key={group.label} className="sm:col-span-2">
                                            <p className="px-3 pt-2 text-xs font-bold uppercase tracking-wide text-slate-400">
                                                {group.label}
                                            </p>

                                            <div className="mt-1 grid gap-1 sm:grid-cols-2">
                                                {group.items.map((item) => (
                                                    <a
                                                        key={item.href}
                                                        href={item.href}
                                                        onClick={() => setMenuOpen(false)}
                                                        className="rounded-md px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                                                    >
                                                        {item.label}
                                                    </a>
                                                ))}
                                            </div>
                                        </div>
                                    ) : (
                                        <a
                                            key={group.href}
                                            href={group.href}
                                            onClick={() => setMenuOpen(false)}
                                            className="rounded-md px-3 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
                                        >
                                            {group.label}
                                        </a>
                                    )
                                )}
                                <div className="px-3 py-2 sm:col-span-2">
                                    <ParentLink color={primary} />
                                </div>
                                {preinscription_url && (
                                    <div className="px-3 py-2 sm:col-span-2">
                                        <PreinscriptionLink url={preinscription_url} color={accent} />
                                    </div>
                                )}
                            </div>
                        </motion.nav>
                    )}
                </AnimatePresence>
                <div className="h-1 w-full" style={{ background: `linear-gradient(90deg, ${primary}, ${secondary}, ${accent})` }} />
            </header>

            <main>
                {/* ---------------------------------------------------------------- */}
                {/* Hero — diaporama professionnel                                    */}
                {/* ---------------------------------------------------------------- */}
                <section id="home" className="relative isolate flex min-h-[640px] items-center overflow-hidden bg-slate-950 sm:min-h-[720px]">
                    <div className="absolute inset-0 -z-20">
                        <AnimatePresence>
                            <motion.div
                                key={activeSlide}
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                exit={{ opacity: 0 }}
                                transition={{ duration: 1.4, ease: 'easeInOut' }}
                                className="absolute inset-0"
                            >
                                <motion.img
                                    src={slides[activeSlide]}
                                    alt=""
                                    aria-hidden="true"
                                    initial={reduceMotion ? false : { scale: 1.12 }}
                                    animate={{ scale: 1 }}
                                    transition={{ duration: 8, ease: 'linear' }}
                                    className="h-full w-full object-cover"
                                    fetchPriority={activeSlide === 0 ? 'high' : 'low'}
                                />
                            </motion.div>
                        </AnimatePresence>
                    </div>

                    <div className="absolute inset-0 -z-10 bg-gradient-to-r from-slate-950/55 via-slate-950/20 to-transparent" />
                    <div className="absolute inset-0 -z-10 bg-gradient-to-t from-slate-950/40 via-transparent to-slate-950/5" />
                    <div className="pointer-events-none absolute -right-24 top-10 -z-10 h-72 w-72 rounded-full bg-white/10 blur-3xl" />
                    <div className="pointer-events-none absolute -bottom-24 left-1/3 -z-10 h-80 w-80 rounded-full bg-slate-500/20 blur-3xl" />

                    <motion.div
                        variants={stagger}
                        initial={reduceMotion ? false : 'hidden'}
                        animate="visible"
                        className="mx-auto w-full max-w-7xl px-6 py-24 sm:px-8 lg:px-12 lg:py-32"
                    >
                        <div className="max-w-3xl text-white">
                            <motion.div variants={fadeUp} className="mb-6 inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-white/90 backdrop-blur">
                                <School className="h-4 w-4" /> Établissement scolaire
                            </motion.div>
                            <motion.h1 variants={fadeUp} className="text-4xl font-bold leading-[1.05] tracking-tight sm:text-5xl lg:text-6xl">
                                {school.name}
                            </motion.h1>
                            {school.slogan && (
                                <motion.p variants={fadeUp} className="mt-5 max-w-2xl text-xl font-semibold leading-8 text-white/90 sm:text-2xl">
                                    {school.slogan}
                                </motion.p>
                            )}
                            {school.description && (
                                <motion.p variants={fadeUp} className="mt-4 max-w-2xl text-base leading-7 text-white/80 sm:text-lg">
                                    {school.description}
                                </motion.p>
                            )}
                            <motion.div variants={fadeUp} className="mt-9 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                                {preinscription_url && (
                                    <Link
                                        href={preinscription_url}
                                        className="group inline-flex min-h-12 items-center justify-center gap-2 rounded-xl px-6 text-sm font-semibold text-white shadow-lg shadow-black/20 transition hover:-translate-y-0.5 hover:brightness-110"
                                        style={{ backgroundColor: accent }}
                                    >
                                        <UserPlus className="h-4 w-4" />
                                        Inscrire mon enfant
                                        <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                                    </Link>
                                )}
                                <a
                                    href="#about"
                                    className="group inline-flex min-h-12 items-center justify-center gap-2 rounded-xl px-6 text-sm font-semibold text-white shadow-lg shadow-black/20 transition hover:-translate-y-0.5 hover:brightness-110"
                                    style={{ backgroundColor: primary }}
                                >
                                    Découvrir notre établissement
                                    <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                                </a>
                                {externalLinks.map((link) => (
                                    <a
                                        key={link.href}
                                        href={link.href}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-white/40 bg-white/5 px-6 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/15"
                                    >
                                        <ExternalLink className="h-4 w-4" /> {link.label}
                                    </a>
                                ))}
                                <ParentLink color={withAlpha('#ffffff', 0.16)} light />
                            </motion.div>
                        </div>
                    </motion.div>

                    {slides.length > 1 && (
                        <>
                            <button
                                type="button"
                                onClick={previousSlide}
                                aria-label="Visuel précédent"
                                className="absolute left-3 top-1/2 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/30 bg-black/20 text-white backdrop-blur transition hover:bg-black/40 lg:flex"
                            >
                                <ChevronLeft className="h-5 w-5" />
                            </button>
                            <button
                                type="button"
                                onClick={nextSlide}
                                aria-label="Visuel suivant"
                                className="absolute right-3 top-1/2 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/30 bg-black/20 text-white backdrop-blur transition hover:bg-black/40 lg:flex"
                            >
                                <ChevronRight className="h-5 w-5" />
                            </button>
                            <div className="absolute bottom-8 left-1/2 flex -translate-x-1/2 items-center gap-2">
                                {slides.map((slide, index) => (
                                    <button
                                        key={`${slide}-${index}`}
                                        type="button"
                                        onClick={() => goToSlide(index)}
                                        aria-label={`Aller au visuel ${index + 1}`}
                                        aria-current={index === activeSlide}
                                        className="h-2 rounded-full transition-all duration-300"
                                        style={{
                                            width: index === activeSlide ? 30 : 10,
                                            backgroundColor: index === activeSlide ? accent : 'rgba(255,255,255,0.5)',
                                        }}
                                    />
                                ))}
                            </div>
                        </>
                    )}

                    {!school.hero_image_url && slides.length === 1 && (
                        <span className="absolute bottom-3 right-4 text-[10px] text-white/60">Image d’illustration</span>
                    )}

                    <motion.a
                        href="#about"
                        animate={reduceMotion ? {} : { y: [0, 8, 0] }}
                        transition={{ repeat: Infinity, duration: 1.8, ease: 'easeInOut' }}
                        className="absolute bottom-7 left-1/2 hidden -translate-x-1/2 items-center gap-2 text-xs font-medium text-white/80 sm:flex"
                    >
                        Faire défiler <ChevronDown className="h-4 w-4" />
                    </motion.a>
                </section>

                {/* ---------------------------------------------------------------- */}
                {/* À propos                                                          */}
                {/* ---------------------------------------------------------------- */}
                <section id="about" className="scroll-mt-24 bg-white py-20 sm:py-24">
                    <div className="mx-auto grid max-w-7xl gap-12 px-5 sm:px-8 lg:grid-cols-[0.85fr_1.15fr] lg:px-12">
                        <motion.div variants={stagger} initial="hidden" whileInView="visible" viewport={revealViewport}>
                            <motion.div variants={fadeUp}>
                                <SectionEyebrow color={primary}>À propos</SectionEyebrow>
                            </motion.div>
                            <motion.h2 variants={fadeUp} className="mt-4 max-w-xl text-3xl font-bold leading-tight tracking-tight text-slate-950 sm:text-4xl">
                                {school.name}, un établissement tourné vers l’avenir de chaque élève.
                            </motion.h2>
                            {school.founded_date && (
                                <motion.p variants={fadeUp} className="mt-6 inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-600">
                                    <Clock3 className="h-4 w-4" style={{ color: primary }} /> École créée le {formatLongDate(school.founded_date)}
                                </motion.p>
                            )}
                        </motion.div>
                        <motion.div variants={stagger} initial="hidden" whileInView="visible" viewport={revealViewport} className="space-y-8">
                            <motion.p variants={fadeUp} className="text-lg leading-8 text-slate-600">
                                {school.description || 'Les informations de présentation de l’établissement seront bientôt disponibles.'}
                            </motion.p>
                            {(school.mission || school.vision) && (
                                <div className="grid gap-5 sm:grid-cols-2">
                                    {school.mission && <InfoBlock variants={fadeUp} icon={Target} label="Notre mission" text={school.mission} color={primary} />}
                                    {school.vision && <InfoBlock variants={fadeUp} icon={Sparkles} label="Notre vision" text={school.vision} color={secondary} />}
                                </div>
                            )}
                            {school.values?.length > 0 && (
                                <motion.div variants={fadeUp}>
                                    <h3 className="mb-3 text-sm font-bold uppercase tracking-wide text-slate-500">Nos valeurs</h3>
                                    <ul className="flex flex-wrap gap-2">
                                        {school.values.map((value) => (
                                            <li
                                                key={value}
                                                className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300"
                                            >
                                                <CheckCircle2 className="h-4 w-4" style={{ color: primary }} />
                                                {value}
                                            </li>
                                        ))}
                                    </ul>
                                </motion.div>
                            )}
                        </motion.div>
                    </div>
                </section>

                {/* ---------------------------------------------------------------- */}
                {/* Impact                                                            */}
                {/* ---------------------------------------------------------------- */}
                <section id="impact" className="relative scroll-mt-24 overflow-hidden border-y border-slate-200 bg-slate-50 py-16 sm:py-20">
                    <div className="pointer-events-none absolute inset-0 opacity-[0.5]" style={{ background: `radial-gradient(60% 60% at 100% 0%, ${withAlpha(primary, 0.12)}, transparent 70%)` }} />
                    <div className="relative mx-auto max-w-7xl px-5 sm:px-8 lg:px-12">
                        <motion.div variants={stagger} initial="hidden" whileInView="visible" viewport={revealViewport} className="mb-10 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                            <motion.div variants={fadeUp}>
                                <SectionEyebrow color={primary}>Notre impact</SectionEyebrow>
                                <h2 className="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Une communauté qui grandit ensemble</h2>
                            </motion.div>
                            <motion.p variants={fadeUp} className="max-w-md text-sm leading-6 text-slate-500">
                                Chiffres calculés à partir des données actuelles de l’établissement.
                            </motion.p>
                        </motion.div>
                        <motion.div
                            variants={stagger}
                            initial="hidden"
                            whileInView="visible"
                            viewport={revealViewport}
                            className="grid grid-cols-2 gap-4 sm:grid-cols-4"
                        >
                            <StatCounter icon={GraduationCap} label="Élèves actifs" value={stats.students} color={primary} />
                            <StatCounter icon={HeartHandshake} label="Enseignants" value={stats.teachers} color={secondary} />
                            <StatCounter icon={BookOpenCheck} label="Classes" value={stats.classes} color={accent} />
                            {stats.years_experience !== null && (
                                <StatCounter icon={Clock3} label="Années d’expérience" value={stats.years_experience} color={primary} />
                            )}
                        </motion.div>
                    </div>
                </section>

                {school.results?.length > 0 && (
                    <section className="scroll-mt-24 bg-white py-12 sm:py-14">
                        <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-12">
                            <motion.div
                                variants={fadeUp}
                                initial="hidden"
                                whileInView="visible"
                                viewport={revealViewport}
                                className="text-center"
                            >
                                <SectionEyebrow color={accent}>Palmarès &amp; résultats</SectionEyebrow>
                            </motion.div>

                            <motion.div
                                variants={stagger}
                                initial="hidden"
                                whileInView="visible"
                                viewport={revealViewport}
                                className={`mx-auto mt-6 grid gap-4 ${school.results.length === 1 ? 'max-w-2xl' : 'sm:grid-cols-2 lg:grid-cols-3'}`}
                            >
                                {school.results.map((result, index) => (
                                    <motion.div
                                        key={`${result.title}-${index}`}
                                        variants={fadeUp}
                                        className="rounded-3xl border px-6 py-8 text-center"
                                        style={{
                                            borderColor: withAlpha(primary, 0.2),
                                            background: `linear-gradient(135deg, ${withAlpha(primary, 0.08)}, ${withAlpha(accent, 0.08)})`,
                                        }}
                                    >
                                        <p className="text-2xl font-extrabold tracking-tight text-slate-950 sm:text-3xl">
                                            {result.title}
                                        </p>
                                        {result.note && (
                                            <p className="mt-2 text-sm font-medium text-slate-500">{result.note}</p>
                                        )}
                                    </motion.div>
                                ))}
                            </motion.div>
                        </div>
                    </section>
                )}

                {/* ---------------------------------------------------------------- */}
                {/* Points forts                                                      */}
                {/* ---------------------------------------------------------------- */}
                <section id="strengths" className="scroll-mt-24 py-20 sm:py-24">
                    <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-12">
                        <motion.div variants={stagger} initial="hidden" whileInView="visible" viewport={revealViewport} className="max-w-2xl">
                            <motion.div variants={fadeUp}>
                                <SectionEyebrow color={primary}>Notre approche</SectionEyebrow>
                            </motion.div>
                            <motion.h2 variants={fadeUp} className="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                                Pourquoi choisir notre établissement ?
                            </motion.h2>
                        </motion.div>

                        {features.length > 0 ? (
                            <motion.div
                                variants={stagger}
                                initial="hidden"
                                whileInView="visible"
                                viewport={revealViewport}
                                className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                {features.map((feature, index) => (
                                    <FeatureCard
                                        key={`${feature.title}-${index}`}
                                        feature={feature}
                                        color={index % 2 ? secondary : primary}
                                        accent={accent}
                                    />
                                ))}
                            </motion.div>
                        ) : (
                            <p className="mt-8 max-w-2xl border-l-2 border-slate-300 pl-4 text-sm leading-6 text-slate-500">
                                Les points forts de l’établissement seront présentés ici dès qu’ils auront été renseignés.
                            </p>
                        )}
                    </div>
                </section>

                {/* ---------------------------------------------------------------- */}
                {/* Programme d'études                                                */}
                {/* ---------------------------------------------------------------- */}
                {curriculumItems.length > 0 && (
                    <section id="curriculum" className="scroll-mt-24 border-y border-slate-200 bg-slate-50 py-20 sm:py-24">
                        <div className="mx-auto max-w-4xl px-5 sm:px-8 lg:px-12">
                            <motion.div variants={stagger} initial="hidden" whileInView="visible" viewport={revealViewport} className="max-w-2xl">
                                <motion.div variants={fadeUp}>
                                    <SectionEyebrow color={primary}>Vie scolaire</SectionEyebrow>
                                </motion.div>
                                <motion.h2 variants={fadeUp} className="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                                    Notre programme d’études
                                </motion.h2>
                                {curriculum?.intro && (
                                    <motion.p variants={fadeUp} className="mt-4 text-base leading-7 text-slate-600">
                                        {curriculum.intro}
                                    </motion.p>
                                )}
                            </motion.div>

                            <motion.div
                                variants={stagger}
                                initial="hidden"
                                whileInView="visible"
                                viewport={revealViewport}
                                className="mt-10 space-y-3"
                            >
                                {curriculumItems.map((item, index) => (
                                    <CurriculumRow
                                        key={`${item.title}-${index}`}
                                        item={item}
                                        index={index}
                                        open={openCurriculum === index}
                                        onToggle={() =>
                                            setOpenCurriculum(openCurriculum === index ? -1 : index)
                                        }
                                        color={primary}
                                        accent={accent}
                                    />
                                ))}
                            </motion.div>
                        </div>
                    </section>
                )}

                {/* ---------------------------------------------------------------- */}
                {/* Calendrier scolaire                                               */}
                {/* ---------------------------------------------------------------- */}
                {calendarItems.length > 0 && (
                    <section id="calendar" className="scroll-mt-24 py-20 sm:py-24">
                        <div className="mx-auto max-w-4xl px-5 sm:px-8 lg:px-12">
                            <motion.div variants={stagger} initial="hidden" whileInView="visible" viewport={revealViewport} className="max-w-2xl">
                                <motion.div variants={fadeUp}>
                                    <SectionEyebrow color={accent}>Vie scolaire</SectionEyebrow>
                                </motion.div>
                                <motion.h2 variants={fadeUp} className="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                                    Calendrier scolaire
                                </motion.h2>
                                {calendar?.intro && (
                                    <motion.p variants={fadeUp} className="mt-4 text-base leading-7 text-slate-600">
                                        {calendar.intro}
                                    </motion.p>
                                )}
                            </motion.div>

                            <motion.ol
                                variants={stagger}
                                initial="hidden"
                                whileInView="visible"
                                viewport={revealViewport}
                                className="mt-10 space-y-4"
                            >
                                {calendarItems.map((item, index) => (
                                    <motion.li
                                        key={`${item.label}-${index}`}
                                        variants={fadeUp}
                                        className="group flex gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                                    >
                                        <span
                                            className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                                            style={{ backgroundColor: withAlpha(accent, 0.12), color: accent }}
                                        >
                                            <CalendarDays className="h-5 w-5" />
                                        </span>
                                        <div className="min-w-0">
                                            {item.date && (
                                                <p className="text-xs font-bold uppercase tracking-wide" style={{ color: accent }}>
                                                    {item.date}
                                                </p>
                                            )}
                                            <h3 className="mt-0.5 text-base font-bold text-slate-900">{item.label}</h3>
                                            {item.description && (
                                                <p className="mt-1 text-sm leading-6 text-slate-600">{item.description}</p>
                                            )}
                                        </div>
                                    </motion.li>
                                ))}
                            </motion.ol>
                        </div>
                    </section>
                )}

                {/* ---------------------------------------------------------------- */}
                {/* Frais de scolarité                                                */}
                {/* ---------------------------------------------------------------- */}
                {tuitionFees.length > 0 && (
                    <section id="fees" className="scroll-mt-24 border-y border-slate-200 bg-slate-50 py-20 sm:py-24">
                        <div className="relative mx-auto max-w-7xl px-5 sm:px-8 lg:px-12">
                            <div className="pointer-events-none absolute inset-0 opacity-[0.5]" style={{ background: `radial-gradient(60% 60% at 0% 0%, ${withAlpha(primary, 0.1)}, transparent 70%)` }} />

                            <motion.div variants={stagger} initial="hidden" whileInView="visible" viewport={revealViewport} className="relative max-w-2xl">
                                <motion.div variants={fadeUp}>
                                    <SectionEyebrow color={primary}>Admission</SectionEyebrow>
                                </motion.div>
                                <motion.h2 variants={fadeUp} className="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                                    Frais de scolarité
                                </motion.h2>
                                <motion.p variants={fadeUp} className="mt-4 text-base leading-7 text-slate-600">
                                    Des frais présentés en toute transparence pour accompagner chaque famille.
                                </motion.p>
                            </motion.div>

                            <motion.div
                                variants={stagger}
                                initial="hidden"
                                whileInView="visible"
                                viewport={revealViewport}
                                className="relative mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                {tuitionFees.map((fee, index) => (
                                    <motion.article
                                        key={`${fee.label}-${index}`}
                                        variants={fadeUp}
                                        className="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md"
                                    >
                                        <span className="absolute inset-x-0 top-0 h-1" style={{ background: `linear-gradient(90deg, ${primary}, ${accent})` }} />
                                        <span
                                            className="flex h-11 w-11 items-center justify-center rounded-xl"
                                            style={{ backgroundColor: withAlpha(primary, 0.12), color: primary }}
                                        >
                                            <Coins className="h-5 w-5" />
                                        </span>
                                        <h3 className="mt-4 text-base font-bold text-slate-900">{fee.label}</h3>
                                        {fee.amount && (
                                            <p className="mt-2 flex items-baseline gap-1.5 text-2xl font-extrabold tracking-tight text-slate-950">
                                                {fee.amount}
                                                {tuition?.currency && (
                                                    <span className="text-sm font-semibold text-slate-500">{tuition.currency}</span>
                                                )}
                                            </p>
                                        )}
                                        {fee.note && (
                                            <p className="mt-2 text-sm leading-6 text-slate-500">{fee.note}</p>
                                        )}
                                    </motion.article>
                                ))}
                            </motion.div>

                            {tuition?.note && (
                                <motion.p
                                    variants={fadeUp}
                                    initial="hidden"
                                    whileInView="visible"
                                    viewport={revealViewport}
                                    className="relative mx-auto mt-8 max-w-3xl rounded-2xl border border-slate-200 bg-white px-5 py-4 text-center text-sm leading-6 text-slate-600"
                                >
                                    {tuition.note}
                                </motion.p>
                            )}
                        </div>
                    </section>
                )}

                {/* ---------------------------------------------------------------- */}
                {/* Témoignages                                                       */}
                {/* ---------------------------------------------------------------- */}
                {testimonials.length > 0 && (
                    <section id="testimonials" className="relative scroll-mt-24 overflow-hidden py-20 text-white sm:py-24" style={{ backgroundColor: '#020617' }}>
                        <div className="pointer-events-none absolute inset-0" style={{ background: `radial-gradient(70% 70% at 15% 0%, ${withAlpha(primary, 0.35)}, transparent 60%), radial-gradient(60% 60% at 100% 100%, ${withAlpha(secondary, 0.3)}, transparent 60%)` }} />
                        <div className="relative mx-auto max-w-7xl px-5 sm:px-8 lg:px-12">
                            <motion.div variants={stagger} initial="hidden" whileInView="visible" viewport={revealViewport} className="max-w-2xl">
                                <motion.div variants={fadeUp}>
                                    <SectionEyebrow color={accent}>Témoignages</SectionEyebrow>
                                </motion.div>
                                <motion.h2 variants={fadeUp} className="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Ils parlent de nous</motion.h2>
                            </motion.div>
                            <motion.div
                                variants={stagger}
                                initial="hidden"
                                whileInView="visible"
                                viewport={revealViewport}
                                className="mt-12 grid gap-5 lg:grid-cols-2"
                            >
                                {testimonials.map((testimonial, index) => (
                                    <TestimonialCard
                                        key={`${testimonial.name}-${index}`}
                                        testimonial={testimonial}
                                        accent={accent}
                                        secondary={secondary}
                                    />
                                ))}
                            </motion.div>
                        </div>
                    </section>
                )}

                {/* ---------------------------------------------------------------- */}
                {/* Galerie                                                           */}
                {/* ---------------------------------------------------------------- */}
                {gallery.length > 0 && (
                    <section id="gallery" className="scroll-mt-24 py-20 sm:py-24">
                        <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-12">
                            <motion.div variants={stagger} initial="hidden" whileInView="visible" viewport={revealViewport} className="flex items-end justify-between gap-4">
                                <motion.div variants={fadeUp}>
                                    <SectionEyebrow color={primary}>Galerie</SectionEyebrow>
                                    <h2 className="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">La vie de notre établissement</h2>
                                </motion.div>
                                <Images className="hidden h-8 w-8 text-slate-300 sm:block" />
                            </motion.div>
                            <motion.div
                                variants={stagger}
                                initial="hidden"
                                whileInView="visible"
                                viewport={revealViewport}
                                className="mt-9 grid auto-rows-[220px] gap-3 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                {gallery.map((item, index) => (
                                    <motion.button
                                        key={`${item.image_url}-${index}`}
                                        variants={fadeUp}
                                        type="button"
                                        onClick={() => setLightboxItem(item)}
                                        className={`group relative block w-full overflow-hidden rounded-2xl bg-slate-100 text-left ${index === 0 ? 'sm:row-span-2 sm:h-full' : 'h-full'}`}
                                        aria-label={`Agrandir : ${item.alt_text}`}
                                    >
                                        <img
                                            src={item.image_url}
                                            alt={item.alt_text}
                                            loading="lazy"
                                            className="h-full w-full object-cover transition duration-700 ease-out group-hover:scale-[1.06]"
                                        />
                                        <span className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/0 to-transparent opacity-0 transition duration-500 group-hover:opacity-100" />
                                        {(item.title || item.description) && (
                                            <span className="absolute inset-x-0 bottom-0 translate-y-2 p-4 text-white opacity-0 transition duration-500 group-hover:translate-y-0 group-hover:opacity-100">
                                                {item.title && <span className="block font-semibold">{item.title}</span>}
                                                {item.description && <span className="mt-1 block text-sm text-white/80">{item.description}</span>}
                                            </span>
                                        )}
                                    </motion.button>
                                ))}
                            </motion.div>
                        </div>
                    </section>
                )}

                {/* ---------------------------------------------------------------- */}
                {/* Contact                                                           */}
                {/* ---------------------------------------------------------------- */}
                <section id="contact" className="scroll-mt-24 border-t border-slate-200 bg-slate-50 py-20 sm:py-24">
                    <div className="mx-auto grid max-w-7xl gap-10 px-5 sm:px-8 lg:grid-cols-[0.8fr_1.2fr] lg:px-12">
                        <motion.div variants={stagger} initial="hidden" whileInView="visible" viewport={revealViewport}>
                            <motion.div variants={fadeUp}>
                                <SectionEyebrow color={primary}>Contact</SectionEyebrow>
                            </motion.div>
                            <motion.h2 variants={fadeUp} className="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Nous contacter</motion.h2>
                            <motion.p variants={fadeUp} className="mt-4 max-w-md text-base leading-7 text-slate-600">
                                Pour toute information sur notre établissement, contactez-nous par téléphone ou par email.
                            </motion.p>
                        </motion.div>
                        <motion.div
                            variants={stagger}
                            initial="hidden"
                            whileInView="visible"
                            viewport={revealViewport}
                            className="grid gap-4 sm:grid-cols-2"
                        >
                            {address && (
                                <ContactItem
                                    variants={fadeUp}
                                    icon={MapPin}
                                    label="Adresse"
                                    value={address}
                                    href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`}
                                    external
                                    color={primary}
                                />
                            )}
                            {school.phone && <ContactItem variants={fadeUp} icon={Phone} label="Téléphone" value={school.phone} href={`tel:${phoneHref}`} color={primary} />}
                            {school.email && <ContactItem variants={fadeUp} icon={Mail} label="Email" value={school.email} href={`mailto:${school.email}`} color={primary} />}
                            {school.website && <ContactItem variants={fadeUp} icon={ArrowRight} label="Site web" value={school.website} href={school.website} external color={primary} />}
                            {school.custom_domain_url && (
                                <ContactItem
                                    variants={fadeUp}
                                    icon={ExternalLink}
                                    label="Site officiel"
                                    value={school.custom_domain_url.replace(/^https?:\/\//, '')}
                                    href={school.custom_domain_url}
                                    external
                                    color={primary}
                                />
                            )}
                            {!address && !school.phone && !school.email && !school.website && !school.custom_domain_url && (
                                <p className="text-sm text-slate-500">Les coordonnées de l’établissement seront bientôt disponibles.</p>
                            )}
                        </motion.div>
                    </div>
                </section>
            </main>

            <section id="corischool" className="relative overflow-hidden py-16 text-white sm:py-20" style={{ background: `linear-gradient(120deg, ${primary}, ${secondary} 55%, ${accent})` }}>
                <div className="pointer-events-none absolute inset-0 opacity-30" style={{ backgroundImage: 'radial-gradient(circle at 1px 1px, rgba(255,255,255,0.35) 1px, transparent 0)', backgroundSize: '22px 22px' }} />
                <motion.div
                    variants={stagger}
                    initial="hidden"
                    whileInView="visible"
                    viewport={revealViewport}
                    className="relative mx-auto flex max-w-7xl flex-col gap-6 px-5 sm:px-8 lg:flex-row lg:items-center lg:justify-between lg:px-12"
                >
                    <motion.div variants={fadeUp}>
                        <p className="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.18em] text-white/85">
                            <Sparkles className="h-4 w-4" /> CoriSchool
                        </p>
                        <h2 className="mt-3 max-w-2xl text-2xl font-bold sm:text-3xl">Vous dirigez un établissement scolaire ?</h2>
                        <p className="mt-2 max-w-2xl text-sm leading-6 text-white/80 sm:text-base">
                            Offrez à votre école une vitrine professionnelle et pilotez élèves, notes, présences et finances au même endroit. Créez votre site public avec CoriSchool.
                        </p>
                    </motion.div>
                    <motion.div variants={fadeUp} className="flex shrink-0 flex-col gap-3 sm:flex-row">
                        {school.demo_url && (
                            <a
                                href={school.demo_url}
                                className="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-white px-6 text-sm font-bold shadow-lg shadow-black/10 transition hover:-translate-y-0.5"
                                style={{ color: primary }}
                            >
                                Demander une démonstration <ArrowRight className="h-4 w-4" />
                            </a>
                        )}
                        {school.platform_url && (
                            <a
                                href={school.platform_url}
                                className="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-white/50 px-6 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/10"
                            >
                                Découvrir CoriSchool
                            </a>
                        )}
                    </motion.div>
                </motion.div>
            </section>

            <footer className="bg-slate-950 py-12 text-white">
                <div className="mx-auto grid max-w-7xl gap-10 px-5 sm:px-8 md:grid-cols-3 lg:px-12">
                    <div>
                        <a href="#home" className="flex items-center gap-3">
                            {school.logo_url ? (
                                <span className="flex h-11 w-11 items-center justify-center rounded-lg bg-white/95 p-1">
                                    <img src={school.logo_url} alt={`Logo ${school.name}`} className="h-full w-full object-contain" />
                                </span>
                            ) : (
                                <GraduationCap className="h-9 w-9" style={{ color: accent }} />
                            )}
                            <span className="font-bold">{school.name}</span>
                        </a>
                        {school.description && <p className="mt-4 max-w-sm text-sm leading-6 text-white/65">{school.description}</p>}
                    </div>
                    <div>
                        <h2 className="text-sm font-semibold">Navigation</h2>
                        <div className="mt-4 grid grid-cols-2 gap-3 text-sm text-white/65">
                            {flatNav.slice(0, 8).map((item) => (
                                <a key={item.href} href={item.href} className="transition hover:text-white">{item.label}</a>
                            ))}
                        </div>
                    </div>
                    <div>
                        <h2 className="text-sm font-semibold">Coordonnées</h2>
                        <div className="mt-4 space-y-2 text-sm text-white/65">
                            {address && <p>{address}</p>}
                            {school.phone && <a href={`tel:${phoneHref}`} className="block transition hover:text-white">{school.phone}</a>}
                            {school.email && <a href={`mailto:${school.email}`} className="block transition hover:text-white">{school.email}</a>}
                        </div>
                    </div>
                </div>
                <div className="mx-auto mt-10 flex max-w-7xl flex-col gap-2 border-t border-white/10 px-5 pt-5 text-xs text-white/50 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-12">
                    <span>© {new Date().getFullYear()} {school.name}</span>
                    {school.platform_url ? (
                        <a href={school.platform_url} className="inline-flex items-center gap-1.5 transition hover:text-white/80" rel="noreferrer">
                            <GraduationCap className="h-3.5 w-3.5" /> Propulsé par <span className="font-semibold text-white/75">CoriSchool</span>
                        </a>
                    ) : null}
                </div>
            </footer>

            <AnimatePresence>
                {lightboxItem && (
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/95 p-4"
                        role="dialog"
                        aria-modal="true"
                        aria-label={lightboxItem.alt_text}
                        onClick={() => setLightboxItem(null)}
                    >
                        <button
                            type="button"
                            onClick={() => setLightboxItem(null)}
                            className="absolute right-4 top-4 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20"
                            aria-label="Fermer l’image"
                        >
                            <X className="h-5 w-5" />
                        </button>
                        <motion.figure
                            initial={{ scale: 0.94, opacity: 0 }}
                            animate={{ scale: 1, opacity: 1 }}
                            exit={{ scale: 0.96, opacity: 0 }}
                            transition={{ duration: 0.3, ease: EASE }}
                            className="max-h-full max-w-6xl"
                            onClick={(event) => event.stopPropagation()}
                        >
                            <img src={lightboxItem.image_url} alt={lightboxItem.alt_text} className="max-h-[78vh] max-w-full rounded-lg object-contain" />
                            {(lightboxItem.title || lightboxItem.description) && (
                                <figcaption className="mt-3 text-center text-sm text-white/80">
                                    {lightboxItem.title}
                                    {lightboxItem.title && lightboxItem.description ? ' · ' : ''}
                                    {lightboxItem.description}
                                </figcaption>
                            )}
                        </motion.figure>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
}

/* -------------------------------------------------------------------------- */
/* Sous-composants                                                            */
/* -------------------------------------------------------------------------- */

function ParentLink({ color, light = false }) {
    return (
        <Link
            href={route('login')}
            className={`inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:brightness-110 ${light ? 'border border-white/35 backdrop-blur' : 'shadow-sm'}`}
            style={{ backgroundColor: color }}
        >
            <GraduationCap className="h-4 w-4" /> Espace parent
        </Link>
    );
}

function PreinscriptionLink({ url, color }) {
    return (
        <Link
            href={url}
            className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:brightness-110"
            style={{ backgroundColor: color }}
        >
            <UserPlus className="h-4 w-4" /> Inscrire mon enfant
        </Link>
    );
}

function NavDropdown({ group, color }) {
    return (
        <div className="group relative">
            <button
                type="button"
                aria-haspopup="true"
                className="relative flex items-center gap-1 whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:text-slate-950"
            >
                {group.label}
                <ChevronDown className="h-3.5 w-3.5 transition-transform duration-200 group-hover:rotate-180" />
                <span
                    className="absolute inset-x-3 -bottom-0.5 h-0.5 origin-left scale-x-0 rounded-full transition-transform duration-300 group-hover:scale-x-100"
                    style={{ backgroundColor: color }}
                />
            </button>

            <div className="invisible absolute left-0 top-full z-50 w-60 translate-y-1 pt-2 opacity-0 transition duration-200 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100 group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:opacity-100">
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl shadow-slate-900/10">
                    {group.items.map((item) => (
                        <a
                            key={item.href}
                            href={item.href}
                            className="block rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-950"
                        >
                            {item.label}
                        </a>
                    ))}
                </div>
            </div>
        </div>
    );
}

function CurriculumRow({ item, index, open, onToggle, color, accent }) {
    const hasDescription = Boolean(item.description);

    return (
        <motion.div
            variants={fadeUp}
            className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
        >
            <button
                type="button"
                onClick={hasDescription ? onToggle : undefined}
                aria-expanded={hasDescription ? open : undefined}
                className={`flex w-full items-center gap-4 px-5 py-4 text-left transition ${hasDescription ? 'hover:bg-slate-50' : 'cursor-default'}`}
            >
                <span
                    className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white shadow-sm"
                    style={{ background: `linear-gradient(135deg, ${color}, ${accent})` }}
                >
                    {index + 1}
                </span>
                <span className="flex-1 text-base font-bold text-slate-900">{item.title}</span>
                {hasDescription && (
                    <ChevronDown
                        className={`h-5 w-5 shrink-0 text-slate-400 transition-transform duration-300 ${open ? 'rotate-180' : ''}`}
                    />
                )}
            </button>

            <AnimatePresence initial={false}>
                {open && hasDescription && (
                    <motion.div
                        initial={{ height: 0, opacity: 0 }}
                        animate={{ height: 'auto', opacity: 1 }}
                        exit={{ height: 0, opacity: 0 }}
                        transition={{ duration: 0.3, ease: EASE }}
                        className="overflow-hidden"
                    >
                        <p className="px-5 pb-5 pl-[4.5rem] text-sm leading-7 text-slate-600">
                            {item.description}
                        </p>
                    </motion.div>
                )}
            </AnimatePresence>
        </motion.div>
    );
}

function SectionEyebrow({ color, children }) {
    return (
        <span className="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em]" style={{ color }}>
            <span className="h-1.5 w-1.5 rounded-full" style={{ backgroundColor: color }} />
            {children}
        </span>
    );
}

function InfoBlock({ icon: Icon, label, text, color, variants }) {
    return (
        <motion.article variants={variants} className="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
            <span className="absolute inset-x-0 top-0 h-1" style={{ background: `linear-gradient(90deg, ${color}, transparent)` }} />
            <span className="flex h-11 w-11 items-center justify-center rounded-xl" style={{ backgroundColor: withAlpha(color, 0.12), color }}>
                <Icon className="h-5 w-5" />
            </span>
            <h3 className="mt-4 text-sm font-bold text-slate-900">{label}</h3>
            <p className="mt-2 text-sm leading-6 text-slate-600">{text}</p>
        </motion.article>
    );
}

function FeatureCard({ feature, color, accent }) {
    return (
        <motion.article
            variants={fadeUp}
            whileHover={{ y: -6 }}
            transition={{ type: 'spring', stiffness: 300, damping: 22 }}
            className="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        >
            <span className="absolute -right-8 -top-8 h-24 w-24 rounded-full opacity-0 blur-2xl transition duration-300 group-hover:opacity-60" style={{ backgroundColor: withAlpha(color, 0.4) }} />
            <span className="relative flex h-12 w-12 items-center justify-center rounded-xl text-white shadow-sm" style={{ background: `linear-gradient(135deg, ${color}, ${withAlpha(accent, 1)})` }}>
                <ShieldCheck className="h-6 w-6" />
            </span>
            <h3 className="relative mt-5 text-lg font-bold text-slate-900">{feature.title}</h3>
            {feature.description && <p className="relative mt-2 text-sm leading-6 text-slate-600">{feature.description}</p>}
        </motion.article>
    );
}

function TestimonialCard({ testimonial, accent, secondary }) {
    return (
        <motion.figure
            variants={fadeUp}
            whileHover={{ y: -4 }}
            transition={{ type: 'spring', stiffness: 300, damping: 22 }}
            className="flex h-full flex-col rounded-2xl border border-white/10 bg-white/[0.06] p-7 backdrop-blur-sm"
        >
            <Quote className="h-8 w-8" style={{ color: accent }} />
            <blockquote className="mt-4 flex-1 text-lg leading-8 text-white/90">“{testimonial.message}”</blockquote>
            <figcaption className="mt-6 flex items-center gap-3 border-t border-white/10 pt-5">
                {testimonial.photo_url ? (
                    <img src={testimonial.photo_url} alt="" loading="lazy" className="h-11 w-11 rounded-full object-cover ring-2 ring-white/20" />
                ) : (
                    <span className="flex h-11 w-11 items-center justify-center rounded-full text-sm font-bold text-white" style={{ background: `linear-gradient(135deg, ${accent}, ${secondary})` }}>
                        {testimonial.name.slice(0, 1).toUpperCase()}
                    </span>
                )}
                <span>
                    <span className="block text-sm font-semibold text-white">{testimonial.name}</span>
                    {testimonial.relationship && <span className="mt-0.5 block text-xs text-white/60">{testimonial.relationship}</span>}
                </span>
            </figcaption>
        </motion.figure>
    );
}

function StatCounter({ icon: Icon, label, value, color }) {
    const ref = useRef(null);
    const inView = useInView(ref, { once: true, amount: 0.4 });
    const reduceMotion = useReducedMotion();
    const target = Number(value) || 0;

    const [display, setDisplay] = useState(reduceMotion ? target : 0);

    useEffect(() => {
        if (!inView) {
            return undefined;
        }

        if (reduceMotion) {
            setDisplay(target);

            return undefined;
        }

        let frame;
        const start = performance.now();
        const duration = 1600;

        const tick = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            setDisplay(Math.round(target * eased));

            if (progress < 1) {
                frame = requestAnimationFrame(tick);
            }
        };

        frame = requestAnimationFrame(tick);

        return () => cancelAnimationFrame(frame);
    }, [inView, target, reduceMotion]);

    return (
        <motion.div
            ref={ref}
            variants={fadeUp}
            className="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md sm:p-6"
        >
            <span className="absolute inset-x-0 top-0 h-1" style={{ backgroundColor: color }} />
            <span className="flex h-10 w-10 items-center justify-center rounded-xl" style={{ backgroundColor: withAlpha(color, 0.12), color }}>
                <Icon className="h-5 w-5" />
            </span>
            <p className="mt-4 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                {display.toLocaleString('fr-FR')}
            </p>
            <p className="mt-1 text-sm text-slate-500">{label}</p>
        </motion.div>
    );
}

function ContactItem({ icon: Icon, label, value, href, external = false, color, variants }) {
    return (
        <motion.a
            variants={variants}
            href={href}
            target={external ? '_blank' : undefined}
            rel={external ? 'noreferrer' : undefined}
            className="group flex min-h-24 items-start gap-4 rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
        >
            <span className="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" style={{ backgroundColor: withAlpha(color, 0.12), color }}>
                <Icon className="h-5 w-5" />
            </span>
            <span className="min-w-0">
                <span className="block text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</span>
                <span className="mt-1 block break-words text-sm font-medium text-slate-900">{value}</span>
            </span>
        </motion.a>
    );
}
