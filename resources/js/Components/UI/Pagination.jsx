import { Link } from '@inertiajs/react';
import { cn } from '@/Utils/cn';

/** Consomme directement la forme d'un LengthAwarePaginator Laravel (props: links, from, to, total). */
export default function Pagination({ meta }) {
    if (!meta || !meta.links || meta.last_page <= 1) return null;

    return (
        <div className="mt-4 flex flex-col items-center justify-between gap-3 border-t border-slate-100 pt-4 sm:flex-row">
            <p className="text-sm text-slate-500">
                {meta.from ?? 0}–{meta.to ?? 0} sur {meta.total}
            </p>
            <div className="flex flex-wrap gap-1">
                {meta.links.map((link, i) => (
                    <Link
                        key={i}
                        href={link.url ?? '#'}
                        preserveScroll
                        preserveState
                        disabled={!link.url}
                        className={cn(
                            'flex min-h-[40px] min-w-[40px] items-center justify-center rounded-md px-3 text-sm',
                            link.active ? 'text-white' : 'text-slate-600 hover:bg-slate-100',
                            !link.url && 'pointer-events-none opacity-40',
                        )}
                        style={link.active ? { backgroundColor: 'var(--color-primary)' } : undefined}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ))}
            </div>
        </div>
    );
}
