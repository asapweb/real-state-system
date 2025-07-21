export default [
  {
    path: 'invoices',
    name: 'VoucherInvoiceIndex',
    component: () => import('@/views/vouchers/invoices/VoucherInvoiceIndex.vue'),
  },
  {
    path: 'invoices/create',
    name: 'VoucherInvoiceCreate',
    component: () => import('@/views/vouchers/invoices/VoucherInvoiceCreate.vue'),
  },
  {
    path: 'invoices/:id',
    name: 'VoucherInvoiceShow',
    component: () => import('@/views/vouchers/invoices/VoucherInvoiceShow.vue'),
  },
]
