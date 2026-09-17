import { createRouter, createWebHistory } from 'vue-router';
import AppShell from '../components/layout/AppShell.vue';
import DashboardView from '../views/DashboardView.vue';
import ProgramsView from '../views/ProgramsView.vue';
import ProgramDetailView from '../views/ProgramDetailView.vue';
import SettingsView from '../views/SettingsView.vue';
import UsersView from '../views/UsersView.vue';
import LoginView from '../views/LoginView.vue';
import { useTaxStore } from '../store/taxStore';
import { resolveAuthRedirect } from '../store/authSession';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: LoginView,
        meta: { title: 'Masuk - SCM TaxVault', public: true }
    },
    {
        path: '/register',
        name: 'register',
        component: LoginView,
        meta: { title: 'Daftar Akun - SCM TaxVault', public: true }
    },
    {
        path: '/',
        redirect: '/login'
    },
    {
        path: '/',
        component: AppShell,
        children: [
            {
                path: 'dashboard',
                name: 'dashboard',
                component: DashboardView,
                meta: { title: 'Overview - SCM TaxVault' }
            },
            {
                path: 'programs',
                name: 'programs',
                component: ProgramsView,
                meta: { title: 'Arsip Program - SCM TaxVault' }
            },
            {
                path: 'programs/:id',
                name: 'program-detail',
                component: ProgramDetailView,
                meta: { title: 'Detail Program - SCM TaxVault' }
            },
            {
                path: 'users',
                name: 'users',
                component: UsersView,
                meta: { title: 'Manajemen User - SCM TaxVault' }
            },
            {
                path: 'settings',
                name: 'settings',
                component: SettingsView,
                meta: { title: 'Pengaturan - SCM TaxVault' }
            },
        ]
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/login',
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0 };
    }
});

router.beforeEach((to, from, next) => {
    if (to.meta.title) {
        document.title = to.meta.title;
    }

    const store = useTaxStore();
    const redirect = resolveAuthRedirect(
        { name: to.name, isPublic: to.meta.public === true },
        {
            loggedIn: store.isLoggedIn.value,
            isAdmin: store.isAdmin.value,
            isLoggingOut: store.isLoggingOut.value,
        }
    );

    if (redirect) {
        return next(redirect);
    }

    next();
});

export default router;
