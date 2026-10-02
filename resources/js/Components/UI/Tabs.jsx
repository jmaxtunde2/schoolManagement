import { cn } from '@/Utils/cn';

/** tabs: [{ key, label }] */
export default function Tabs({ tabs, active, onChange }) {
    return (
        <div className="border-b border-slate-200">
            <nav className="-mb-px flex gap-4 overflow-x-auto no-scrollbar" aria-label="Onglets">
                {tabs.map((tab) => {
                    const isActive = tab.key === active;

                    return (
                        <button
                            key={tab.key}
                            type="button"
                            onClick={() => onChange(tab.key)}
                            className={cn(
                                'shrink-0 whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition',
                                isActive ? 'border-current text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-700',
                            )}
                            style={isActive ? { borderColor: 'var(--color-primary)', color: 'var(--color-primary)' } : undefined}
                        >
                            {tab.label}
                        </button>
                    );
                })}
            </nav>
        </div>
    );
}
