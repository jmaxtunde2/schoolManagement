import { Head, Link } from '@inertiajs/react';
import { GraduationCap, Users } from 'lucide-react';

export default function Dashboard({ school, parent, children }) {
    const primaryColor = '#10b981';

    return (
        <div className="min-h-screen bg-gray-50">
            <Head title="Espace parent" />
            <header className="bg-white shadow-sm border-b">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-3">
                            {school?.logo_url && (
                                <img src={school.logo_url} alt={school.name} className="h-10" />
                            )}
                            <div>
                                <h1 className="text-lg font-semibold text-gray-900">Espace parent</h1>
                                <p className="text-sm text-gray-600">{school?.name}</p>
                            </div>
                        </div>
                        <div className="flex items-center space-x-4">
                            <span className="text-sm text-gray-700">{parent?.name}</span>
                            <Link
                                href={school?.slug ? route('parent-access.logout.slug', { slug: school.slug }) : route('parent-access.logout')}
                                method="post"
                                as="button"
                                className="text-sm text-gray-600 hover:text-gray-900"
                            >
                                Déconnexion
                            </Link>
                        </div>
                    </div>
                </div>
            </header>

            <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="mb-8">
                    <h2 className="text-2xl font-bold text-gray-900 mb-2">Mes enfants</h2>
                    <p className="text-gray-600">Sélectionnez un enfant pour consulter ses informations scolaires.</p>
                </div>

                {children && children.length > 0 ? (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {children.map((child) => (
                            <div
                                key={child.id}
                                className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow"
                            >
                                <div className="flex items-center space-x-4 mb-4">
                                    <div 
                                        className="w-12 h-12 rounded-full flex items-center justify-center"
                                        style={{ backgroundColor: primaryColor + '20' }}
                                    >
                                        <GraduationCap className="w-6 h-6" style={{ color: primaryColor }} />
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <h3 className="text-lg font-semibold text-gray-900 truncate">{child.name}</h3>
                                        <p className="text-sm text-gray-600">{child.class_name || 'Classe non définie'}</p>
                                        {child.matricule && (
                                            <p className="text-xs text-gray-500">Matricule: {child.matricule}</p>
                                        )}
                                    </div>
                                </div>
                                <p className="text-sm text-gray-600">
                                    Espace disponible pour consulter bulletins, emplois du temps, etc.
                                </p>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="text-center py-12 bg-white rounded-xl">
                        <Users className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                        <p className="text-gray-600">Aucun enfant associé à ce compte.</p>
                    </div>
                )}
            </main>
        </div>
    );
}
