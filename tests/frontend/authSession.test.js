import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import { userFromStorage, resolveAuthRedirect } from '../../resources/js/store/authSession.js';

describe('userFromStorage', () => {
    it('returns null when storage is empty so logout survives refresh', () => {
        assert.equal(userFromStorage(null), null);
        assert.equal(userFromStorage(undefined), null);
        assert.equal(userFromStorage(''), null);
    });

    it('returns the parsed user when storage has a session', () => {
        const user = { id: 'usr-1', email: 'admin@scm.corp', role: 'Admin SCM' };
        assert.deepEqual(userFromStorage(JSON.stringify(user)), user);
    });

    it('returns null when stored JSON is invalid', () => {
        assert.equal(userFromStorage('{not-json'), null);
    });
});

describe('resolveAuthRedirect', () => {
    it('sends guests from protected pages to login', () => {
        assert.deepEqual(
            resolveAuthRedirect({ name: 'dashboard', isPublic: false }, { loggedIn: false, isAdmin: false }),
            { name: 'login' }
        );
        assert.deepEqual(
            resolveAuthRedirect({ name: 'programs', isPublic: false }, { loggedIn: false, isAdmin: false }),
            { name: 'login' }
        );
    });

    it('allows guests to stay on login and register', () => {
        assert.equal(
            resolveAuthRedirect({ name: 'login', isPublic: true }, { loggedIn: false, isAdmin: false }),
            null
        );
        assert.equal(
            resolveAuthRedirect({ name: 'register', isPublic: true }, { loggedIn: false, isAdmin: false }),
            null
        );
    });

    it('allows navigating to login while a logout is in progress', () => {
        assert.equal(
            resolveAuthRedirect(
                { name: 'login', isPublic: true },
                { loggedIn: true, isAdmin: true, isLoggingOut: true }
            ),
            null
        );
    });

    it('allows navigating to login and register even when logged in without auto-redirect', () => {
        assert.equal(
            resolveAuthRedirect({ name: 'login', isPublic: true }, { loggedIn: true, isAdmin: true }),
            null
        );
        assert.equal(
            resolveAuthRedirect({ name: 'register', isPublic: true }, { loggedIn: true, isAdmin: true }),
            null
        );
    });

    it('keeps non-admin users off users and settings pages', () => {
        assert.deepEqual(
            resolveAuthRedirect({ name: 'users', isPublic: false }, { loggedIn: true, isAdmin: false }),
            { path: '/dashboard' }
        );
        assert.deepEqual(
            resolveAuthRedirect({ name: 'settings', isPublic: false }, { loggedIn: true, isAdmin: false }),
            { path: '/dashboard' }
        );
    });

    it('allows authenticated users through protected pages', () => {
        assert.equal(
            resolveAuthRedirect({ name: 'dashboard', isPublic: false }, { loggedIn: true, isAdmin: false }),
            null
        );
        assert.equal(
            resolveAuthRedirect({ name: 'users', isPublic: false }, { loggedIn: true, isAdmin: true }),
            null
        );
    });
});
