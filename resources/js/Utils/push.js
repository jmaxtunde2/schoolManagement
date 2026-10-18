/**
 * Web Push côté client (PWA).
 *
 * Les abonnements sont rattachés à une origine : on enregistre l'endpoint tel
 * quel et le serveur renvoie un chemin relatif dans la notification.
 */

export function isPushSupported() {
    return (
        typeof window !== 'undefined'
        && 'serviceWorker' in navigator
        && 'PushManager' in window
        && 'Notification' in window
    );
}

export function isIos() {
    if (typeof navigator === 'undefined') {
        return false;
    }

    return (
        /iPad|iPhone|iPod/.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
    );
}

/** iOS n'autorise le push que pour une app installée sur l'écran d'accueil. */
export function isStandalone() {
    if (typeof window === 'undefined') {
        return false;
    }

    return (
        window.matchMedia?.('(display-mode: standalone)').matches
        || window.navigator.standalone === true
    );
}

/** Convertit la clé publique VAPID (base64url) en Uint8Array pour `subscribe()`. */
export function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);
    const output = new Uint8Array(raw.length);

    for (let i = 0; i < raw.length; i += 1) {
        output[i] = raw.charCodeAt(i);
    }

    return output;
}

/** Retourne le service worker actif, en l'enregistrant à la demande. */
export async function getServiceWorkerRegistration() {
    if (!('serviceWorker' in navigator)) {
        return null;
    }

    let registration = await navigator.serviceWorker.getRegistration('/');

    if (!registration) {
        try {
            registration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
        } catch {
            return null;
        }
    }

    return registration;
}

export async function getExistingSubscription() {
    const registration = await getServiceWorkerRegistration();

    if (!registration) {
        return null;
    }

    return registration.pushManager.getSubscription();
}

function csrfToken() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

async function jsonRequest(method, url, body) {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': csrfToken(),
        },
        body: body ? JSON.stringify(body) : undefined,
    });

    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }

    return response.status === 204 ? null : response.json().catch(() => null);
}

/** Enregistre l'abonnement auprès du serveur (endpoint + clés). */
export async function saveSubscription(subscription) {
    const json = subscription.toJSON();

    const contentEncoding = window.PushManager?.supportedContentEncodings?.includes('aes128gcm')
        ? 'aes128gcm'
        : undefined;

    return jsonRequest('POST', window.route('push.subscriptions.store'), {
        endpoint: json.endpoint,
        keys: {
            p256dh: json.keys?.p256dh,
            auth: json.keys?.auth,
        },
        contentEncoding,
    });
}

/** Supprime l'abonnement côté serveur après désinscription locale. */
export async function deleteSubscription(endpoint) {
    return jsonRequest('DELETE', window.route('push.subscriptions.destroy'), { endpoint });
}
