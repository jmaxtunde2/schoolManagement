import { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BookOpenCheck,
    CheckCircle2,
    ChevronDown,
    Clock3,
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
    X,
} from 'lucide-react';

const fallbackHero =
    'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=2200&q=85';

const navigation = [
    ['Accueil', '#home'],
    ['À propos', '#about'],
    ['Impact', '#impact'],
    ['Témoignages', '#testimonials'],
    ['Galerie', '#gallery'],
    ['Contact', '#contact'],
];

export default function SchoolLanding({
    school,
    stats,
    features = [],
    testimonials = [],
    gallery = [],
}) {
    const [menuOpen, setMenuOpen] = useState(false);
    const [lightboxItem, setLightboxItem] = useState(null);
    const address = [school.address, school.city, school.country]
        .filter(Boolean)
        .join(', ');
    const phoneHref = school.phone?.replace(/[^+\d]/g, '');

    useEffect(() => {
        if (!lightboxItem) return undefined;
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

    return (
        <div
            className="min-h-screen bg-white text-slate-900"
            style={{
                '--school-primary': school.primary_color,
                '--school-secondary': school.secondary_color,
                '--school-accent': school.accent_color,
            }}
        >
            <Head title={school.name}>
                <meta name="description" content={school.meta_description} />
                <meta property="og:title" content={school.name} />
                <meta property="og:description" content={school.meta_description} />
                <meta property="og:image" content={school.hero_image_url || fallbackHero} />
                <meta property="og:url" content={school.canonical_url} />
                <meta property="og:type" content="website" />
                {school.logo_url && <link rel="icon" href={school.logo_url} />}
                <link rel="canonical" href={school.canonical_url} />
            </Head>

            <header className="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
                <div className="mx-auto flex h-[76px] max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <a href="#home" className="flex min-w-0 items-center gap-3">
                        {school.logo_url ? (
                            <img src={school.logo_url} alt={`Logo ${school.name}`} className="h-11 w-11 shrink-0 rounded-lg object-contain" />
                        ) : (
                            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-white" style={{ backgroundColor: school.primary_color }}>
                                <GraduationCap className="h-6 w-6" />
                            </span>
                        )}
                        <span className="min-w-0">
                            <span className="block truncate text-sm font-bold text-slate-950 sm:text-base">{school.name}</span>
                            {school.slogan && <span className="hidden max-w-56 truncate text-xs text-slate-500 sm:block">{school.slogan}</span>}
                        </span>
                    </a>

                    <nav className="hidden items-center gap-5 xl:flex" aria-label="Navigation principale">
                        {navigation.map(([label, href]) => <a key={href} href={href} className="text-sm font-medium text-slate-600 transition hover:text-slate-950">{label}</a>)}
                    </nav>
                    <div className="hidden xl:block"><ParentLink color={school.primary_color} /></div>
                    <button
                        type="button"
                        onClick={() => setMenuOpen((open) => !open)}
                        className="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-slate-200 text-slate-700 xl:hidden"
                        aria-label={menuOpen ? 'Fermer le menu' : 'Ouvrir le menu'}
                        aria-expanded={menuOpen}
                    >
                        {menuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                    </button>
                </div>
                {menuOpen && (
                    <nav className="border-t border-slate-100 bg-white px-4 py-3 xl:hidden" aria-label="Navigation mobile">
                        <div className="mx-auto grid max-w-7xl gap-1 sm:grid-cols-2">
                            {navigation.map(([label, href]) => <a key={href} href={href} onClick={() => setMenuOpen(false)} className="rounded-md px-3 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">{label}</a>)}
                            <div className="px-3 py-2 sm:col-span-2"><ParentLink color={school.primary_color} /></div>
                        </div>
                    </nav>
                )}
            </header>

            <main>
                <section id="home" className="relative isolate flex min-h-[620px] items-center overflow-hidden bg-slate-900 sm:min-h-[680px]">
                    <img
                        src={school.hero_image_url || fallbackHero}
                        alt={school.hero_image_url ? `Vue de ${school.name}` : 'Illustration pédagogique d’une salle de classe'}
                        fetchPriority="high"
                        className="absolute inset-0 -z-20 h-full w-full object-cover"
                    />
                    <div className="absolute inset-0 -z-10 bg-gradient-to-r from-slate-950/90 via-slate-950/65 to-slate-950/15" />
                    <div className="absolute inset-y-0 left-0 -z-10 w-2" style={{ backgroundColor: school.accent_color }} />
                    <div className="mx-auto w-full max-w-7xl px-6 py-20 sm:px-8 lg:px-12 lg:py-28">
                        <div className="max-w-3xl text-white">
                            <div className="mb-6 inline-flex items-center gap-2 border-l-2 border-amber-300 pl-3 text-sm font-semibold text-white/85">
                                <School className="h-4 w-4" /> Établissement scolaire
                            </div>
                            <h1 className="max-w-3xl text-4xl font-bold leading-tight sm:text-5xl lg:text-6xl">{school.name}</h1>
                            {school.slogan && <p className="mt-5 max-w-2xl text-xl font-semibold leading-8 text-white sm:text-2xl">{school.slogan}</p>}
                            {school.description && <p className="mt-4 max-w-2xl text-base leading-7 text-white/80 sm:text-lg">{school.description}</p>}
                            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                <a href="#about" className="inline-flex min-h-12 items-center justify-center gap-2 rounded-md px-5 text-sm font-semibold text-white transition hover:brightness-110" style={{ backgroundColor: school.primary_color }}>
                                    Découvrir notre établissement <ArrowRight className="h-4 w-4" />
                                </a>
                                <ParentLink color="rgba(255,255,255,0.16)" light />
                            </div>
                        </div>
                    </div>
                    {!school.hero_image_url && <span className="absolute bottom-3 right-4 text-[10px] text-white/70">Image d’illustration</span>}
                    <a href="#about" className="absolute bottom-8 left-1/2 hidden -translate-x-1/2 items-center gap-2 text-xs font-medium text-white/80 sm:flex">Faire défiler <ChevronDown className="h-4 w-4" /></a>
                </section>

                <section id="about" className="scroll-mt-20 bg-white py-20 sm:py-24">
                    <div className="mx-auto grid max-w-7xl gap-12 px-5 sm:px-8 lg:grid-cols-[0.8fr_1.2fr] lg:px-12">
                        <div>
                            <SectionEyebrow color={school.primary_color}>À propos</SectionEyebrow>
                            <h2 className="mt-4 max-w-xl text-3xl font-bold leading-tight text-slate-950 sm:text-4xl">{school.name}, un établissement tourné vers l’avenir de chaque élève.</h2>
                            {school.founded_year && <p className="mt-6 inline-flex items-center gap-2 text-sm font-medium text-slate-600"><Clock3 className="h-4 w-4" />Établi en {school.founded_year}</p>}
                        </div>
                        <div className="space-y-8">
                            {school.description ? <p className="text-lg leading-8 text-slate-600">{school.description}</p> : <p className="text-lg leading-8 text-slate-500">Les informations de présentation de l’établissement seront bientôt disponibles.</p>}
                            {(school.mission || school.vision) && (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    {school.mission && <InfoBlock icon={Target} label="Notre mission" text={school.mission} color={school.primary_color} />}
                                    {school.vision && <InfoBlock icon={Sparkles} label="Notre vision" text={school.vision} color={school.secondary_color} />}
                                </div>
                            )}
                            {school.values?.length > 0 && (
                                <div>
                                    <h3 className="mb-3 text-sm font-bold uppercase text-slate-500">Nos valeurs</h3>
                                    <ul className="flex flex-wrap gap-2">
                                        {school.values.map((value) => <li key={value} className="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-2 text-sm font-medium text-slate-700"><CheckCircle2 className="h-4 w-4" style={{ color: school.primary_color }} />{value}</li>)}
                                    </ul>
                                </div>
                            )}
                        </div>
                    </div>
                </section>

                <section id="impact" className="scroll-mt-20 border-y border-slate-200 bg-slate-50 py-14 sm:py-16">
                    <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-12">
                        <div className="mb-9 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                            <div><SectionEyebrow color={school.primary_color}>Notre impact</SectionEyebrow><h2 className="mt-2 text-2xl font-bold text-slate-950 sm:text-3xl">Une communauté qui grandit ensemble</h2></div>
                            <p className="max-w-md text-sm leading-6 text-slate-500">Chiffres calculés à partir des données actuelles de l’établissement.</p>
                        </div>
                        <div className="grid grid-cols-2 divide-x divide-y divide-slate-200 border-y border-slate-200 sm:grid-cols-4 sm:divide-y-0">
                            <ImpactStat icon={GraduationCap} label="Élèves actifs" value={stats.students} />
                            <ImpactStat icon={HeartHandshake} label="Enseignants" value={stats.teachers} />
                            <ImpactStat icon={BookOpenCheck} label="Classes" value={stats.classes} />
                            {stats.years_experience !== null && <ImpactStat icon={Clock3} label="Années d’expérience" value={stats.years_experience} />}
                        </div>
                    </div>
                </section>

                <section id="strengths" className="scroll-mt-20 py-20 sm:py-24">
                    <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-12">
                        <div className="max-w-2xl"><SectionEyebrow color={school.primary_color}>Notre approche</SectionEyebrow><h2 className="mt-3 text-3xl font-bold text-slate-950 sm:text-4xl">Pourquoi choisir notre établissement ?</h2></div>
                        {features.length > 0 ? (
                            <div className="mt-10 grid gap-x-8 gap-y-9 sm:grid-cols-2 lg:grid-cols-3">
                                {features.map((feature, index) => <article key={`${feature.title}-${index}`} className="border-t-2 border-slate-200 pt-5"><span className="mb-4 flex h-10 w-10 items-center justify-center rounded-md text-white" style={{ backgroundColor: index % 2 ? school.secondary_color : school.primary_color }}>{index % 2 ? <ShieldCheck className="h-5 w-5" /> : <BookOpenCheck className="h-5 w-5" />}</span><h3 className="text-lg font-bold text-slate-900">{feature.title}</h3>{feature.description && <p className="mt-2 text-sm leading-6 text-slate-600">{feature.description}</p>}</article>)}
                            </div>
                        ) : <p className="mt-8 max-w-2xl border-l-2 border-slate-300 pl-4 text-sm leading-6 text-slate-500">Les points forts de l’établissement seront présentés ici dès qu’ils auront été renseignés.</p>}
                    </div>
                </section>

                {testimonials.length > 0 && (
                    <section id="testimonials" className="scroll-mt-20 bg-slate-950 py-20 text-white sm:py-24">
                        <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-12">
                            <div className="max-w-2xl"><SectionEyebrow color={school.accent_color}>Témoignages</SectionEyebrow><h2 className="mt-3 text-3xl font-bold sm:text-4xl">Ils parlent de nous</h2></div>
                            <div className="mt-10 grid gap-5 lg:grid-cols-2">
                                {testimonials.map((testimonial) => <figure key={`${testimonial.name}-${testimonial.message.slice(0, 20)}`} className="border-t border-white/20 py-6"><Quote className="h-7 w-7" style={{ color: school.accent_color }} /><blockquote className="mt-4 text-lg leading-8 text-white/90">“{testimonial.message}”</blockquote><figcaption className="mt-6 flex items-center gap-3">{testimonial.photo_url ? <img src={testimonial.photo_url} alt="" loading="lazy" className="h-11 w-11 rounded-full object-cover" /> : <span className="flex h-11 w-11 items-center justify-center rounded-full text-sm font-bold text-white" style={{ backgroundColor: school.secondary_color }}>{testimonial.name.slice(0, 1).toUpperCase()}</span>}<span><span className="block text-sm font-semibold">{testimonial.name}</span>{testimonial.relationship && <span className="mt-0.5 block text-xs text-white/60">{testimonial.relationship}</span>}</span></figcaption></figure>)}
                            </div>
                        </div>
                    </section>
                )}

                {gallery.length > 0 && (
                    <section id="gallery" className="scroll-mt-20 py-20 sm:py-24">
                        <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-12">
                            <div className="flex items-end justify-between gap-4"><div><SectionEyebrow color={school.primary_color}>Galerie</SectionEyebrow><h2 className="mt-3 text-3xl font-bold text-slate-950 sm:text-4xl">La vie de notre établissement</h2></div><Images className="hidden h-8 w-8 text-slate-300 sm:block" /></div>
                            <div className="mt-9 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                {gallery.map((item, index) => <button key={`${item.image_url}-${index}`} type="button" onClick={() => setLightboxItem(item)} className={`group relative block w-full overflow-hidden bg-slate-100 text-left ${index === 0 ? 'aspect-[4/3] sm:row-span-2 sm:aspect-auto' : 'aspect-[4/3]'}`} aria-label={`Agrandir : ${item.alt_text}`}><img src={item.image_url} alt={item.alt_text} loading="lazy" className="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" />{(item.title || item.description) && <span className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/75 to-transparent p-4 pt-12 text-white">{item.title && <span className="block font-semibold">{item.title}</span>}{item.description && <span className="mt-1 block text-sm text-white/80">{item.description}</span>}</span>}</button>)}
                            </div>
                        </div>
                    </section>
                )}

                <section id="contact" className="scroll-mt-20 border-t border-slate-200 bg-slate-50 py-20 sm:py-24">
                    <div className="mx-auto grid max-w-7xl gap-10 px-5 sm:px-8 lg:grid-cols-[0.8fr_1.2fr] lg:px-12">
                        <div><SectionEyebrow color={school.primary_color}>Contact</SectionEyebrow><h2 className="mt-3 text-3xl font-bold text-slate-950 sm:text-4xl">Nous contacter</h2><p className="mt-4 max-w-md text-base leading-7 text-slate-600">Pour toute information sur notre établissement, contactez-nous par téléphone ou par email.</p></div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            {address && <ContactItem icon={MapPin} label="Adresse" value={address} href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`} external />}
                            {school.phone && <ContactItem icon={Phone} label="Téléphone" value={school.phone} href={`tel:${phoneHref}`} />}
                            {school.email && <ContactItem icon={Mail} label="Email" value={school.email} href={`mailto:${school.email}`} />}
                            {school.website && <ContactItem icon={ArrowRight} label="Site web" value={school.website} href={school.website} external />}
                            {!address && !school.phone && !school.email && !school.website && <p className="text-sm text-slate-500">Les coordonnées de l’établissement seront bientôt disponibles.</p>}
                        </div>
                    </div>
                </section>
            </main>

            <footer className="bg-slate-950 py-12 text-white">
                <div className="mx-auto grid max-w-7xl gap-10 px-5 sm:px-8 md:grid-cols-3 lg:px-12">
                    <div><a href="#home" className="flex items-center gap-3">{school.logo_url ? <img src={school.logo_url} alt={`Logo ${school.name}`} className="h-10 w-10 rounded object-contain" /> : <GraduationCap className="h-9 w-9" style={{ color: school.accent_color }} />}<span className="font-bold">{school.name}</span></a>{school.description && <p className="mt-4 max-w-sm text-sm leading-6 text-white/65">{school.description}</p>}</div>
                    <div><h2 className="text-sm font-semibold">Navigation</h2><div className="mt-4 grid grid-cols-2 gap-3 text-sm text-white/65">{navigation.map(([label, href]) => <a key={href} href={href} className="hover:text-white">{label}</a>)}</div></div>
                    <div><h2 className="text-sm font-semibold">Coordonnées</h2><div className="mt-4 space-y-2 text-sm text-white/65">{address && <p>{address}</p>}{school.phone && <a href={`tel:${phoneHref}`} className="block hover:text-white">{school.phone}</a>}{school.email && <a href={`mailto:${school.email}`} className="block hover:text-white">{school.email}</a>}</div></div>
                </div>
                <div className="mx-auto mt-10 flex max-w-7xl flex-col gap-2 border-t border-white/10 px-5 pt-5 text-xs text-white/50 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-12"><span>© {new Date().getFullYear()} {school.name}</span><a href="https://coriyase.com" className="hover:text-white/80" rel="noreferrer">Propulsé par Coriyase</a></div>
            </footer>

            {lightboxItem && <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/95 p-4" role="dialog" aria-modal="true" aria-label={lightboxItem.alt_text} onClick={() => setLightboxItem(null)}><button type="button" onClick={() => setLightboxItem(null)} className="absolute right-4 top-4 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20" aria-label="Fermer l’image"><X className="h-5 w-5" /></button><figure className="max-h-full max-w-6xl" onClick={(event) => event.stopPropagation()}><img src={lightboxItem.image_url} alt={lightboxItem.alt_text} className="max-h-[78vh] max-w-full object-contain" />{(lightboxItem.title || lightboxItem.description) && <figcaption className="mt-3 text-center text-sm text-white/80">{lightboxItem.title}{lightboxItem.title && lightboxItem.description ? ' · ' : ''}{lightboxItem.description}</figcaption>}</figure></div>}
        </div>
    );
}

function ParentLink({ color, light = false }) {
    return <Link href={route('login')} className={`inline-flex min-h-11 items-center justify-center gap-2 rounded-md px-4 text-sm font-semibold transition hover:brightness-110 ${light ? 'border border-white/35 text-white' : 'text-white'}`} style={{ backgroundColor: color }}><GraduationCap className="h-4 w-4" />Espace parent</Link>;
}

function SectionEyebrow({ color, children }) {
    return <p className="text-xs font-bold uppercase text-slate-500" style={{ color }}>{children}</p>;
}

function InfoBlock({ icon: Icon, label, text, color }) {
    return <article className="border-l-2 border-slate-200 pl-4"><Icon className="h-5 w-5" style={{ color }} /><h3 className="mt-3 text-sm font-bold text-slate-900">{label}</h3><p className="mt-2 text-sm leading-6 text-slate-600">{text}</p></article>;
}

function ImpactStat({ icon: Icon, label, value }) {
    return <div className="flex flex-col items-start gap-2 px-4 py-6 sm:px-6 sm:py-7"><Icon className="h-5 w-5 text-slate-400" /><p className="text-3xl font-bold text-slate-950">{Number(value).toLocaleString('fr-FR')}</p><p className="text-sm text-slate-500">{label}</p></div>;
}

function ContactItem({ icon: Icon, label, value, href, external = false }) {
    return <a href={href} target={external ? '_blank' : undefined} rel={external ? 'noreferrer' : undefined} className="flex min-h-24 items-start gap-4 border-t border-slate-200 py-4 text-left transition hover:border-slate-400"><Icon className="mt-1 h-5 w-5 shrink-0 text-slate-500" /><span className="min-w-0"><span className="block text-xs font-semibold uppercase text-slate-500">{label}</span><span className="mt-1 block break-words text-sm font-medium text-slate-900">{value}</span></span></a>;
}