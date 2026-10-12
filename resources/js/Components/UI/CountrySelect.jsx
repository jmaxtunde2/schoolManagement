import { useEffect, useMemo, useRef, useState } from 'react';
import { Check, ChevronDown, Search } from 'lucide-react';
import { cn } from '@/Utils/cn';
import { countries, findCountryByName, flagEmoji } from '@/Utils/countries';

function normalize(value) {
    return (value ?? '')
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
}

export default function CountrySelect({
    value,
    onChange,
    error,
    placeholder = 'Sélectionner un pays',
    id,
}) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [highlighted, setHighlighted] = useState(0);
    const containerRef = useRef(null);
    const inputRef = useRef(null);
    const listRef = useRef(null);

    const selected = useMemo(() => findCountryByName(value), [value]);

    const filtered = useMemo(() => {
        const search = normalize(query);

        if (!search) {
            return countries;
        }

        return countries.filter(
            (country) =>
                normalize(country.name).includes(search) ||
                normalize(country.code).includes(search)
        );
    }, [query]);

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        setHighlighted(0);

        const focusTimer = setTimeout(() => inputRef.current?.focus(), 0);

        function handlePointerDown(event) {
            if (containerRef.current && !containerRef.current.contains(event.target)) {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', handlePointerDown);

        return () => {
            clearTimeout(focusTimer);
            document.removeEventListener('mousedown', handlePointerDown);
        };
    }, [open]);

    useEffect(() => {
        if (!open) {
            setQuery('');
        }
    }, [open]);

    useEffect(() => {
        setHighlighted(0);
    }, [query]);

    useEffect(() => {
        if (!open || !listRef.current) {
            return;
        }

        const node = listRef.current.querySelector(`[data-index="${highlighted}"]`);
        node?.scrollIntoView({ block: 'nearest' });
    }, [highlighted, open]);

    function select(country) {
        onChange(country.name);
        setOpen(false);
    }

    function handleKeyDown(event) {
        if (!open) {
            if (['Enter', ' ', 'ArrowDown'].includes(event.key)) {
                event.preventDefault();
                setOpen(true);
            }

            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setHighlighted((index) => Math.min(index + 1, filtered.length - 1));
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setHighlighted((index) => Math.max(index - 1, 0));
        } else if (event.key === 'Enter') {
            event.preventDefault();

            if (filtered[highlighted]) {
                select(filtered[highlighted]);
            }
        } else if (event.key === 'Escape') {
            event.preventDefault();
            setOpen(false);
        }
    }

    return (
        <div ref={containerRef} className="relative" onKeyDown={handleKeyDown}>
            <button
                type="button"
                id={id}
                onClick={() => setOpen((current) => !current)}
                aria-haspopup="listbox"
                aria-expanded={open}
                className={cn(
                    'flex w-full items-center gap-2 rounded-lg border bg-white px-3.5 py-2.5 text-left text-sm text-slate-900 shadow-sm transition',
                    'min-h-[44px] focus:outline-none focus:ring-2 focus:ring-offset-0',
                    error ? 'border-red-400 focus:ring-red-300' : 'border-slate-300 focus:ring-primary/30 focus:border-primary'
                )}
            >
                {selected ? (
                    <span className="flex min-w-0 items-center gap-2">
                        <span aria-hidden="true">{flagEmoji(selected.code)}</span>
                        <span className="truncate">{selected.name}</span>
                    </span>
                ) : value ? (
                    <span className="truncate">{value}</span>
                ) : (
                    <span className="truncate text-slate-400">{placeholder}</span>
                )}

                <ChevronDown
                    className={cn(
                        'ml-auto h-4 w-4 shrink-0 text-slate-400 transition',
                        open && 'rotate-180'
                    )}
                />
            </button>

            {open && (
                <div className="absolute z-30 mt-1 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                    <div className="flex items-center gap-2 border-b border-slate-100 px-3 py-2">
                        <Search className="h-4 w-4 shrink-0 text-slate-400" />
                        <input
                            ref={inputRef}
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder="Rechercher un pays..."
                            className="w-full border-0 bg-transparent p-0 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                        />
                    </div>

                    <ul ref={listRef} role="listbox" className="max-h-64 overflow-y-auto py-1">
                        {filtered.length === 0 ? (
                            <li className="px-3 py-2 text-sm text-slate-500">Aucun pays trouvé</li>
                        ) : (
                            filtered.map((country, index) => {
                                const isSelected = country.name === value;

                                return (
                                    <li
                                        key={country.code}
                                        data-index={index}
                                        role="option"
                                        aria-selected={isSelected}
                                        onMouseEnter={() => setHighlighted(index)}
                                        onMouseDown={(event) => event.preventDefault()}
                                        onClick={() => select(country)}
                                        className={cn(
                                            'flex cursor-pointer items-center gap-2 px-3 py-2 text-sm',
                                            index === highlighted
                                                ? 'bg-primary/10 text-slate-900'
                                                : 'text-slate-700'
                                        )}
                                    >
                                        <span aria-hidden="true">{flagEmoji(country.code)}</span>
                                        <span className="truncate">{country.name}</span>
                                        {isSelected && (
                                            <Check className="ml-auto h-4 w-4 text-primary" />
                                        )}
                                    </li>
                                );
                            })
                        )}
                    </ul>
                </div>
            )}
        </div>
    );
}
