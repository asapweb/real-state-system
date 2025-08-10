<template>
  <v-navigation-drawer permanent :rail="rail">
    <v-list>
      <v-list-item class="font-weight-regular text-h6">
        <v-btn
          v-if="rail"
          class=""
          variant="text"
          :icon="rail ? 'mdi-chevron-right' : 'mdi-chevron-left'"
          @click.stop="rail = !rail"
        ></v-btn>
        <div v-if="!rail" class="font-weight-light">
          <span>CASSA</span>
        </div>
        <template v-slot:append>
          <v-btn
            variant="text"
            :icon="rail ? 'mdi-chevron-right' : 'mdi-chevron-left'"
            @click.stop="rail = !rail"
          ></v-btn>
        </template>
      </v-list-item>
    </v-list>
    <v-divider></v-divider>

    <v-list density="compact" nav>
      <v-list-subheader>Flujo de Alquiler</v-list-subheader>
      <template v-for="link in mainNavGeneral" :key="link.title">
        <v-list-group v-if="link.children" :value="link.title">
          <template v-slot:activator="{ props }">
            <v-list-item v-bind="props" :prepend-icon="link.icon" :title="link.title"></v-list-item>
          </template>
          <v-list-item
            v-for="child in link.children"
            :key="child.title"
            :title="child.title"
            :to="child.to"
            link
          ></v-list-item>
        </v-list-group>
        <v-list-item
          v-else
          :prepend-icon="link.icon"
          :title="link.title"
          :to="link.to"
          link
        ></v-list-item>
      </template>
    </v-list>

    <v-list density="compact" nav>
      <v-list-subheader>Parámetros</v-list-subheader>
      <template v-for="link in parametersNavLinks" :key="link.title">
        <v-list-group v-if="link.children" :value="link.title">
          <template v-slot:activator="{ props }">
            <v-list-item v-bind="props" :prepend-icon="link.icon" :title="link.title"></v-list-item>
          </template>
          <v-list-item
            v-for="child in link.children"
            :key="child.title"
            :title="child.title"
            :to="child.to"
            link
          ></v-list-item>
        </v-list-group>
        <v-list-item
          v-else
          :prepend-icon="link.icon"
          :title="link.title"
          :to="link.to"
          link
        ></v-list-item>
      </template>
    </v-list>

    <v-list density="compact" nav>
      <v-list-subheader>Gestión de fondos</v-list-subheader>
      <template v-for="link in cashNavLinks" :key="link.title">
        <v-list-group v-if="link.children" :value="link.title">
          <template v-slot:activator="{ props }">
            <v-list-item v-bind="props" :prepend-icon="link.icon" :title="link.title"></v-list-item>
          </template>
          <v-list-item
            v-for="child in link.children"
            :key="child.title"
            :title="child.title"
            :to="child.to"
            link
          ></v-list-item>
        </v-list-group>
        <v-list-item
          v-else
          :prepend-icon="link.icon"
          :title="link.title"
          :to="link.to"
          link
        ></v-list-item>
      </template>
    </v-list>

    <v-list density="compact" nav>
      <v-list-subheader>Gestion financiera</v-list-subheader>
      <template v-for="link in mainNavLinks" :key="link.title">
        <v-list-group v-if="link.children" :value="link.title">
          <template v-slot:activator="{ props }">
            <v-list-item v-bind="props" :prepend-icon="link.icon" :title="link.title"></v-list-item>
          </template>
          <v-list-item
            v-for="child in link.children"
            :key="child.title"
            :title="child.title"
            :to="child.to"
            link
          ></v-list-item>
        </v-list-group>
        <v-list-item
          v-else
          :prepend-icon="link.icon"
          :title="link.title"
          :to="link.to"
          link
        ></v-list-item>
      </template>
    </v-list>



    <v-list-item class="flex-grow-1" style="min-height: 20px"></v-list-item>

    <v-divider></v-divider>
    <v-list density="compact" nav>
      <v-list-subheader>Otros</v-list-subheader>
      <v-list-item
        prepend-icon="mdi-calendar-clock"
        link
        title="Visitas"
        to="/"
        :active="$route.path === '/'"
      ></v-list-item>
      <v-list-item
        v-for="link in secondaryNavLinks"
        :key="link.title"
        :prepend-icon="link.icon"
        :title="link.title"
        :to="link.to"
        :target="link.target"
        link
      ></v-list-item>
    </v-list>
  </v-navigation-drawer>
</template>

<script setup>
import { ref, watch } from 'vue'

const estadoGuardado = JSON.parse(localStorage.getItem('railState'))
const rail = ref(estadoGuardado ?? true) // Dejamos true por defecto para que inicie minimizado
watch(rail, (nuevoValor) => {
  localStorage.setItem('railState', JSON.stringify(nuevoValor))
})

const mainNavGeneral = [
  { icon: 'mdi-account-multiple', title: 'Clientes', to: '/clients' },
  { icon: 'mdi-domain', title: 'Propiedades', to: '/properties' },
  {
    icon: 'mdi-file-document-outline',
    title: 'Contratos',
    children: [
      { title: 'Listado', to: '/contracts', icon: 'mdi-format-list-bulleted' },
      { title: 'Gestión de Ajustes', to: '/contracts/adjustments', icon: 'mdi-cogs' },
      { title: 'Gestión de Gastos', to: '/contracts/expenses', icon: 'mdi-cogs' },
      { title: 'Pendientes de cobranza', icon: 'mdi-alert', to: '/contracts/pending-concepts' }
    ],
  },
  {
    title: 'Cobranzas',
    to: '/collections',
    icon: 'mdi-file-document-outline',
  },
  { icon: 'mdi-check-circle-outline', title: 'To Do',
    children: [
      { icon: 'mdi-home-clock-outline', title: 'Ofertas de Alquiler', to: '/rental-offers' },
      { icon: 'mdi-account-file-outline', title: 'Solicitudes y reservas', to: '/rental-applications' },
      { title: 'Gestión de Servicios', to: '/contracts/services', icon: 'mdi-room-service-outline' },
      { icon: 'mdi-hammer-wrench', title: 'Mantenimiento', to: '/maintenance' },
    ]
   },
]

const cashNavLinks = [
  { icon: 'mdi-bank', title: 'Cajas', to: '/cash-accounts' },
  { icon: 'mdi-swap-vertical', title: 'Movimientos de Caja', to: '/cash-movements' },
];
const mainNavLinks = [
  {
    title: 'Vouchers',
    to: '/vouchers',
    icon: 'mdi-file-document-multiple',
  },

  {
    title: 'Facturas',
    to: '/vouchers/invoices',
    icon: 'mdi-file-document-multiple',
  },
  { icon: 'mdi-briefcase-check-outline', title: 'Liquidaciones', to: '/settlements' },
  { icon: 'mdi-cash-multiple', title: 'Recibo de cobranza', to: '/collection-receipts' },
  { icon: 'mdi-credit-card-outline', title: 'Recibo de Pago', to: '/payments' },
  { icon: 'mdi-receipt-text-outline', title: 'Gastos', to: '/expenses' },
];

const secondaryNavLinks = [
  { icon: 'mdi-cog-outline', title: 'Configuración', to: '/settings' },
  { icon: 'mdi-monitor-multiple', title: 'Sala de espera', to: '/display', target: '_blank' },
]

const parametersNavLinks = [
  { icon: 'mdi-finance', title: 'Índices ', to: '/index-types' },
  { icon: 'mdi-chart-line-variant', title: 'Valores de Índices', to: '/index-values' },
  { icon: 'mdi-chart-line-variant', title: 'Comandos', to: '/admin/commands' },
]
</script>
