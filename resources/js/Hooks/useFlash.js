import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/** Expose les messages flash (succès/erreur) envoyés par le serveur après une redirection. */
export function useFlash() {
    const { flash } = usePage().props;
    const [visible, setVisible] = useState(null);

    useEffect(() => {
        if (flash?.success) {
            setVisible({ type: 'success', message: flash.success });
        } else if (flash?.error) {
            setVisible({ type: 'error', message: flash.error });
        }
    }, [flash]);

    return [visible, setVisible];
}
