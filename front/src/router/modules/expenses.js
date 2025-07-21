const expenseRoutes = [
  {
    path: '',
    name: 'ExpensesIndex',
    component: () => import('@/views/Expenses/ExpenseIndex.vue'),
  },
];

export default expenseRoutes;
