const propertyRoutes = [
  {
    path: '',
    name: 'PropertiesIndex',
    component: () => import('@/views/properties/PropertiesIndex.vue'),
  },
  {
    path: 'create',
    name: 'PropertyCreate',
    component: () => import('@/views/properties/PropertyCreate.vue'),
  },
  {
    path: ':id',
    name: 'PropertyShow',
    component: () => import('@/views/properties/PropertyShow.vue'),
  },
  {
    path: ':id/edit',
    name: 'PropertyEdit',
    component: () => import('@/views/properties/PropertyEdit.vue'),
  },
];

export default propertyRoutes;
