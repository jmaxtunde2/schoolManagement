export function parentRoute(space, name, params = {}) {
    const prefix = space === 'parent-access' ? 'parent-access' : 'parent';

    return route(`${prefix}.${name}`, params);
}
