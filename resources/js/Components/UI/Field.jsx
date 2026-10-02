import Label from '@/Components/UI/Label';
import FieldError from '@/Components/UI/FieldError';

/** Regroupe label + champ + erreur pour éviter de répéter ce triptyque dans chaque formulaire. */
export default function Field({ label, required, error, children, hint, htmlFor }) {
    return (
        <div>
            {label && (
                <Label htmlFor={htmlFor} required={required}>
                    {label}
                </Label>
            )}
            {children}
            {hint && !error && <p className="mt-1.5 text-xs text-slate-500">{hint}</p>}
            <FieldError message={error} />
        </div>
    );
}
