import { Head, Link } from '@inertiajs/react';

import GuestLayout from '@/Layouts/GuestLayout';
import Button from '@/Components/UI/Button';

export default function Recovery({ codes = [] }) {
    return (
        <GuestLayout>
            <Head title="Codes de récupération" />

            <div className="mx-auto max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

                <h1 className="text-xl font-bold text-slate-900">
                    Codes de récupération
                </h1>

                <p className="mt-2 text-sm text-slate-600">
                    Conservez ces codes dans un endroit sûr et hors ligne.
                    Chaque code ne peut être utilisé qu’une seule fois.
                </p>

                {codes.length > 0 ? (
                    <div className="my-5 grid grid-cols-2 gap-2 rounded-lg bg-slate-50 p-4">
                        {codes.map((code) => (
                            <div
                                key={code}
                                className="rounded border border-slate-200 bg-white px-3 py-2 text-center font-mono text-sm text-slate-800"
                            >
                                {code}
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="my-5 rounded-lg bg-amber-50 p-4 text-sm text-amber-800">
                        Aucun code de récupération n’est disponible.
                    </div>
                )}

                <p className="mb-5 text-xs text-slate-500">
                    Ces codes permettent de récupérer l’accès à votre compte
                    si vous n’avez plus accès à votre application
                    Authenticator.
                </p>

                <Link
                    href={route('two-factor.challenge')}
                    className="block"
                >
                    <Button className="w-full">
                        Continuer
                    </Button>
                </Link>
            </div>
        </GuestLayout>
    );
}