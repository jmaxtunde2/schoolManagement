import { Head, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import Input from '@/Components/UI/Input';
import Button from '@/Components/UI/Button';
import { QRCodeSVG } from 'qrcode.react';

export default function Setup({
    secret,
    otpauth_uri,
}) {
    const {
        data,
        setData,
        post,
        processing,
        errors,
    } = useForm({
        code: '',
    });

    const handleSubmit = (event) => {
        event.preventDefault();

        console.log('2FA setup form submitted');
        console.log('Code:', data.code);

        if (data.code.length !== 6) {
            console.log('Invalid code length');

            return;
        }

        console.log('Sending POST request...');

        post(route('two-factor.setup.confirm'), {
            preserveScroll: true,

            onStart: () => {
                console.log('Inertia POST started');
            },

            onSuccess: (page) => {
                console.log('2FA confirmation successful', page);
            },

            onError: (errors) => {
                console.log('2FA confirmation errors:', errors);
            },

            onFinish: () => {
                console.log('Inertia POST finished');
            },
        });
    };

    return (
        <GuestLayout>
            <Head title="Configurer Authenticator" />

            <div className="mx-auto max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

                <h1 className="text-xl font-bold text-slate-900">
                    Sécurisez votre compte
                </h1>

                <p className="mt-2 text-sm text-slate-600">
                    L’authentification par application est obligatoire.
                    Scannez ce QR code avec Google Authenticator ou une
                    application TOTP compatible.
                </p>

                {/* QR CODE */}
                <div className="my-6 flex justify-center">
                    <div className="rounded-xl border border-slate-200 bg-white p-4">
                        <QRCodeSVG
                            value={otpauth_uri}
                            size={210}
                        />
                    </div>
                </div>

                {/* SECRET MANUEL */}
                <div className="rounded-lg bg-slate-50 p-3">
                    <p className="text-xs font-medium text-slate-600">
                        Clé manuelle
                    </p>

                    <p className="mt-1 break-all font-mono text-xs text-slate-800">
                        {secret}
                    </p>
                </div>

                {/* FORMULAIRE */}
                <form
                    onSubmit={handleSubmit}
                    className="mt-5 space-y-4"
                >
                    <div>
                        <label
                            htmlFor="two-factor-code"
                            className="block text-sm font-medium text-slate-700"
                        >
                            Code à 6 chiffres
                        </label>

                        <Input
                            id="two-factor-code"
                            type="text"
                            inputMode="numeric"
                            autoComplete="one-time-code"
                            placeholder="Entrez le code à 6 chiffres"
                            maxLength={6}
                            value={data.code}
                            onChange={(event) => {
                                const value = event.target.value
                                    .replace(/\D/g, '')
                                    .slice(0, 6);

                                setData('code', value);
                            }}
                            className="mt-1"
                            autoFocus
                        />

                        {errors.code && (
                            <p className="mt-1 text-sm text-red-600">
                                {errors.code}
                            </p>
                        )}
                    </div>

                    <Button
                        type="submit"
                        className="w-full"
                        loading={processing}
                        disabled={processing || data.code.length !== 6}
                    >
                        Activer l’Authenticator
                    </Button>
                </form>
            </div>
        </GuestLayout>
    );
}
