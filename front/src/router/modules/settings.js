const settingsRoutes = [
  {
    path: '/settings/departments',
    name: 'departments',
    component: () => import('@/views/Settings/Departments/DepartmentsIndexView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/settings',
    name: 'settings',
    component: () => import('@/views/Settings/IndexView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/settings/users',
    name: 'users',
    component: () => import('@/views/Settings/Users/UsersIndexView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/settings/users/:id',
    name: 'users.show',
    component: () => import('@/views/Settings/Users/UsersShowView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/settings/roles',
    name: 'roles',
    component: () => import('@/views/Settings/Roles/RolesIndexView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/settings/roles/:id',
    name: 'roles.show',
    component: () => import('@/views/Settings/Roles/RolesShowView.vue'),
    meta: { requiresAuth: true },
  },
];

export default settingsRoutes;
