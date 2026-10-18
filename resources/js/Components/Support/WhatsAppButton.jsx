import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import { X } from 'lucide-react';

import { cn } from '@/Utils/cn';

const DEFAULT_MESSAGE =
    'Bonjour, je souhaite entrer en contact avec le service clientèle de CoriSchool.';

const GREETING_KEY = 'corischool.whatsapp.greeting.dismissed';

/** Icône officielle WhatsApp (glyphe de marque). */
export function WhatsAppIcon({ className }) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="currentColor"
            aria-hidden="true"
            className={className}
        >
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
        </svg>
    );
}

/** Nettoie un numéro en ne conservant que les chiffres (format attendu par wa.me). */
function normalizePhone(value) {
    if (typeof value !== 'string') {
        return '';
    }

    const digits = value.replace(/\D/g, '');

    return digits.length >= 8 ? digits : '';
}

/**
 * Bouton flottant « Contacter le service clientèle » via WhatsApp.
 *
 * Le numéro est fourni par le back-office (config/cori.php → support.whatsapp,
 * exposé en prop Inertia `support.whatsapp`). Aucun rendu tant que le numéro
 * n'est pas renseigné, ce qui permet de déployer sans risque.
 */
export default function WhatsAppButton({
    label = 'Service clientèle',
    description = 'Une question sur CoriSchool ? Notre équipe vous répond sur WhatsApp.',
    message,
    className,
}) {
    const { support } = usePage().props;
    const phone = normalizePhone(support?.whatsapp);
    const text = message ?? support?.whatsapp_message ?? DEFAULT_MESSAGE;

    const [greetingVisible, setGreetingVisible] = useState(false);

    useEffect(() => {
        if (!phone || typeof window === 'undefined') {
            return undefined;
        }

        let dismissed = false;

        try {
            dismissed = window.sessionStorage.getItem(GREETING_KEY) === '1';
        } catch (error) {
            dismissed = false;
        }

        if (dismissed) {
            return undefined;
        }

        const timer = setTimeout(() => setGreetingVisible(true), 1800);

        return () => clearTimeout(timer);
    }, [phone]);

    if (!phone) {
        return null;
    }

    const href = `https://wa.me/${phone}?text=${encodeURIComponent(text)}`;

    function dismissGreeting() {
        setGreetingVisible(false);

        try {
            window.sessionStorage.setItem(GREETING_KEY, '1');
        } catch (error) {
            // sessionStorage indisponible : le message se réaffichera, sans impact.
        }
    }

    return (
        <motion.div
            initial={{ opacity: 0, y: 16, scale: 0.9 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            transition={{ delay: 0.8, duration: 0.45, ease: [0.22, 1, 0.36, 1] }}
            className={cn('fixed bottom-5 right-5 z-40 sm:bottom-6 sm:right-6', className)}
        >
            <AnimatePresence>
                {greetingVisible && (
                    <motion.div
                        initial={{ opacity: 0, y: 10, scale: 0.96 }}
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        exit={{ opacity: 0, y: 10, scale: 0.96 }}
                        transition={{ duration: 0.25, ease: [0.22, 1, 0.36, 1] }}
                        className="absolute bottom-[4.75rem] right-0 hidden w-72 origin-bottom-right sm:block"
                    >
                        <div className="relative rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl shadow-slate-900/10">
                            <button
                                type="button"
                                onClick={dismissGreeting}
                                aria-label="Fermer le message"
                                className="absolute right-2.5 top-2.5 rounded-md p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            >
                                <X className="h-4 w-4" />
                            </button>

                            <div className="flex items-center gap-2.5">
                                <span className="flex h-9 w-9 items-center justify-center rounded-full bg-[#25D366]/10 text-[#25D366]">
                                    <WhatsAppIcon className="h-5 w-5" />
                                </span>

                                <div>
                                    <p className="text-sm font-bold text-slate-900">{label}</p>
                                    <p className="flex items-center gap-1.5 text-[11px] font-medium text-emerald-600">
                                        <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                        En ligne
                                    </p>
                                </div>
                            </div>

                            <p className="mt-3 text-xs leading-5 text-slate-500">{description}</p>

                            <a
                                href={href}
                                target="_blank"
                                rel="noopener noreferrer"
                                onClick={dismissGreeting}
                                className="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#25D366] px-3 py-2.5 text-xs font-bold text-white transition hover:bg-[#20BD5A]"
                            >
                                <WhatsAppIcon className="h-4 w-4" />
                                Démarrer la discussion
                            </a>

                            <span className="absolute -bottom-2 right-7 h-4 w-4 rotate-45 border-b border-r border-slate-200 bg-white" />
                        </div>
                    </motion.div>
                )}
            </AnimatePresence>

            <a
                href={href}
                target="_blank"
                rel="noopener noreferrer"
                aria-label={`Contacter ${label} sur WhatsApp`}
                className="group relative flex h-14 w-14 items-center justify-center rounded-full focus:outline-none"
            >
                <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#25D366] opacity-25 motion-reduce:animate-none" />

                <span className="relative flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg shadow-emerald-500/30 ring-1 ring-black/5 transition duration-200 group-hover:scale-105 group-hover:bg-[#20BD5A] group-focus-visible:ring-4 group-focus-visible:ring-emerald-400/50">
                    <WhatsAppIcon className="h-7 w-7" />
                </span>
            </a>
        </motion.div>
    );
}
