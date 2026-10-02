import { forwardRef } from 'react';
import { cn } from '@/Utils/cn';

const Textarea = forwardRef(function Textarea({ className, error, rows = 4, ...props }, ref) {
    return (
        <textarea
            ref={ref}
            rows={rows}
            className={cn(
                'block w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400',
                'focus:outline-none focus:ring-2 focus:ring-offset-0',
                error ? 'border-red-400 focus:ring-red-300' : 'border-slate-300 focus:ring-primary/30 focus:border-primary',
                className,
            )}
            {...props}
        />
    );
});

export default Textarea;
