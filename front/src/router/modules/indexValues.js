const indexValueRoutes = [
  {
    path: '',
    name: 'IndexValueIndex',
    component: () => import('@/views/index-values/IndexValueIndex.vue'),
    meta: { requiresAuth: true },
  },
];

export default indexValueRoutes;
