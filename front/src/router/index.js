import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/Login.vue'),
      meta: { requiresAuth: false },
    },
    {
      path: '/forgot-password',
      name: 'forgot',
      component: () => import('../views/Login.vue'),
      meta: { requiresAuth: false },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/Register.vue'),
      meta: { requiresAuth: false },
    },
    {
      path: '/display',
      name: 'display',
      component: () => import('../views/Screen.vue'),
      meta: { requiresAuth: false },
    },
    {
      path: '/test2',
      name: 'test2',
      component: () => import('../views/Test.vue'),
      meta: { requiresAuth: false },
    },
    {
      path: '/unauthorized',
      name: 'unauthorized',
      component: () => import('../views/Unauthorized.vue'),
      meta: { requiresAuth: false },
    },
    {
      path: '/',
      component: () => import('../layout/Layout.vue'),
      meta: { requiresAuth: false },
      children: [
        {
          path: '',
          name: 'dashboard',
          component: () => import('../views/visits/VisitsDashboardPage.vue'),
          meta: { requiresAuth: true }, // Ejemplo de roles
        },
        {
        path: '/clients',
        children: [
            {
                path: '',
                name: 'ClientsIndex',
                component: () => import('@/views/clients/ClientIndex.vue'),
            },
            {
                path: 'create',
                name: 'ClientCreate',
                component: () => import('@/views/clients/ClientCreate.vue'),
            },
            {
                path: ':id',
                name: 'ClientShow',
                component: () => import('@/views/clients/ClientShow.vue'),
            },
            {
                path: ':id/edit',
                name: 'ClientEdit',
                component: () => import('@/views/clients/ClientEdit.vue'),
            },
        ],
    },
        {
        path: '/properties',
        children: [
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
        ],
    },
    {
        path: '/rental-offers',
        children: [
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
        ],
    },
    {
        path: '/rental-applications',
        children: [
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
        ],
    },
    {
        path: '/contracts/adjustments',
        children: [
            {
                path: '',
                name: 'ContractAdjustmentIndex',
                component: () => import('@/views/contract-adjustments/ContractAdjustmentIndex.vue'),
            },
          ],
    },
    {
        path: '/contracts',
        children: [
            {
                path: '',
                name: 'ContractIndex',
                component: () => import('@/views/contracts/ContractIndex.vue'),
            },
            {
                path: 'create',
                name: 'ContractCreate',
                component: () => import('@/views/contracts/ContractCreate.vue'),
            },
            {
                path: ':id',
                name: 'ContractShow',
                component: () => import('@/views/contracts/ContractShow.vue'),
            },
            {
                path: ':id/edit',
                name: 'ContractEdit',
                component: () => import('@/views/contracts/ContractEdit.vue'),
            },
        ],
    },
        {
        path: '/collections',
        children: [
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
        ],
    },
        {
        path: '/collection-receipts',
        children: [
            {
                path: '',
                name: 'CollectionReceiptIndex',
                component: () => import('@/views/collection-receipts/CollectionReceiptIndex.vue'),
            },
        ],
    },
        {
        path: '/contracts/services',
        children: [
            {
                path: '',
                name: 'ContractServiceIndex',
                component: () => import('@/views/contract-service/ContractServiceIndex.vue'),
            },
        ],
    },
        {
        path: '/payments',
        children: [
            {
                path: '',
                name: 'PaymentsIndex',
                component: () => import('@/views/Payments/PaymentIndex.vue'),
            },
        ],
    },
        {
        path: '/expenses',
        children: [
            {
                path: '',
                name: 'ExpensesIndex',
                component: () => import('@/views/Expenses/ExpenseIndex.vue'),
            },
        ],
    },
        {
        path: '/cashbox',
        children: [
            {
                path: '',
                name: 'CashboxIndex',
                component: () => import('@/views/CashBox/CashboxIndex.vue'),
            },
        ],
    },
        {
        path: '/maintenance',
        children: [
            {
                path: '',
                name: 'MaintenanceIndex',
                component: () => import('@/views/maintenance/MaintenanceIndex.vue'),
            },
        ],
    },
        {
        path: '/settlements',
        children: [
            {
                path: '',
                name: 'SettlementsIndex',
                component: () => import('@/views/Settlements/SettlementIndex.vue'),
            },
        ],
    },
        {
          path: 'visits',
          name: 'index',
          component: () => import('../views/visits/VisitsIndexPage.vue'),
          meta: { requiresAuth: true, roles: ['administrador'] },
        },
        {
          path: 'test',
          name: 'test',
          component: () => import('../views/Test.vue'),
          meta: { requiresAuth: true },
        },
        {
          path: 'user',
          name: 'user',
          component: () => import('../views/UserView.vue'),
          meta: { requiresAuth: true },
        },
        {
          path: '/settings/departments',
          name: 'departments',
          component: () => import('../views/Settings/Departments/DepartmentsIndexView.vue'),
          meta: { requiresAuth: true },
        },
        {
          path: '/settings',
          name: 'settings',
          component: () => import('../views/Settings/IndexView.vue'),
          meta: { requiresAuth: true },
        },
        {
          path: '/settings/users',
          name: 'users',
          component: () => import('../views/Settings/Users/UsersIndexView.vue'),
          meta: { requiresAuth: true },
        },
        {
          path: '/settings/users/:id',
          name: 'users.show',
          component: () => import('../views/Settings/Users/UsersShowView.vue'),
          meta: { requiresAuth: true },
        },
        {
          path: '/settings/roles',
          name: 'roles',
          component: () => import('../views/Settings/Roles/RolesIndexView.vue'),
          meta: { requiresAuth: true },
        },
        {
          path: '/settings/roles/:id',
          name: 'roles.show',
          component: () => import('../views/Settings/Roles/RolesShowView.vue'),
          meta: { requiresAuth: true },
        },
      ],
    },
  ],
})

// Guardia de navegación global
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  // Verifica si la ruta requiere autenticación
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    // Redirige al login si el usuario no está autenticado
    next({ name: 'login' })
  } else if (to.meta.requiresAuth === false && authStore.isAuthenticated) {
    // Redirige al dashboard si el usuario ya está autenticado
    // next({ name: 'dashboard' })
    next()
  } else if (to.meta.roles) { // Nueva verificación de roles
    const requiredRoles = Array.isArray(to.meta.roles) ? to.meta.roles : [to.meta.roles];
    if (!authStore.hasAnyRole(requiredRoles)) {
      next('/unauthorized'); // Redirige a una página de no autorizado
    } else {
      next();
    }
  } else if (to.meta.permissions) { // Nueva verificación de permisos
      const requiredPermissions = Array.isArray(to.meta.permissions) ? to.meta.permissions : [to.meta.permissions];
      if (!authStore.checkAnyPermission(requiredPermissions)) {
        next('/unauthorized'); // Redirige a una página de no autorizado
      } else {
        next();
      }
  }
  else {
    // Continúa con la navegación
    next()
  }
})

export default router
