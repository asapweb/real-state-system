const indexTypeRoutes = [
  {
    path: '',
    name: 'IndexTypeIndex',
    component: () => import('@/views/index-types/IndexTypeIndex.vue'),
    meta: { requiresAuth: true },
  },
  // Descomentar si se agregan más rutas
  // {
  //   path: 'create',
  //   name: 'IndexTypeCreate',
  //   component: () => import('@/views/index-types/IndexTypeCreate.vue'),
  //   meta: { requiresAuth: true },
  // },
  // {
  //   path: ':id',
  //   name: 'IndexTypeShow',
  //   component: () => import('@/views/index-types/IndexTypeShow.vue'),
  //   meta: { requiresAuth: true },
  // },
  // {
  //   path: ':id/edit',
  //   name: 'IndexTypeEdit',
  //   component: () => import('@/views/index-types/IndexTypeEdit.vue'),
  //   meta: { requiresAuth: true },
  // },
];

export default indexTypeRoutes;
