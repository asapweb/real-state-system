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
    path: ':id/collections',
    name: 'ContractCollections',
    component: () => import('@/views/contracts/ContractCollectionsView.vue'),
  },
  {
    path: ':id/edit',
    name: 'ContractEdit',
    component: () => import('@/views/contracts/ContractEdit.vue'),
  },
  {
    path: 'pending-concepts',
    name: 'PendingConcepts',
    component: () => import('@/views/contracts/ContractPendingConcepts.vue'),
  },
];

export default contractRoutes;
