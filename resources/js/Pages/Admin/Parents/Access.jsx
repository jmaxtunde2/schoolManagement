import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Search, UserPlus, RefreshCw, X, Printer, Send } from 'lucide-react';

import Pagination from '@/Components/UI/Pagination';

export default function Access({ auth, parents, filters, classes = [], routePrefix = 'admin', schoolId, codeChannelLabel = 'SMS' }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters?.search || '');
    const [classId, setClassId] = useState(filters?.class_id || '');
    const [dismissedCode, setDismissedCode] = useState(false);
    const [busyAction, setBusyAction] = useState(null);

    const generatedCode = dismissedCode ? null : flash?.generated_code;
    const parentName = flash?.parent_name;

    const accessRoute = (action, params = {}) => {
        if (routePrefix === 'platform') {
            return route(`platform.schools.parents.access.${action}`, { school: schoolId, ...params });
        }

        return route(`${routePrefix}.parents.access.${action}`, params);
    };

    const applyFilters = (overrides = {}) => {
        const params = Object.fromEntries(
            Object.entries({ search, class_id: classId, ...overrides }).filter(
                ([, value]) => value !== '' && value !== null && value !== undefined
            )
        );

        router.get(accessRoute('index'), params, { preserveState: true });
    };

    const handleSearch = (e) => {
        e.preventDefault();
        applyFilters();
    };

    const generateCode = (parentId) => {
        if (busyAction) {
            return;
        }

        router.post(accessRoute('generate', { parent: parentId }), {}, {
            preserveScroll: true,
            onStart: () => setBusyAction(`parent-${parentId}`),
            onFinish: () => setBusyAction(null),
        });
    };

    const regenerateCode = (parentId) => {
        if (busyAction) {
            return;
        }

        router.post(accessRoute('regenerate', { parent: parentId }), {}, {
            preserveScroll: true,
            onStart: () => setBusyAction(`parent-${parentId}`),
            onFinish: () => setBusyAction(null),
        });
    };

    const generateAllCodes = () => {
        if (busyAction) {
            return;
        }

        if (confirm(`Générer un code valable 1 jour pour tous les parents actifs et l'envoyer par ${codeChannelLabel} ?`)) {
            router.post(accessRoute('generate-all'), {}, {
                preserveScroll: true,
                onStart: () => setBusyAction('all'),
                onFinish: () => setBusyAction(null),
            });
        }
    };

    const revokeCode = (codeId) => {
        if (busyAction) {
            return;
        }

        if (confirm('Êtes-vous sûr de vouloir révoquer ce code ?')) {
            router.post(accessRoute('code.revoke', { code: codeId }), {}, {
                preserveScroll: true,
                onStart: () => setBusyAction(`code-${codeId}`),
                onFinish: () => setBusyAction(null),
            });
        }
    };

    const revokeAllSessions = (parentId) => {
        if (busyAction) {
            return;
        }

        if (confirm('Êtes-vous sûr de vouloir révoquer toutes les sessions de ce parent ?')) {
            router.post(accessRoute('sessions.revoke-all', { parent: parentId }), {}, {
                preserveScroll: true,
                onStart: () => setBusyAction(`parent-${parentId}`),
                onFinish: () => setBusyAction(null),
            });
        }
    };

    const getStatusBadge = (status) => {
        const badges = {
            non_activated: { text: 'Non activé', color: 'bg-gray-100 text-gray-800' },
            code_generated: { text: 'Code généré', color: 'bg-blue-100 text-blue-800' },
            expired: { text: 'Code expiré', color: 'bg-orange-100 text-orange-800' },
            session_expired: { text: 'Session expirée', color: 'bg-orange-100 text-orange-800' },
            activated: { text: 'Session active', color: 'bg-green-100 text-green-800' },
            revoked: { text: 'Accès révoqué', color: 'bg-red-100 text-red-800' },
        };
        const badge = badges[status] || badges.non_activated;
        return <span className={`px-2 py-1 text-xs rounded-full ${badge.color}`}>{badge.text}</span>;
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Gestion des accès parents</h2>}
        >
            <Head title="Gestion des accès parents" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3">
                            {flash.success}
                        </div>
                    )}
                    {flash?.error && (
                        <div className="mb-4 bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3">
                            {flash.error}
                        </div>
                    )}
                    {generatedCode && (
                        <div className="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-green-800 font-semibold">Code généré pour {parentName}</h3>
                                    <p className="text-2xl font-mono font-bold text-green-900 mt-2">{generatedCode}</p>
                                    <p className="text-sm text-green-700 mt-1">Code valable 1 jour. Le parent le reçoit également par {codeChannelLabel}.</p>
                                </div>
                                <button onClick={() => setDismissedCode(true)} className="text-green-600 hover:text-green-800">
                                    <X className="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    )}

                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <form onSubmit={handleSearch} className="flex flex-col gap-3 lg:flex-row lg:items-center">
                                <div className="flex-1 relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                                    <input
                                        type="text"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder="Rechercher par nom, téléphone ou email..."
                                        className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                                    />
                                </div>
                                <select
                                    value={classId}
                                    onChange={(e) => {
                                        setClassId(e.target.value);
                                        applyFilters({ class_id: e.target.value });
                                    }}
                                    className="px-4 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500"
                                >
                                    <option value="">Toutes les classes</option>
                                    {classes.map((classRoom) => (
                                        <option key={classRoom.id} value={classRoom.id}>
                                            {classRoom.name}
                                        </option>
                                    ))}
                                </select>
                                <button type="submit" className="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                                    Rechercher
                                </button>
                                <button
                                    type="button"
                                    onClick={generateAllCodes}
                                    disabled={busyAction === 'all'}
                                    className="inline-flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                                >
                                    <Send className="w-4 h-4" />
                                    {busyAction === 'all' ? 'Génération...' : 'Générer et envoyer à tous'}
                                </button>
                            </form>
                        </div>
                    </div>

                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parent</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enfants</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code d'accès</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates du code</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Session</th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {parents.data.map((parent) => (
                                        <tr key={parent.id}>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div>
                                                    <div className="text-sm font-medium text-gray-900">{parent.name}</div>
                                                    <div className="text-sm text-gray-500">{parent.phone}</div>
                                                    {parent.email && <div className="text-sm text-gray-500">{parent.email}</div>}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="text-sm text-gray-900">
                                                    {parent.students_count} enfant(s)
                                                </div>
                                                <div className="text-xs text-gray-500">
                                                    {parent.students.map(s => s.name).join(', ')}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {getStatusBadge(parent.status)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {parent.latest_code?.plain_code ? (
                                                    <span className="font-mono font-semibold text-emerald-700">{parent.latest_code.plain_code}</span>
                                                ) : (
                                                    <span className="text-gray-400">—</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {parent.latest_code ? (
                                                    <div>
                                                        <div>Généré le {parent.latest_code.created_at}</div>
                                                        <div>Code expire le {parent.latest_code.expires_at}</div>
                                                        {parent.latest_code.used_at && <div className="text-green-600">Utilisé le {parent.latest_code.used_at}</div>}
                                                    </div>
                                                ) : (
                                                    <span>Aucun code</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div>{parent.active_sessions_count} active(s)</div>
                                                {parent.latest_session ? (
                                                    <div className="text-xs text-gray-500">
                                                        {parent.latest_session.is_active
                                                            ? `Jusqu'au ${parent.latest_session.expires_at}`
                                                            : parent.latest_session.revoked_at
                                                                ? `Révoquée le ${parent.latest_session.revoked_at}`
                                                                : `Expirée le ${parent.latest_session.expires_at}`}
                                                    </div>
                                                ) : (
                                                    <div className="text-xs text-gray-400">Aucune session</div>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div className="flex items-center justify-end gap-2">
                                                    <button
                                                        onClick={() => (parent.status === 'code_generated' || parent.latest_code?.used_at)
                                                            ? regenerateCode(parent.id)
                                                            : generateCode(parent.id)}
                                                        disabled={busyAction === `parent-${parent.id}`}
                                                        className="text-emerald-600 hover:text-emerald-900 disabled:opacity-50"
                                                        title="Générer un nouveau code"
                                                    >
                                                        {(parent.status === 'code_generated' || parent.latest_code?.used_at)
                                                            ? <RefreshCw className="w-4 h-4" />
                                                            : <UserPlus className="w-4 h-4" />}
                                                    </button>
                                                    {parent.status === 'code_generated' && parent.latest_code && (
                                                        <button
                                                            onClick={() => revokeCode(parent.latest_code.id)}
                                                            disabled={busyAction === `code-${parent.latest_code.id}`}
                                                            className="text-red-600 hover:text-red-900 disabled:opacity-50"
                                                            title="Révoquer le code"
                                                        >
                                                            <X className="w-4 h-4" />
                                                        </button>
                                                    )}
                                                    {parent.active_sessions_count > 0 && (
                                                        <button
                                                            onClick={() => revokeAllSessions(parent.id)}
                                                            disabled={busyAction === `parent-${parent.id}`}
                                                            className="text-orange-600 hover:text-orange-900 disabled:opacity-50"
                                                            title="Révoquer toutes les sessions"
                                                        >
                                                            <X className="w-4 h-4" />
                                                        </button>
                                                    )}
                                                    <Link
                                                        href={accessRoute('print', { parent: parent.id })}
                                                        className="text-gray-600 hover:text-gray-900"
                                                        title="Imprimer la fiche"
                                                    >
                                                        <Printer className="w-4 h-4" />
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mt-6">
                        <Pagination meta={parents} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
