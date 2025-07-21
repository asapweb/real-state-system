const rentalApplicationRoutes = [
  {
    path: '',
    name: 'RentalApplicationIndex',
    component: () => import('@/views/rental-applications/RentalApplicationIndex.vue'),
  },
  {
    path: 'create',
    name: 'RentalApplicationCreate',
    component: () => import('@/views/rental-applications/RentalApplicationCreate.vue'),
  },
  {
    path: ':id',
    name: 'RentalApplicationShow',
    component: () => import('@/views/rental-applications/RentalApplicationShow.vue'),
  },
  {
    path: ':id/edit',
    name: 'RentalApplicationEdit',
    component: () => import('@/views/rental-applications/RentalApplicationEdit.vue'),
  },
];

export default rentalApplicationRoutes;
