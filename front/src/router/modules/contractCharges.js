const contractChargesRoutes = [
  {
    path: '',
    name: 'ContractChargeIndex',
    component: () => import('@/views/contract-charges/ContractChargeIndex.vue'),
  },
  {
    path: 'create',
    name: 'ContractChargeCreate',
    component: () => import('@/views/contract-charges/ContractChargeCreate.vue'),
  },
  {
    path: ':id/edit',
    name: 'ContractChargeEdit',
    component: () => import('@/views/contract-charges/ContractChargeEdit.vue'),
    props: true,
  },
];

export default contractChargesRoutes;
