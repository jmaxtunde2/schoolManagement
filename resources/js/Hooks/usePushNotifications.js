import { useCallback, useEffect, useState } from 'react';
import {
    deleteSubscription,
    getExistingSubscription,
    getServiceWorkerRegistration,
    isIos,
    isPushSupported,
    isStandalone,
    saveSubscription,
    urlBase64ToUint8Array,
} from '@/Utils/push';

/**
 * États possibles :
 * - checking   : détection initiale
 * - unavailable: clé VAPID absente (push non configuré côté serveur)
 * - unsupported: navigateur sans support des notifications push
 * - ios-install: iOS hors application installée (Ajouter à l'écran d'accueil)
 * - blocked    : permission refusée par l'utilisateur
 * - off / on   : abonnement inactif / actif
 * - error      : échec de l'activation
 */
export function usePushNotifications(vapidPublicKey) {
    const [status, setStatus] = useState('checking');
    const [busy, setBusy] = useState(false);
    const [message, setMessage] = useState(null);

    useEffect(() => {
        let cancelled = false;

        async function detect() {
            if (!vapidPublicKey) {
                setStatus('unavailable');
                return;
            }

            if (!isPushSupported()) {
                setStatus('unsupported');
                return;
            }

            if (isIos() && !isStandalone()) {
                setStatus('ios-install');
                return;
            }

            if (Notification.permission === 'denied') {
                setStatus('blocked');
                return;
            }

            const subscription = await getExistingSubscription();

            if (cancelled) {
                return;
            }

            setStatus(subscription ? 'on' : 'off');
        }

        detect();

        return () => {
            cancelled = true;
        };
    }, [vapidPublicKey]);

    const subscribe = useCallback(async () => {
        setBusy(true);
        setMessage(null);

        try {
            const permission = await Notification.requestPermission();

            if (permission !== 'granted') {
                setStatus('blocked');
                setMessage('Autorisation refusée : activez les notifications dans les réglages du navigateur.');
                return;
            }

            const registration = await getServiceWorkerRegistration();

            if (!registration) {
                setStatus('unsupported');
                setMessage('Service worker indisponible sur cet appareil.');
                return;
            }

            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
            });

            await saveSubscription(subscription);

            setStatus('on');
            setMessage('Notifications activées sur cet appareil.');
        } catch (error) {
            setStatus('error');
            setMessage("Impossible d'activer les notifications. Réessayez plus tard.");
        } finally {
            setBusy(false);
        }
    }, [vapidPublicKey]);

    const unsubscribe = useCallback(async () => {
        setBusy(true);
        setMessage(null);

        try {
            const subscription = await getExistingSubscription();

            if (subscription) {
                const { endpoint } = subscription;
                await subscription.unsubscribe();
                await deleteSubscription(endpoint);
            }

            setStatus('off');
            setMessage('Notifications désactivées sur cet appareil.');
        } catch (error) {
            setStatus('error');
            setMessage('Impossible de désactiver les notifications. Réessayez plus tard.');
        } finally {
            setBusy(false);
        }
    }, []);

    return { status, busy, message, subscribe, unsubscribe };
}
