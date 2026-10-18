import { router } from '@inertiajs/react';
import {
    BellOff,
    BellRing,
    CheckCircle2,
    Info,
    Loader2,
    Send,
    Share,
    ShieldAlert,
    Smartphone,
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import { usePushNotifications } from '@/Hooks/usePushNotifications';

function StatusBanner({ tone = 'info', icon: Icon, title, children }) {
    const tones = {
        info: 'border-sky-200 bg-sky-50 text-sky-800',
        success: 'border-emerald-200 bg-emerald-50 text-emerald-800',
        warning: 'border-amber-200 bg-amber-50 text-amber-800',
        danger: 'border-rose-200 bg-rose-50 text-rose-800',
    };

    return (
        <div className={`flex items-start gap-3 rounded-xl border p-4 ${tones[tone]}`}>
            {Icon && (
                <span className="mt-0.5 shrink-0">
                    <Icon className="h-5 w-5" />
                </span>
            )}
            <div>
                <p className="font-semibold">{title}</p>
                {children && <div className="mt-1 text-sm">{children}</div>}
            </div>
        </div>
    );
}

export default function PushNotifications({ vapidPublicKey, subscriptions = [] }) {
    const { status, busy, message, subscribe, unsubscribe } = usePushNotifications(vapidPublicKey);

    function sendTest() {
        router.post(route('parent.notifications.test'), {}, { preserveScroll: true });
    }

    return (
        <AuthenticatedLayout title="Notifications">
            <div className="mx-auto max-w-3xl space-y-6">
                <div className="rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50 p-6 shadow-sm">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-700">
                                Application mobile
                            </p>
                            <h2 className="mt-2 text-2xl font-bold text-slate-900">Notifications</h2>
                            <p className="mt-1 text-sm text-slate-600">
                                Recevez les notes, bulletins et messages de l&apos;établissement directement
                                sur votre appareil, même lorsque l&apos;application est fermée.
                            </p>
                        </div>
                        <div className="rounded-full bg-white p-3 text-emerald-600 shadow-sm">
                            <BellRing className="h-6 w-6" />
                        </div>
                    </div>
                </div>

                <Card>
                    <Card.Header
                        title="Cet appareil"
                        description="Activez ou désactivez les notifications pour le navigateur que vous utilisez actuellement."
                    />
                    <Card.Body className="space-y-4">
                        {status === 'checking' && (
                            <div className="flex items-center gap-2 text-sm text-slate-500">
                                <Loader2 className="h-4 w-4 animate-spin" />
                                Vérification de l&apos;appareil…
                            </div>
                        )}

                        {status === 'unavailable' && (
                            <StatusBanner tone="warning" icon={ShieldAlert} title="Service indisponible">
                                Les notifications push ne sont pas encore configurées par
                                l&apos;établissement. Contactez le secrétariat.
                            </StatusBanner>
                        )}

                        {status === 'unsupported' && (
                            <StatusBanner tone="warning" icon={Info} title="Navigateur non compatible">
                                Votre navigateur ne prend pas en charge les notifications push.
                                Essayez Google Chrome, Microsoft Edge ou Safari.
                            </StatusBanner>
                        )}

                        {status === 'ios-install' && (
                            <StatusBanner tone="info" icon={Share} title="Installez l'application">
                                Sur iPhone ou iPad, ajoutez d&apos;abord CoriSchool à votre écran
                                d&apos;accueil : appuyez sur <strong>Partager</strong> puis
                                <strong> Sur l&apos;écran d&apos;accueil</strong>, ouvrez l&apos;application
                                installée et revenez ici.
                            </StatusBanner>
                        )}

                        {status === 'blocked' && (
                            <StatusBanner tone="danger" icon={BellOff} title="Notifications bloquées">
                                Les notifications ont été refusées pour ce navigateur. Autorisez-les
                                depuis les réglages du site, puis rechargez la page.
                            </StatusBanner>
                        )}

                        {(status === 'off' || status === 'on' || status === 'error') && (
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div className="flex items-center gap-2 text-sm">
                                    {status === 'on' ? (
                                        <>
                                            <CheckCircle2 className="h-5 w-5 text-emerald-600" />
                                            <span className="font-medium text-emerald-700">
                                                Notifications activées sur cet appareil
                                            </span>
                                        </>
                                    ) : (
                                        <>
                                            <BellOff className="h-5 w-5 text-slate-400" />
                                            <span className="font-medium text-slate-600">
                                                Notifications désactivées
                                            </span>
                                        </>
                                    )}
                                </div>

                                <div className="flex flex-wrap gap-2">
                                    {status === 'on' ? (
                                        <>
                                            <Button variant="secondary" onClick={sendTest} disabled={busy}>
                                                <Send className="h-4 w-4" />
                                                Tester
                                            </Button>
                                            <Button variant="ghost" onClick={unsubscribe} loading={busy}>
                                                Désactiver
                                            </Button>
                                        </>
                                    ) : (
                                        <Button onClick={subscribe} loading={busy}>
                                            <BellRing className="h-4 w-4" />
                                            Activer les notifications
                                        </Button>
                                    )}
                                </div>
                            </div>
                        )}

                        {message && <p className="text-sm text-slate-600">{message}</p>}
                    </Card.Body>
                </Card>

                <Card>
                    <Card.Header
                        title="Appareils abonnés"
                        description="Les appareils qui reçoivent déjà les notifications."
                    />
                    <Card.Body>
                        {subscriptions.length === 0 ? (
                            <p className="text-sm text-slate-500">
                                Aucun appareil abonné pour le moment.
                            </p>
                        ) : (
                            <ul className="divide-y divide-slate-100">
                                {subscriptions.map((subscription) => (
                                    <li
                                        key={subscription.id}
                                        className="flex items-center justify-between gap-3 py-3"
                                    >
                                        <div className="flex items-center gap-3">
                                            <Smartphone className="h-5 w-5 text-slate-400" />
                                            <div>
                                                <p className="text-sm font-medium text-slate-800">
                                                    {subscription.device}
                                                </p>
                                                {subscription.domain && (
                                                    <p className="text-xs text-slate-500">
                                                        {subscription.domain}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                        {subscription.last_used_at && (
                                            <span className="text-xs text-slate-400">
                                                Vu {subscription.last_used_at}
                                            </span>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card.Body>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
