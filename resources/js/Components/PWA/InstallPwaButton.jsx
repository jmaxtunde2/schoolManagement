import { useState } from 'react';
import { PlusSquare, Share, Smartphone, X } from 'lucide-react';

import { usePwaInstall } from '@/Hooks/usePwaInstall';
import { cn } from '@/Utils/cn';

/**
 * Bouton discret d'installation de la PWA dans l'Espace Parent.
 * Sur iOS, affiche les instructions Safari (l'API d'installation n'existe pas).
 */
export default function InstallPwaButton({ className = '', label = 'Installer CoriSchool' }) {
    const { canInstall, isIos, promptInstall } = usePwaInstall();
    const [showIosHelp, setShowIosHelp] = useState(false);

    if (!canInstall) {
        return null;
    }

    async function handleClick() {
        if (isIos) {
            setShowIosHelp(true);

            return;
        }

        await promptInstall();
    }

    return (
        <>
            <button
                type="button"
                onClick={handleClick}
                className={cn(
                    'inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100',
                    className,
                )}
            >
                <Smartphone className="h-4 w-4" />
                {label}
            </button>

            {showIosHelp && (
                <div
                    className="fixed inset-0 z-[60] flex items-end justify-center bg-slate-950/50 p-4 sm:items-center"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="pwa-install-title"
                    onClick={() => setShowIosHelp(false)}
                >
                    <div
                        className="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl"
                        onClick={(event) => event.stopPropagation()}
                    >
                        <div className="flex items-start justify-between gap-4">
                            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                                <Smartphone className="h-5 w-5" />
                            </div>
                            <button
                                type="button"
                                onClick={() => setShowIosHelp(false)}
                                aria-label="Fermer"
                                className="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>

                        <h2 id="pwa-install-title" className="mt-4 text-lg font-black text-slate-950">
                            Installer CoriSchool sur iPhone
                        </h2>
                        <p className="mt-2 text-sm leading-6 text-slate-500">
                            Ajoutez l’application à votre écran d’accueil pour ouvrir
                            directement votre Espace Parent.
                        </p>

                        <ol className="mt-5 space-y-4">
                            <li className="flex gap-3">
                                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-slate-600">
                                    1
                                </span>
                                <div className="flex items-start gap-2 text-sm leading-6 text-slate-700">
                                    <Share className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                                    <span>
                                        Ouvrez le menu <strong>Partager</strong> dans Safari.
                                    </span>
                                </div>
                            </li>
                            <li className="flex gap-3">
                                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-slate-600">
                                    2
                                </span>
                                <div className="flex items-start gap-2 text-sm leading-6 text-slate-700">
                                    <PlusSquare className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                                    <span>
                                        Choisissez <strong>Sur l’écran d’accueil</strong>.
                                    </span>
                                </div>
                            </li>
                            <li className="flex gap-3">
                                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-slate-600">
                                    3
                                </span>
                                <p className="text-sm leading-6 text-slate-700">
                                    Appuyez sur <strong>Ajouter</strong>. L’icône CoriSchool
                                    apparaît sur votre téléphone.
                                </p>
                            </li>
                        </ol>

                        <button
                            type="button"
                            onClick={() => setShowIosHelp(false)}
                            className="mt-6 w-full rounded-xl bg-slate-950 px-4 py-3 text-sm font-bold text-white transition hover:bg-slate-800"
                        >
                            Compris
                        </button>
                    </div>
                </div>
            )}
        </>
    );
}
