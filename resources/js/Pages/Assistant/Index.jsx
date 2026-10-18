import { useEffect, useRef, useState } from 'react';
import { Head } from '@inertiajs/react';
import {
    Bot,
    Info,
    Loader2,
    Send,
    Sparkles,
    UserRound,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Button from '@/Components/UI/Button';
import Input from '@/Components/UI/Input';
import { cn } from '@/Utils/cn';

const toneClasses = {
    positive: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    negative: 'bg-red-50 text-red-700 ring-red-200',
};

export default function AssistantIndex({ routePrefix, userName, roleLabel, suggestions }) {
    const [entries, setEntries] = useState([]);
    const [value, setValue] = useState('');
    const bottomRef = useRef(null);

    const latestSuggestions = entries.length > 0 && entries[entries.length - 1].answer
        ? entries[entries.length - 1].answer.suggestions
        : suggestions;

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth', block: 'end' });
    }, [entries]);

    function ask(question) {
        const trimmed = (question ?? '').trim();

        if (trimmed === '') {
            return;
        }

        const id = `${Date.now()}-${Math.random()}`;
        setEntries((prev) => [...prev, { id, question: trimmed, loading: true }]);
        setValue('');

        window.axios
            .post(route(`${routePrefix}.assistant.ask`), { question: trimmed })
            .then(({ data }) => {
                setEntries((prev) => prev.map((entry) => (
                    entry.id === id ? { ...entry, loading: false, answer: data } : entry
                )));
            })
            .catch(() => {
                setEntries((prev) => prev.map((entry) => (
                    entry.id === id
                        ? { ...entry, loading: false, error: 'Une erreur est survenue. Veuillez réessayer.' }
                        : entry
                )));
            });
    }

    function submit(event) {
        event.preventDefault();
        ask(value);
    }

    return (
        <AuthenticatedLayout title="CoriSchool Assistant">
            <Head title="CoriSchool Assistant" />

            <div className="mx-auto max-w-3xl space-y-6">
                <div className="flex items-start gap-4">
                    <span
                        className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-white shadow-sm"
                        style={{ backgroundColor: 'var(--color-primary)' }}
                    >
                        <Sparkles className="h-6 w-6" />
                    </span>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                            CoriSchool Assistant
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Votre assistant de pilotage. Posez vos questions sur les données de votre établissement.
                        </p>
                    </div>
                </div>

                <Card>
                    <Card.Body className="space-y-5">
                        <div>
                            <p className="text-base font-semibold text-slate-900">
                                Bonjour {userName} 👋
                            </p>
                            <p className="text-sm text-slate-500">Que souhaitez-vous savoir ?</p>
                            <p className="mt-1 text-xs text-slate-400">Connecté en tant que {roleLabel}.</p>
                        </div>

                        {entries.length === 0 && (
                            <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-600">
                                <div className="flex items-center gap-2 font-medium text-slate-700">
                                    <Info className="h-4 w-4 text-slate-400" />
                                    Comment ça marche ?
                                </div>
                                <p className="mt-2">
                                    L'assistant répond uniquement à partir des données autorisées de votre
                                    établissement (effectifs, résultats, enseignants, absences, finances).
                                    Choisissez une suggestion ou écrivez votre question ci-dessous.
                                </p>
                            </div>
                        )}

                        {entries.length > 0 && (
                            <div className="space-y-6">
                                {entries.map((entry) => (
                                    <div key={entry.id} className="space-y-4">
                                        <div className="flex items-start justify-end gap-3">
                                            <div className="max-w-[85%] rounded-2xl rounded-tr-sm bg-slate-100 px-4 py-2.5 text-sm text-slate-800">
                                                {entry.question}
                                            </div>
                                            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-200 text-slate-500">
                                                <UserRound className="h-4 w-4" />
                                            </span>
                                        </div>

                                        <div className="flex items-start gap-3">
                                            <span
                                                className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-white"
                                                style={{ backgroundColor: 'var(--color-primary)' }}
                                            >
                                                <Bot className="h-4 w-4" />
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                {entry.loading && (
                                                    <div className="flex items-center gap-2 text-sm text-slate-500">
                                                        <Loader2 className="h-4 w-4 animate-spin" />
                                                        Analyse des données…
                                                    </div>
                                                )}

                                                {entry.error && (
                                                    <div className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
                                                        {entry.error}
                                                    </div>
                                                )}

                                                {entry.answer && <AssistantAnswer answer={entry.answer} />}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {latestSuggestions?.length > 0 && (
                            <div className="flex flex-wrap gap-2">
                                {latestSuggestions.map((suggestion) => (
                                    <button
                                        key={suggestion}
                                        type="button"
                                        onClick={() => ask(suggestion)}
                                        className="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                                    >
                                        {suggestion}
                                    </button>
                                ))}
                            </div>
                        )}

                        <form onSubmit={submit} className="flex items-center gap-2">
                            <Input
                                value={value}
                                onChange={(event) => setValue(event.target.value)}
                                placeholder="Combien avons-nous d'élèves ?"
                                aria-label="Votre question"
                                autoComplete="off"
                            />
                            <Button type="submit" disabled={value.trim() === ''} className="shrink-0">
                                <Send className="h-4 w-4" />
                                <span className="hidden sm:inline">Envoyer</span>
                            </Button>
                        </form>
                    </Card.Body>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

function AssistantAnswer({ answer }) {
    return (
        <div className="space-y-4">
            <div>
                <p className="text-sm font-semibold text-slate-900">{answer.headline}</p>
                <p className="mt-1 whitespace-pre-line text-sm text-slate-600">{answer.text}</p>
            </div>

            {answer.metrics?.length > 0 && (
                <div className="flex flex-wrap gap-2">
                    {answer.metrics.map((metric, index) => (
                        <span
                            key={`${metric.label}-${index}`}
                            className={cn(
                                'rounded-xl px-3 py-1.5 text-xs font-semibold ring-1',
                                toneClasses[metric.tone] ?? 'bg-slate-100 text-slate-600 ring-slate-200',
                            )}
                        >
                            {metric.label} : <span className="font-bold">{metric.value}</span>
                        </span>
                    ))}
                </div>
            )}

            {answer.table && (
                <div className="overflow-hidden rounded-xl border border-slate-200">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-100 text-sm">
                            <thead className="bg-slate-50">
                                <tr>
                                    {answer.table.columns.map((column) => (
                                        <th
                                            key={column.key}
                                            className="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                                        >
                                            {column.label}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 bg-white">
                                {answer.table.rows.map((row, rowIndex) => (
                                    <tr key={rowIndex}>
                                        {answer.table.columns.map((column) => (
                                            <td key={column.key} className="px-4 py-2 text-slate-700">
                                                {row[column.key]}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {answer.list?.length > 0 && (
                <ul className="list-inside list-disc space-y-1 text-sm text-slate-600">
                    {answer.list.map((item, index) => (
                        <li key={index}>{item}</li>
                    ))}
                </ul>
            )}

            {answer.source && (
                <p className="text-xs italic text-slate-400">Source : {answer.source}</p>
            )}
        </div>
    );
}
