import { useRef, useState } from 'react';
import { UploadCloud, X } from 'lucide-react';
import { cn } from '@/Utils/cn';

/** Zone d'upload d'image avec aperçu immédiat, pensée tactile (grande zone cliquable). */
export default function FileUpload({ label, previewUrl, onChange, onRemove, error, accept = 'image/png,image/jpeg,image/webp', hint }) {
    const inputRef = useRef(null);
    const [localPreview, setLocalPreview] = useState(null);

    function handleFile(file) {
        if (!file) return;
        onChange?.(file);
        setLocalPreview(URL.createObjectURL(file));
    }

    const shown = localPreview ?? previewUrl;

    return (
        <div>
            {label && <p className="mb-1.5 text-sm font-medium text-slate-700">{label}</p>}
            <div className="flex items-center gap-4">
                <div className="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                    {shown ? (
                        <img src={shown} alt="Aperçu du logo" className="h-full w-full object-contain" />
                    ) : (
                        <UploadCloud className="h-6 w-6 text-slate-300" />
                    )}
                </div>
                <div className="flex flex-col gap-2 sm:flex-row">
                    <button
                        type="button"
                        onClick={() => inputRef.current?.click()}
                        className={cn(
                            'min-h-[44px] rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50',
                            error && 'border-red-400',
                        )}
                    >
                        Choisir une image
                    </button>
                    {shown && (
                        <button
                            type="button"
                            onClick={() => {
                                setLocalPreview(null);
                                onRemove?.();
                            }}
                            className="inline-flex min-h-[44px] items-center justify-center gap-1 rounded-lg px-3 text-sm text-red-600 hover:bg-red-50"
                        >
                            <X className="h-4 w-4" /> Retirer
                        </button>
                    )}
                </div>
                <input
                    ref={inputRef}
                    type="file"
                    accept={accept}
                    className="hidden"
                    onChange={(e) => handleFile(e.target.files?.[0])}
                />
            </div>
            {hint && !error && <p className="mt-1.5 text-xs text-slate-500">{hint}</p>}
            {error && <p className="mt-1.5 text-sm text-red-600">{error}</p>}
        </div>
    );
}
