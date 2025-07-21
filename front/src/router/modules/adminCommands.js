const adminCommandRoutes = [
  {
    path: '',
    name: 'AdminCommands',
    component: () => import('@/views/admin/AdminCommands.vue'),
    meta: { requiresAuth: true, roles: ['administrador'] },
  },
];

export default adminCommandRoutes;
