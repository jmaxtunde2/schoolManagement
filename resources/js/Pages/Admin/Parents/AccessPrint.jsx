import { Head } from '@inertiajs/react';
import { useEffect } from 'react';

export default function AccessPrint({ school, parent, generated_code }) {
    useEffect(() => {
        window.print();
    }, []);

    return (
        <div className="p-8 max-w-2xl mx-auto">
            <Head title="Fiche d'accès parent" />
            <div className="border-2 border-gray-300 p-8">
                <div className="text-center mb-6">
                    {school?.logo_url && (
                        <img src={school.logo_url} alt={school.name} className="h-16 mx-auto mb-4" />
                    )}
                    <h1 className="text-2xl font-bold">{school?.name}</h1>
                    <h2 className="text-xl mt-2">FICHE D'ACCÈS ESPACE PARENT</h2>
                </div>

                <div className="space-y-4 mb-6">
                    <div>
                        <span className="font-semibold">Nom du parent :</span> {parent?.name}
                    </div>
                    <div>
                        <span className="font-semibold">Établissement :</span> {school?.name}
                    </div>
                </div>

                {generated_code && (
                    <div className="bg-gray-100 p-6 text-center mb-6 border-2 border-dashed border-gray-400">
                        <p className="text-sm mb-2">CODE D'ACCÈS</p>
                        <p className="text-3xl font-mono font-bold tracking-widest">{generated_code}</p>
                    </div>
                )}

                <div className="space-y-3 text-sm">
                    <h3 className="font-semibold text-base">Mode d'emploi :</h3>
                    <ol className="list-decimal list-inside space-y-2">
                        <li>Rendez-vous sur le site internet de l'établissement</li>
                        <li>Cliquez sur le bouton « ESPACE PARENT »</li>
                        <li>Saisissez votre code d'accès ci-dessus</li>
                        <li>Cliquez sur « Accéder à mon espace »</li>
                        <li>Vous pourrez consulter les informations de votre enfant</li>
                    </ol>
                    <p className="mt-4 text-xs text-gray-600">
                        Ce code est valable 1 jour à compter de sa génération et ne peut être utilisé qu'une seule fois.
                        Une fois activé, vous n'aurez plus besoin de le ressaisir jusqu'à la fin de l'année scolaire.
                    </p>
                </div>
            </div>
        </div>
    );
}
