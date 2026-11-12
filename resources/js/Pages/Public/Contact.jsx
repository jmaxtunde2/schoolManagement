import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    Clock,
    Mail,
    MessageCircle,
    Sparkles,
} from 'lucide-react';

import InfoPageLayout, {
    InfoSection,
} from '@/Components/Public/InfoPageLayout';
import { WhatsAppIcon } from '@/Components/Support/WhatsAppButton';

function normalizePhone(value) {
    if (typeof value !== 'string') {
        return '';
    }

    const digits = value.replace(/\D/g, '');

    return digits.length >= 8 ? digits : '';
}

export default function Contact({ support = {} }) {
    const phone = normalizePhone(support.whatsapp);
    const message =
        support.whatsapp_message ??
        'Bonjour, je souhaite entrer en contact avec le service clientèle de CoriSchool.';
    const whatsappHref = phone
        ? `https://wa.me/${phone}?text=${encodeURIComponent(message)}`
        : null;
    const email = support.email || 'contact@coriyase.com';

    const channels = [
        {
            icon: WhatsAppIcon,
            title: 'WhatsApp',
            description:
                'La voie la plus rapide pour échanger avec notre équipe et poser vos questions.',
            value: phone ? `+${phone}` : 'Bientôt disponible',
            href: whatsappHref,
            action: 'Démarrer la discussion',
            external: true,
            tone: 'bg-[#25D366]/10 text-[#1da851]',
        },
        {
            icon: Mail,
            title: 'E-mail',
            description:
                'Écrivez-nous pour une demande détaillée, un devis ou un besoin d’accompagnement.',
            value: email,
            href: `mailto:${email}`,
            action: 'Envoyer un e-mail',
            external: false,
            tone: 'bg-emerald-50 text-emerald-700',
        },
        {
            icon: Sparkles,
            title: 'Démonstration',
            description:
                'Découvrez la plateforme en conditions réelles avec une présentation adaptée à votre établissement.',
            value: 'Sur rendez-vous',
            href: route('demo.request'),
            action: 'Demander une démonstration',
            external: false,
            tone: 'bg-teal-50 text-teal-700',
        },
    ];

    return (
        <InfoPageLayout
            metaTitle="Contact | CoriSchool — Parlons de votre établissement"
            metaDescription="Contactez l’équipe CoriSchool : WhatsApp, e-mail ou demande de démonstration. Nous accompagnons les établissements scolaires dans leur transition numérique."
            title="Contactez-nous"
            subtitle="Une question sur CoriSchool, une demande de démonstration ou un projet de digitalisation pour votre établissement ? Notre équipe vous répond."
        >
            <div className="mt-10 space-y-8">
                <InfoSection title="Comment nous joindre ?">
                    <p>
                        CoriSchool est un produit de Coriyase Technologies, 
                        une entreprise située à Parakou, en face de la paroisse Marie Auxiliatrice, en allant carrefour de Guy Riobé, Bénin.
                    </p>
                    <p>
                        Le service clientèle CoriSchool est assuré par
                        l’équipe Coriyase Technologies. Pour un échange
                        immédiat, privilégiez WhatsApp. Pour une demande
                        nécessitant des pièces ou un suivi écrit, utilisez
                        l’adresse e-mail.
                    </p>
                    <p>
                        Si vous souhaitez découvrir la plateforme avant de
                        vous engager, le formulaire de démonstration nous
                        permet de préparer une présentation adaptée à votre
                        établissement.
                    </p>
                </InfoSection>

                <InfoSection title="Temps de réponse">
                    <div className="flex items-start gap-3 rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4">
                        <Clock className="mt-0.5 h-5 w-5 shrink-0 text-emerald-700" />
                        <p className="text-sm leading-6 text-emerald-900">
                            Notre équipe traite les demandes dans les meilleurs
                            délais. Les échanges WhatsApp pendant les heures
                            ouvrées sont généralement les plus rapides.
                        </p>
                    </div>
                </InfoSection>

                <InfoSection title="Vous préférez être rappelé ?">
                    <p>
                        Déposez une demande de démonstration en indiquant votre
                        numéro de téléphone : nous vous recontactons pour
                        convenir d’un créneau.
                    </p>

                    <div className="pt-2">
                        <Link
                            href={route('demo.request')}
                            className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-500"
                        >
                            <MessageCircle className="h-4 w-4" />
                            Demander une démonstration
                            <ArrowRight className="h-4 w-4" />
                        </Link>
                    </div>
                </InfoSection>
            </div>
            <div className="grid gap-4 sm:grid-cols-3">
                {channels.map((channel) => {
                    const Icon = channel.icon;

                    return (
                        <div
                            key={channel.title}
                            className="flex flex-col rounded-2xl border border-slate-200 bg-slate-50 p-5"
                        >
                            <div
                                className={`flex h-11 w-11 items-center justify-center rounded-xl ${channel.tone}`}
                            >
                                <Icon className="h-5 w-5" />
                            </div>

                            <h2 className="mt-4 text-base font-black text-slate-950">
                                {channel.title}
                            </h2>
                            <p className="mt-2 flex-1 text-sm leading-6 text-slate-500">
                                {channel.description}
                            </p>
                            <p className="mt-4 break-words text-sm font-bold text-slate-700">
                                {channel.value}
                            </p>

                            {channel.href &&
                                (channel.external ? (
                                    <a
                                        href={channel.href}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="mt-4 inline-flex items-center gap-2 text-sm font-bold text-emerald-700 transition hover:text-emerald-600"
                                    >
                                        {channel.action}
                                        <ArrowRight className="h-4 w-4" />
                                    </a>
                                ) : channel.href.startsWith('mailto:') ? (
                                    <a
                                        href={channel.href}
                                        className="mt-4 inline-flex items-center gap-2 text-sm font-bold text-emerald-700 transition hover:text-emerald-600"
                                    >
                                        {channel.action}
                                        <ArrowRight className="h-4 w-4" />
                                    </a>
                                ) : (
                                    <Link
                                        href={channel.href}
                                        className="mt-4 inline-flex items-center gap-2 text-sm font-bold text-emerald-700 transition hover:text-emerald-600"
                                    >
                                        {channel.action}
                                        <ArrowRight className="h-4 w-4" />
                                    </Link>
                                ))}
                        </div>
                    );
                })}
            </div>
        </InfoPageLayout>
    );
}
