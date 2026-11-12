const STORAGE_KEY = 'corischool.parent.school';

/**
 * Mémorise localement le dernier établissement du parent pour que la PWA
 * puisse le proposer lorsque la session a expiré. Aucune donnée sensible :
 * uniquement le slug public et le nom de l'établissement.
 */
export function rememberParentSchool(school) {
    if (typeof window === 'undefined' || !school?.slug) {
        return;
    }

    try {
        window.localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify({ slug: school.slug, name: school.name ?? null }),
        );
    } catch (error) {
        // Stockage indisponible : la page de reprise proposera la recherche.
    }
}

export function readParentSchool() {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        const parsed = raw ? JSON.parse(raw) : null;

        return parsed?.slug ? parsed : null;
    } catch (error) {
        return null;
    }
}
