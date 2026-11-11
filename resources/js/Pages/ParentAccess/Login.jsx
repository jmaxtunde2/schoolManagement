import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Login({ school }) {
    const { data, setData, post, processing, errors } = useForm({
        code: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(school?.slug
            ? route('parent-access.login.slug.store', { slug: school.slug })
            : route('parent-access.login.store'));
    };

    const primaryColor = school?.primary_color || '#10b981';

    return (
        <div className="min-h-screen bg-gradient-to-br from-emerald-50 to-teal-50 flex items-center justify-center px-4">
            <Head title="Espace parent" />
            <div className="w-full max-w-md">
                <div className="bg-white rounded-2xl shadow-xl overflow-hidden">
                    <div className="px-8 py-10">
                        <div className="text-center mb-8">
                            {school?.logo_url ? (
                                <img
                                    src={school.logo_url}
                                    alt={school.name}
                                    className="mx-auto mb-4 h-20 w-20 rounded-2xl object-contain"
                                />
                            ) : (
                                school?.name && (
                                    <div
                                        className="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-2xl text-2xl font-bold text-white shadow-sm"
                                        style={{ backgroundColor: primaryColor }}
                                    >
                                        {school.name.charAt(0).toUpperCase()}
                                    </div>
                                )
                            )}
                            <h1 className="text-2xl font-bold text-gray-900 mb-2">
                                Bienvenue dans votre espace parent
                            </h1>
                            <p className="text-gray-600">
                                Entrez votre code d'accès pour consulter les informations scolaires de votre enfant.
                            </p>
                            {school?.name && (
                                <p className="mt-2 text-sm font-medium text-emerald-700">{school.name}</p>
                            )}
                        </div>

                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <label htmlFor="code" className="block text-sm font-medium text-gray-700 mb-2">
                                    Code d'accès parent
                                </label>
                                <input
                                    id="code"
                                    type="text"
                                    value={data.code}
                                    onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                    className="w-full px-4 py-3 text-center text-lg font-mono tracking-widest border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 uppercase"
                                    placeholder="CS-XXXXXXXX"
                                    maxLength={12}
                                    required
                                    autoFocus
                                />
                                {errors.code && (
                                    <p className="mt-2 text-sm text-red-600">{errors.code}</p>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full py-3 px-4 rounded-xl text-white font-semibold text-lg shadow-lg hover:shadow-xl transition-all disabled:opacity-50"
                                style={{ backgroundColor: primaryColor }}
                            >
                                {processing ? 'Vérification...' : 'Accéder à mon espace'}
                            </button>
                        </form>

                        <div className="mt-6 text-center">
                            <p className="text-sm text-gray-600">
                                En cas de problème de connexion, contactez l'administration de l'établissement.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
