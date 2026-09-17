export const USER_STORAGE_KEY = 'scm_taxvault_user_v2';

export function userFromStorage(raw) {
    if (raw == null || raw === '') {
        return null;
    }

    try {
        const parsed = JSON.parse(raw);
        if (!parsed || typeof parsed !== 'object') {
            return null;
        }
        return parsed;
    } catch (e) {
        return null;
    }
}

export function resolveAuthRedirect({ name, isPublic }, { loggedIn, isAdmin, isLoggingOut = false }) {
    if (isLoggingOut && isPublic) {
        return null;
    }

    if (!isPublic && !loggedIn) {
        return { name: 'login' };
    }

    if ((name === 'users' || name === 'settings') && !isAdmin) {
        return { path: '/dashboard' };
    }

    return null;
}
