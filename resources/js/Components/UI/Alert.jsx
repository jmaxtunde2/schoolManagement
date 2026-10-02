import { AlertTriangle, Info, CheckCircle2 } from 'lucide-react';
import { cn } from '@/Utils/cn';

const styles = {
    info: { wrap: 'border-blue-200 bg-blue-50 text-blue-800', Icon: Info },
    warning: { wrap: 'border-amber-200 bg-amber-50 text-amber-800', Icon: AlertTriangle },
    success: { wrap: 'border-emerald-200 bg-emerald-50 text-emerald-800', Icon: CheckCircle2 },
};

export default function Alert({ type = 'info', title, children, className }) {
    const { wrap, Icon } = styles[type];

    return (
        <div className={cn('flex gap-3 rounded-lg border p-4 text-sm', wrap, className)}>
            <Icon className="mt-0.5 h-5 w-5 shrink-0" />
            <div>
                {title && <p className="font-medium">{title}</p>}
                {children && <div className={title ? 'mt-1' : ''}>{children}</div>}
            </div>
        </div>
    );
}
