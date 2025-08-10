const collectionRoutes = [
  {
    path: '',
    name: 'CollectionsIndex',
    component: () => import('@/views/collections/CollectionIndex.vue'),
  },
  {
    path: ':contractId/editor',
    name: 'collections.editor',
    component: () => import('@/views/collections/CollectionEditor.vue'),
    props: (route) => ({
      contractId: Number(route.params.contractId),
      period: route.query.period,
    }),
  },
  {
    path: ':contractId/view',
    name: 'CollectionView',
    component: () => import('@/views/collections/CollectionView.vue'),
    props: (route) => ({
      contractId: Number(route.params.contractId),
      period: route.query.period,
    }),
  },
];

export default collectionRoutes;
