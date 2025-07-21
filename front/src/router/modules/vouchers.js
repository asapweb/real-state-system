export default [
  {
    path: '',
    name: 'VoucherIndex',
    component: () => import('@/views/vouchers/VoucherIndex.vue'),
  },
  {
    path: 'create',
    name: 'VoucherCreate',
    component: () => import('@/views/vouchers/VoucherCreate.vue'),
  },
  {
    path: ':id',
    name: 'VoucherShow',
    component: () => import('@/views/vouchers/VoucherShow.vue'),
  },
  {
    path: ':id/edit',
    name: 'VoucherEdit',
    component: () => import('@/views/vouchers/VoucherEdit.vue'),
  },
];
