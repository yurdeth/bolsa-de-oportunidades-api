import { createRouter, createWebHistory } from "vue-router";

// Define tus rutas
const routes = [
    {
        path: "/",
        component: () => import("../pages/auth/MainLogin.vue"),
    },
    {
        path: "/login",
        component: () => import("../pages/auth/MainLogin.vue"),
    },
    {
        path: "/dashboard",
        middleware: "auth",
        component: () => import("../layouts/MainLayout.vue"),
        children: [
            {
                path: "/dashboard",
                component: () => import("../pages/dashboard/MainDashboard.vue"),
            },
            {
                path: "/empresas",
                component: () => import("../pages/companies/MainCompanies.vue"),
            },
        ],
    },
    {
        path: "/register",
        component: () => import("../pages/auth/MainRegisterCompanies.vue"),
    },
];

// Configura el router
const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
