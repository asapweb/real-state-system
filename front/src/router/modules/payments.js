const paymentRoutes = [
  {
    path: '',
    name: 'PaymentsIndex',
    component: () => import('@/views/Payments/PaymentIndex.vue'),
  },
];

export default paymentRoutes;
