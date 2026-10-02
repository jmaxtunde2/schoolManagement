import { cn } from '@/Utils/cn';

export function Skeleton({ className }) {
    return <div className={cn('animate-pulse rounded-md bg-slate-200', className)} />;
}

export function TableSkeleton({ rows = 5 }) {
    return (
        <div className="space-y-2">
            {Array.from({ length: rows }).map((_, i) => (
                <Skeleton key={i} className="h-12 w-full" />
            ))}
        </div>
    );
}
