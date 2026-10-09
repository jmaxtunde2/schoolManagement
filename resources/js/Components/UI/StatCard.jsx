import { cn } from '@/Utils/cn';

export default function StatCard({
    icon: Icon,
    label,
    value,
    description,
    className = 'from-blue-500 to-indigo-600',
    iconBg = 'bg-white/15',
}) {
    return (
        <div
            className={cn(
                'relative overflow-hidden rounded-2xl p-5 text-white shadow-md bg-gradient-to-br',
                className
            )}
        >
            <div className="absolute -right-8 -top-8 h-24 w-24 rounded-full bg-white/10" />

            <div className="relative z-10">
                <div className="mb-4 flex items-center justify-between">
                    <div className={cn('flex h-11 w-11 items-center justify-center rounded-xl backdrop-blur-sm', iconBg)}>
                        <Icon className="h-5 w-5" />
                    </div>

                    <span className="text-3xl font-bold">
                        {value}
                    </span>
                </div>

                <p className="font-semibold">
                    {label}
                </p>

                <p className="mt-1 text-xs text-white/70">
                    {description}
                </p>
            </div>
        </div>
    );
}