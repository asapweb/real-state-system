const rentalOfferRoutes = [
  {
    path: '',
    name: 'RentalOfferIndex',
    component: () => import('@/views/rental-offers/RentalOfferIndex.vue'),
  },
  {
    path: 'create',
    name: 'RentalOfferCreate',
    component: () => import('@/views/rental-offers/RentalOfferCreate.vue'),
  },
  {
    path: ':id',
    name: 'RentalOfferShow',
    component: () => import('@/views/rental-offers/RentalOfferShow.vue'),
  },
  {
    path: ':id/edit',
    name: 'RentalOfferEdit',
    component: () => import('@/views/rental-offers/RentalOfferEdit.vue'),
  },
];

export default rentalOfferRoutes;
