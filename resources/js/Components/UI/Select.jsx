import { forwardRef } from 'react';
import { ChevronDown } from 'lucide-react';
import { cn } from '@/Utils/cn';

const Select = forwardRef(function Select({ className, error, children, ...props }, ref) {
    return (
        <div className="relative">
            <select
                ref={ref}
                className={cn(
                    'block w-full appearance-none rounded-lg border bg-white px-3.5 py-2.5 pr-9 text-sm text-slate-900 shadow-sm transition',
                    'min-h-[44px] focus:outline-none focus:ring-2 focus:ring-offset-0',
                    error ? 'border-red-400 focus:ring-red-300' : 'border-slate-300 focus:ring-primary/30 focus:border-primary',
                    className,
                )}
                {...props}
            >
                {children}
            </select>
            <ChevronDown className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        </div>
    );
});

export default Select;
