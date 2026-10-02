import { forwardRef } from 'react';
import { cn } from '@/Utils/cn';

const Checkbox = forwardRef(function Checkbox({ label, className, ...props }, ref) {
    return (
        <label className="inline-flex min-h-[44px] cursor-pointer items-center gap-2 text-sm text-slate-700">
            <input
                ref={ref}
                type="checkbox"
                className={cn('h-4.5 w-4.5 rounded border-slate-300 text-primary focus:ring-primary/40', className)}
                style={{ accentColor: 'var(--color-primary)' }}
                {...props}
            />
            {label}
        </label>
    );
});

export default Checkbox;
