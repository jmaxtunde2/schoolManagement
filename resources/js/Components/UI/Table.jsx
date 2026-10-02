import { cn } from '@/Utils/cn';

/**
 * Table responsive : rendu classique en tableau à partir de `sm`, et en liste de cartes
 * empilées en dessous (voir section "responsive ≠ redimensionnement" du cahier des charges).
 * `columns`: [{ key, header, render?(row), className? }]
 */
export default function Table({ columns, rows, keyField = 'id', emptyState }) {
    if (!rows || rows.length === 0) {
        return emptyState ?? null;
    }

    return (
        <div>
            {/* Desktop / tablette */}
            <div className="hidden overflow-x-auto rounded-lg border border-slate-200 sm:block">
                <table className="w-full min-w-[640px] divide-y divide-slate-200 text-left text-sm">
                    <thead className="bg-slate-50">
                        <tr>
                            {columns.map((col) => (
                                <th key={col.key} className={cn('whitespace-nowrap px-4 py-3 font-medium text-slate-500', col.className)}>
                                    {col.header}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 bg-white">
                        {rows.map((row) => (
                            <tr key={row[keyField]} className="hover:bg-slate-50">
                                {columns.map((col) => (
                                    <td key={col.key} className={cn('px-4 py-3 align-middle text-slate-700', col.className)}>
                                        {col.render ? col.render(row) : row[col.key]}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Mobile : cartes empilées, jamais de tableau large forcé à défiler */}
            <div className="space-y-3 sm:hidden">
                {rows.map((row) => (
                    <div key={row[keyField]} className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        {columns.map((col) => (
                            <div key={col.key} className="flex items-start justify-between gap-3 py-1 text-sm first:pt-0 last:pb-0">
                                <span className="shrink-0 font-medium text-slate-500">{col.header}</span>
                                <span className="text-right text-slate-800">{col.render ? col.render(row) : row[col.key]}</span>
                            </div>
                        ))}
                    </div>
                ))}
            </div>
        </div>
    );
}
