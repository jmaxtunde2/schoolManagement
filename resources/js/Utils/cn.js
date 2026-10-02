/** Fusionne des classes conditionnelles (évite une dépendance externe pour un besoin aussi simple). */
export function cn(...classes) {
    return classes.filter(Boolean).join(' ');
}
