import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import authRoutes from './auth'
import clientRoutes from './modules/clients'
import propertyRoutes from './modules/properties'
import rentalOfferRoutes from './modules/rentalOffers'
import rentalApplicationRoutes from './modules/rentalApplications'
import contractRoutes from './modules/contracts'
import contractAdjustmentRoutes from './modules/contractAdjustments'
import collectionRoutes from './modules/collections'
import collectionReceiptRoutes from './modules/collectionReceipts'
import contractServiceRoutes from './modules/contractServices'
import paymentRoutes from './modules/payments'
import expenseRoutes from './modules/expenses'
import maintenanceRoutes from './modules/maintenance'
import settlementRoutes from './modules/settlements'
import indexTypeRoutes from './modules/indexTypes'
import indexValueRoutes from './modules/indexValues'
import adminCommandRoutes from './modules/adminCommands'
import settingsRoutes from './modules/settings'
import voucherRoutes from './modules/vouchers'
import voucherInvoiceRoutes from './modules/vouchers/invoices'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    ...authRoutes,
    {
      path: '/',
      component: () => import('../layout/AppLayout.vue'),
      meta: { requiresAuth: false },
      children: [
        {
          path: '',
          name: 'dashboard',
          component: () => import('../views/visits/VisitsDashboardPage.vue'),
          meta: { requiresAuth: true },
        },
        { path: '/admin/commands', children: adminCommandRoutes },
        { path: '/clients', children: clientRoutes },
        { path: '/collections', children: collectionRoutes },
        { path: '/collection-receipts', children: collectionReceiptRoutes },
        { path: '/contracts', children: contractRoutes },
        { path: '/contracts/adjustments', children: contractAdjustmentRoutes },
        { path: '/contracts/services', children: contractServiceRoutes },
        { path: '/expenses', children: expenseRoutes },
        { path: '/maintenance', children: maintenanceRoutes },
        { path: '/index-types', children: indexTypeRoutes },
        { path: '/index-values', children: indexValueRoutes },
        { path: '/payments', children: paymentRoutes },
        { path: '/properties', children: propertyRoutes },
        { path: '/rental-offers', children: rentalOfferRoutes },
        { path: '/rental-applications', children: rentalApplicationRoutes },
        { path: '/settlements', children: settlementRoutes },
        { path: '/vouchers', children: voucherRoutes },
        { path: '/vouchers', children: voucherInvoiceRoutes },
        // Rutas individuales
        {
          path: '/cash-accounts',
          name: 'CashAccountIndex',
          component: () => import('@/views/cash-accounts/CashAccountIndex.vue'),
        },
        {
          path: '/cash-movements',
          name: 'CashMovementIndex',
          component: () => import('@/views/cash-movements/CashMovementIndex.vue'),
        },
        // Otras rutas individuales
        {
          path: 'visits',
          name: 'index',
          component: () => import('../views/visits/VisitsIndexPage.vue'),
          meta: { requiresAuth: true, roles: ['administrador'] },
        },
        {
          path: 'test',
          name: 'test',
          component: () => import('../views/TestPage.vue'),
          meta: { requiresAuth: true },
        },
        {
          path: 'user',
          name: 'user',
          component: () => import('../views/UserView.vue'),
          meta: { requiresAuth: true },
        },
        ...settingsRoutes,
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
  } else if (to.meta.roles) {
    // Nueva verificación de roles
    const requiredRoles = Array.isArray(to.meta.roles) ? to.meta.roles : [to.meta.roles]
    if (!authStore.hasAnyRole(requiredRoles)) {
      next('/unauthorized') // Redirige a una página de no autorizado
    } else {
      next()
    }
  } else if (to.meta.permissions) {
    // Nueva verificación de permisos
    const requiredPermissions = Array.isArray(to.meta.permissions)
      ? to.meta.permissions
      : [to.meta.permissions]
    if (!authStore.checkAnyPermission(requiredPermissions)) {
      next('/unauthorized') // Redirige a una página de no autorizado
    } else {
      next()
    }
  } else {
    // Continúa con la navegación
    next()
  }
})

export default router
