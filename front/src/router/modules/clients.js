const clientRoutes = [
  {
    path: '',
    name: 'ClientsIndex',
    component: () => import('@/views/clients/ClientIndex.vue'),
  },
  {
    path: 'create',
    name: 'ClientCreate',
    component: () => import('@/views/clients/ClientCreate.vue'),
  },
  {
    path: ':id',
    name: 'ClientShow',
    component: () => import('@/views/clients/ClientShow.vue'),
  },
  {
    path: ':id/edit',
    name: 'ClientEdit',
    component: () => import('@/views/clients/ClientEdit.vue'),
  },
];

export default clientRoutes;
