import { Loader2 } from 'lucide-react';
import { cn } from '@/Utils/cn';

const variants = {
    primary: 'text-primary-contrast shadow-sm hover:opacity-90 focus-visible:outline-primary',
    secondary: 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 focus-visible:outline-slate-400',
    danger: 'bg-red-600 text-white hover:bg-red-700 focus-visible:outline-red-600',
    ghost: 'text-slate-600 hover:bg-slate-100 focus-visible:outline-slate-400',
};

const sizes = {
    sm: 'px-3 py-1.5 text-sm',
    md: 'px-4 py-2.5 text-sm',
    lg: 'px-5 py-3 text-base',
};

export default function Button({
    as: Tag = 'button',
    variant = 'primary',
    size = 'md',
    loading = false,
    disabled = false,
    className,
    children,
    style,
    ...props
}) {
    const isPrimary = variant === 'primary';

    return (
        <Tag
            type={Tag === 'button' ? 'button' : undefined}
            disabled={disabled || loading}
            className={cn(
                'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-60',
                'min-h-[44px] sm:min-h-0',
                variants[variant],
                sizes[size],
                className,
            )}
            style={isPrimary ? { backgroundColor: 'var(--color-primary)', ...style } : style}
            {...props}
        >
            {loading && <Loader2 className="h-4 w-4 animate-spin" aria-hidden />}
            {children}
        </Tag>
    );
}
