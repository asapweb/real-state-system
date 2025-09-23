const contractRoutes = [
  {
    path: 'dashboard',
    name: 'contract.dashboard',
    component: () => import('@/views/contracts/ContractIndex.vue'),
  },
  {
    path: '',
    name: 'contract.index',
    component: () => import('@/views/contracts/ContractIndex.vue'),
  },
  {
    path: 'rents',
    name: 'contracts.rents',
    component: () => import('@/views/contract-rents/ContractRentIndex.vue'),
  },
  {
    path: 'rents/dashboard',
    name: 'ContractRentDashboard',
    component: () => import('@/views/contracts/ContractRentDashboard.vue'),
  },
  {
    path: 'vouchers/generation',
    name: 'ContractVouchersGeneration',
    component: () => import('@/views/contracts/ContractVouchersGeneration.vue'),
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
];

export default contractRoutes;
