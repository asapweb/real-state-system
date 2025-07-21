const collectionRoutes = [
  {
    path: '',
    name: 'CollectionsIndex',
    component: () => import('@/views/collections/CollectionIndex.vue'),
  },
  {
    path: 'create',
    name: 'CollectionCreate',
    component: () => import('@/views/collections/CollectionCreate.vue'),
  },
  {
    path: 'generation',
    name: 'CollectionGeneration',
    component: () => import('@/views/collections/CollectionGeneration.vue'),
  },
  {
    path: ':id',
    name: 'CollectionsShow',
    component: () => import('@/views/collections/CollectionShow.vue'),
  },
];

export default collectionRoutes;
