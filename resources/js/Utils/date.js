/** Formate une date ISO (YYYY-MM-DD) en français : 11 Novembre 1996. */
export function formatLongDate(value) {
    if (!value) {
        return '';
    }

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return date
        .toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        })
        .replace(
            /(\d{1,2}\s+)([a-zà-ÿ])/,
            (match, prefix, letter) => `${prefix}${letter.toUpperCase()}`
        );
}
