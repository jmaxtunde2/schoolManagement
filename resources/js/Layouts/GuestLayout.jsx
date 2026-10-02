import { usePage } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    const { school } = usePage().props;

    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-12">
            <div className="w-full max-w-sm">
                <div className="mb-8 flex flex-col items-center text-center">
                    {school.logo_url ? (
                        <img src={school.logo_url} alt={school.name} className="mb-3 h-16 w-16 object-contain" />
                    ) : (
                        <div
                            className="mb-3 flex h-16 w-16 items-center justify-center rounded-2xl text-xl font-bold text-white"
                            style={{ backgroundColor: 'var(--color-primary)' }}
                        >
                            {school.short_name?.slice(0, 2).toUpperCase() ?? 'EC'}
                        </div>
                    )}
                    <h1 className="text-lg font-semibold text-slate-900">{school.name}</h1>
                    {school.slogan && <p className="text-sm text-slate-500">{school.slogan}</p>}
                </div>
                {children}
            </div>
        </div>
    );
}
