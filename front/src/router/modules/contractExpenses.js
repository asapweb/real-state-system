const contractExpensesRoutes = [
  {
    path: '',
    name: 'ContractExpenseIndex',
    component: () => import('@/views/contract-expenses/ContractExpenseIndex.vue'),
  },
  // {
  //   path: 'create',
  //   name: 'ContractExpenseCreate',
  //   component: () => import('@/views/contract-expenses/ContractExpenseCreate.vue'),
  // },
  // {
  //   path: ':id',
  //   name: 'ContractExpenseShow',
  //   component: () => import('@/views/contract-expenses/ContractExpenseShow.vue'),
  // },
  // {
  //   path: ':id/edit',
  //   name: 'ContractExpenseEdit',
  //   component: () => import('@/views/contract-expenses/ContractExpenseEdit.vue'),
  // },
];

export default contractExpensesRoutes;
