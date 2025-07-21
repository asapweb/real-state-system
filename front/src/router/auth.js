const authRoutes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/LoginPage.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/forgot-password',
    name: 'forgot',
    component: () => import('../views/LoginPage.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../views/RegisterPage.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/display',
    name: 'display',
    component: () => import('../views/ScreenPage.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/test2',
    name: 'test2',
    component: () => import('../views/TestPage.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/unauthorized',
    name: 'unauthorized',
    component: () => import('../views/UnauthorizedPage.vue'),
    meta: { requiresAuth: false },
  },
];

export default authRoutes;
