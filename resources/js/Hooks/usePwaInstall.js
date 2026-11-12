import { useCallback, useEffect, useState } from 'react';

const STANDALONE_QUERY = '(display-mode: standalone)';

function detectPlatform() {
    if (typeof navigator === 'undefined') {
        return 'desktop';
    }

    const userAgent = navigator.userAgent || '';
    const isIPad = /Macintosh/i.test(userAgent) && navigator.maxTouchPoints > 1;

    if (/iphone|ipad|ipod/i.test(userAgent) || isIPad) {
        return 'ios';
    }

    if (/android/i.test(userAgent)) {
        return 'android';
    }

    return 'desktop';
}

function isStandalone() {
    if (typeof window === 'undefined') {
        return false;
    }

    return (
        window.matchMedia?.(STANDALONE_QUERY)?.matches === true
        || window.navigator.standalone === true
    );
}

/**
 * Installation de la PWA CoriSchool.
 *
 * - Chrome/Edge/Android : utilise l'événement natif `beforeinstallprompt`.
 * - iOS/iPadOS : l'API n'existe pas, on expose `isIos` pour afficher les
 *   instructions « Partager > Sur l'écran d'accueil ».
 * - Application déjà installée : `installed` passe à true et le bouton disparaît.
 */
export function usePwaInstall() {
    const [deferredPrompt, setDeferredPrompt] = useState(null);
    const [installed, setInstalled] = useState(false);
    const [platform, setPlatform] = useState('desktop');
    const [ready, setReady] = useState(false);

    useEffect(() => {
        if (typeof window === 'undefined') {
            return undefined;
        }

        setPlatform(detectPlatform());
        setInstalled(isStandalone());
        setReady(true);

        const handleBeforeInstall = (event) => {
            event.preventDefault();
            setDeferredPrompt(event);
        };

        const handleInstalled = () => {
            setInstalled(true);
            setDeferredPrompt(null);
        };

        window.addEventListener('beforeinstallprompt', handleBeforeInstall);
        window.addEventListener('appinstalled', handleInstalled);

        const media = window.matchMedia?.(STANDALONE_QUERY);
        const handleDisplayChange = (event) => setInstalled(event.matches);
        media?.addEventListener?.('change', handleDisplayChange);

        return () => {
            window.removeEventListener('beforeinstallprompt', handleBeforeInstall);
            window.removeEventListener('appinstalled', handleInstalled);
            media?.removeEventListener?.('change', handleDisplayChange);
        };
    }, []);

    const promptInstall = useCallback(async () => {
        if (!deferredPrompt) {
            return false;
        }

        deferredPrompt.prompt();

        const choice = await deferredPrompt.userChoice.catch(() => null);

        // Le prompt natif ne peut être utilisé qu'une seule fois.
        setDeferredPrompt(null);

        if (choice?.outcome === 'accepted') {
            setInstalled(true);

            return true;
        }

        return false;
    }, [deferredPrompt]);

    const isIos = platform === 'ios';
    const canInstall = ready && !installed && (Boolean(deferredPrompt) || isIos);

    return {
        canInstall,
        installed,
        isIos,
        platform,
        promptInstall,
    };
}
