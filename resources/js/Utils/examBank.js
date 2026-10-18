export const statusTones = {
    draft: 'slate',
    in_review: 'amber',
    published: 'green',
    archived: 'red',
};

export const difficultyTones = {
    easy: 'green',
    medium: 'amber',
    hard: 'red',
};

export const difficultyBar = {
    easy: 'from-emerald-500 to-teal-600',
    medium: 'from-amber-500 to-orange-500',
    hard: 'from-rose-500 to-red-600',
};

export function formatBytes(bytes) {
    if (!bytes || bytes <= 0) return '—';

    const units = ['o', 'Ko', 'Mo', 'Go'];
    const exponent = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    const value = bytes / 1024 ** exponent;

    return `${value.toFixed(exponent === 0 ? 0 : 1)} ${units[exponent]}`;
}

export function fileLabel(name) {
    return name?.split('.').pop()?.toUpperCase() ?? 'FICHIER';
}
