const contractRoutes = [
  {
    path: '',
    name: 'ContractIndex',
    component: () => import('@/views/contracts/ContractIndex.vue'),
  },
  {
    path: 'create',
    name: 'ContractCreate',
    component: () => import('@/views/contracts/ContractCreate.vue'),
  },
  {
    path: ':id',
    name: 'ContractShow',
    component: () => import('@/views/contracts/ContractShow.vue'),
  },
  {
    path: ':id/edit',
    name: 'ContractEdit',
    component: () => import('@/views/contracts/ContractEdit.vue'),
  },
];

export default contractRoutes;
