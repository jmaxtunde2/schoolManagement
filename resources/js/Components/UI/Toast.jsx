import { useEffect } from 'react';
import { CheckCircle2, XCircle, X } from 'lucide-react';
import { cn } from '@/Utils/cn';

export default function Toast({ toast, onDismiss }) {
    useEffect(() => {
        if (!toast) return;
        const t = setTimeout(() => onDismiss(), 5000);
        return () => clearTimeout(t);
    }, [toast, onDismiss]);

    if (!toast) return null;

    const isSuccess = toast.type === 'success';

    return (
        <div className="fixed inset-x-4 bottom-4 z-50 sm:inset-x-auto sm:right-6 sm:bottom-6 sm:max-w-sm">
            <div
                className={cn(
                    'flex items-start gap-3 rounded-lg border p-4 shadow-lg',
                    isSuccess ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50',
                )}
            >
                {isSuccess ? (
                    <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
                ) : (
                    <XCircle className="mt-0.5 h-5 w-5 shrink-0 text-red-600" />
                )}
                <p className={cn('flex-1 text-sm', isSuccess ? 'text-emerald-800' : 'text-red-800')}>{toast.message}</p>
                <button onClick={onDismiss} aria-label="Fermer" className="text-slate-400 hover:text-slate-600">
                    <X className="h-4 w-4" />
                </button>
            </div>
        </div>
    );
}
