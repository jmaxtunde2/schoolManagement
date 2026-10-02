import { cn } from '@/Utils/cn';

export default function Card({ className, children, ...props }) {
    return (
        <div className={cn('rounded-xl border border-slate-200 bg-white shadow-sm', className)} {...props}>
            {children}
        </div>
    );
}

Card.Header = function CardHeader({ title, description, actions, className }) {
    return (
        <div className={cn('flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5', className)}>
            <div>
                {title && <h2 className="text-base font-semibold text-slate-900">{title}</h2>}
                {description && <p className="mt-0.5 text-sm text-slate-500">{description}</p>}
            </div>
            {actions && <div className="flex shrink-0 flex-wrap gap-2">{actions}</div>}
        </div>
    );
};

Card.Body = function CardBody({ className, children }) {
    return <div className={cn('p-4 sm:p-5', className)}>{children}</div>;
};
