import { cn } from '@/Utils/cn';

const HEX_PATTERN = /^#[0-9A-Fa-f]{0,6}$/;

/** Sélecteur de couleur natif + champ hexadécimal éditable, avec aperçu immédiat côté formulaire. */
export default function ColorPicker({ label, value, onChange, error }) {
    return (
        <div>
            {label && <p className="mb-1.5 text-sm font-medium text-slate-700">{label}</p>}
            <div className="flex items-center gap-3">
                <input
                    type="color"
                    value={value || '#000000'}
                    onChange={(e) => onChange(e.target.value.toUpperCase())}
                    className="h-11 w-14 cursor-pointer rounded-lg border border-slate-300 bg-white p-1"
                    aria-label={label}
                />
                <input
                    type="text"
                    value={value || ''}
                    onChange={(e) => {
                        const v = e.target.value;
                        if (HEX_PATTERN.test(v)) onChange(v.toUpperCase());
                    }}
                    maxLength={7}
                    placeholder="#1E40AF"
                    className={cn(
                        'min-h-[44px] w-32 rounded-lg border px-3 py-2 font-mono text-sm uppercase text-slate-900 focus:outline-none focus:ring-2',
                        error ? 'border-red-400 focus:ring-red-300' : 'border-slate-300 focus:ring-primary/30 focus:border-primary',
                    )}
                />
            </div>
            {error && <p className="mt-1.5 text-sm text-red-600">{error}</p>}
        </div>
    );
}
