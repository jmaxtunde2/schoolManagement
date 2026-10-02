import { Head, useForm } from '@inertiajs/react';
import { Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';

import GuestLayout from '@/Layouts/GuestLayout';
import Field from '@/Components/UI/Field';
import Input from '@/Components/UI/Input';
import Checkbox from '@/Components/UI/Checkbox';
import Button from '@/Components/UI/Button';

export default function Login() {
    const [showPassword, setShowPassword] = useState(false);

    const {
        data,
        setData,
        post,
        processing,
        errors,
    } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    function submit(e) {
        e.preventDefault();

        post(route('login'));
    }

    return (
        <GuestLayout>
            <Head title="Connexion" />

            <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 className="mb-5 text-center text-base font-semibold text-slate-900">
                    Connexion à votre espace
                </h2>

                <form
                    onSubmit={submit}
                    className="space-y-4"
                >
                    <Field
                        label="Adresse email"
                        required
                        error={errors.email}
                    >
                        <Input
                            type="email"
                            autoFocus
                            autoComplete="username"
                            value={data.email}
                            onChange={(e) =>
                                setData(
                                    'email',
                                    e.target.value
                                )
                            }
                        />
                    </Field>

                    <Field
                        label="Mot de passe"
                        required
                        error={errors.password}
                    >
                        <div className="relative">
                            <Input
                                type={
                                    showPassword
                                        ? 'text'
                                        : 'password'
                                }
                                autoComplete="current-password"
                                value={data.password}
                                onChange={(e) =>
                                    setData(
                                        'password',
                                        e.target.value
                                    )
                                }
                                className="pr-12"
                            />

                            <button
                                type="button"
                                onClick={() =>
                                    setShowPassword(
                                        (value) => !value
                                    )
                                }
                                className="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                aria-label={
                                    showPassword
                                        ? 'Masquer le mot de passe'
                                        : 'Afficher le mot de passe'
                                }
                                title={
                                    showPassword
                                        ? 'Masquer le mot de passe'
                                        : 'Afficher le mot de passe'
                                }
                            >
                                {showPassword ? (
                                    <EyeOff className="h-5 w-5" />
                                ) : (
                                    <Eye className="h-5 w-5" />
                                )}
                            </button>
                        </div>
                    </Field>

                    <Checkbox
                        label="Se souvenir de moi"
                        checked={data.remember}
                        onChange={(e) =>
                            setData(
                                'remember',
                                e.target.checked
                            )
                        }
                    />

                    <Button
                        type="submit"
                        className="w-full"
                        loading={processing}
                    >
                        Se connecter
                    </Button>
                </form>
            </div>
        </GuestLayout>
    );
}