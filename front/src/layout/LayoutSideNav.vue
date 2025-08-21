<template>
  <v-navigation-drawer
    permanent
    :rail="rail"
  >
    <!-- Header con toggle -->
    <v-list>
      <v-list-item class="px-2">
        <template v-slot:prepend>
          <v-btn
            variant="text"
            :icon="rail ? 'mdi-menu' : 'mdi-menu-open'"
            size="small"
            @click.stop="rail = !rail"
          />
        </template>

        <v-list-item-title v-if="!rail" class="font-weight-light text-h6 ml-2">
          CASSA
        </v-list-item-title>
      </v-list-item>

      <v-divider class="my-2" />
    </v-list>

    <!-- Gestión de Alquileres -->
    <v-list density="compact" nav>
      <v-list-subheader v-if="!rail">Gestión de Alquileres</v-list-subheader>
      <template v-for="link in mainNavGeneral" :key="link.title">
        <v-list-group v-if="link.children && !rail" :value="link.title">
          <template v-slot:activator="{ props }">
            <v-list-item
              v-bind="props"
              :prepend-icon="link.icon"
              :title="link.title"
            />
          </template>
          <v-list-item
            v-for="child in link.children"
            :key="child.title"
            :title="child.title"
            :to="child.to"
            link
            class="ml-4"
          />
        </v-list-group>

        <!-- Versión colapsada o item simple -->
        <v-list-item
          v-else
          :prepend-icon="link.icon"
          :title="rail ? undefined : link.title"
          :to="link.children ? undefined : link.to"
          :link="!link.children"
        >
          <v-tooltip
            v-if="rail && link.title"
            activator="parent"
            location="end"
            :text="link.title"
          />
        </v-list-item>
      </template>
    </v-list>

    <!-- Gestión financiera -->
    <v-list density="compact" nav>
      <v-list-subheader v-if="!rail">Gestión financiera</v-list-subheader>
      <template v-for="link in mainNavLinks" :key="link.title">
        <v-list-group v-if="link.children && !rail" :value="link.title">
          <template v-slot:activator="{ props }">
            <v-list-item
              v-bind="props"
              :prepend-icon="link.icon"
              :title="link.title"
            />
          </template>
          <v-list-item
            v-for="child in link.children"
            :key="child.title"
            :title="child.title"
            :to="child.to"
            link
            class="ml-4"
          />
        </v-list-group>

        <v-list-item
          v-else
          :prepend-icon="link.icon"
          :title="rail ? undefined : link.title"
          :to="link.children ? undefined : link.to"
          :link="!link.children"
        >
          <v-tooltip
            v-if="rail && link.title"
            activator="parent"
            location="end"
            :text="link.title"
          />
        </v-list-item>
      </template>
    </v-list>

    <!-- Gestión de fondos -->
    <v-list density="compact" nav>
      <v-list-subheader v-if="!rail">Gestión de fondos</v-list-subheader>
      <template v-for="link in cashNavLinks" :key="link.title">
        <v-list-item
          :prepend-icon="link.icon"
          :title="rail ? undefined : link.title"
          :to="link.to"
          link
        >
          <v-tooltip
            v-if="rail"
            activator="parent"
            location="end"
            :text="link.title"
          />
        </v-list-item>
      </template>
    </v-list>

    <!-- Parámetros -->
    <v-list density="compact" nav>
      <v-list-subheader v-if="!rail">Parámetros</v-list-subheader>
      <template v-for="link in parametersNavLinks" :key="link.title">
        <v-list-item
          :prepend-icon="link.icon"
          :title="rail ? undefined : link.title"
          :to="link.to"
          link
        >
          <v-tooltip
            v-if="rail"
            activator="parent"
            location="end"
            :text="link.title"
          />
        </v-list-item>
      </template>
    </v-list>

    <!-- Otros -->
    <v-list density="compact" nav>
      <v-list-subheader v-if="!rail">Otros</v-list-subheader>

      <!-- Visitas -->
      <v-list-item
        prepend-icon="mdi-calendar-clock"
        :title="rail ? undefined : 'Visitas'"
        to="/"
        :active="$route.path === '/'"
        link
      >
        <v-tooltip
          v-if="rail"
          activator="parent"
          location="end"
          text="Visitas"
        />
      </v-list-item>

      <!-- Resto de enlaces secundarios -->
      <template v-for="link in secondaryNavLinks" :key="link.title">
        <v-list-group v-if="link.children && !rail" :value="link.title">
          <template v-slot:activator="{ props }">
            <v-list-item
              v-bind="props"
              :prepend-icon="link.icon"
              :title="link.title"
            />
          </template>
          <v-list-item
            v-for="child in link.children"
            :key="child.title"
            :title="child.title"
            :to="child.to"
            link
            class="ml-4"
          />
        </v-list-group>

        <v-list-item
          v-else
          :prepend-icon="link.icon"
          :title="rail ? undefined : link.title"
          :to="link.children ? undefined : link.to"
          :target="link.target"
          :link="!link.children"
        >
          <v-tooltip
            v-if="rail && link.title"
            activator="parent"
            location="end"
            :text="link.title"
          />
        </v-list-item>
      </template>
    </v-list>
  </v-navigation-drawer>
</template>

<script setup>
import { ref, watch } from 'vue'

const estadoGuardado = JSON.parse(localStorage.getItem('railState'))
const rail = ref(estadoGuardado ?? true)

watch(rail, (nuevoValor) => {
  localStorage.setItem('railState', JSON.stringify(nuevoValor))
})

const mainNavGeneral = [
  { icon: 'mdi-account-multiple', title: 'Clientes', to: '/clients' },
  { icon: 'mdi-domain', title: 'Propiedades', to: '/properties' },
  {
    icon: 'mdi-file-sign',
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
    icon: 'mdi-hand-coin-outline',
  },
]

const cashNavLinks = [
  { icon: 'mdi-bank', title: 'Cajas', to: '/cash-accounts' },
  { icon: 'mdi-swap-vertical', title: 'Movimientos de Caja', to: '/cash-movements' },
]

const mainNavLinks = [
  {
    title: 'Comprobantes',
    to: '/vouchers',
    icon: 'mdi-file-document-multiple-outline',
  },
  {
    title: 'Facturas',
    to: '/vouchers/invoices',
    icon: 'mdi-invoice-text-outline',
  },
]

const secondaryNavLinks = [
  { icon: 'mdi-cog-outline', title: 'Configuración', to: '/settings' },
  { icon: 'mdi-monitor-multiple', title: 'Sala de espera', to: '/display', target: '_blank' },
  {
    icon: 'mdi-check-circle-outline',
    title: 'To Do',
    children: [
      { icon: 'mdi-home-clock-outline', title: 'Ofertas de Alquiler', to: '/rental-offers' },
      { icon: 'mdi-account-file-outline', title: 'Solicitudes y reservas', to: '/rental-applications' },
      { title: 'Gestión de Servicios', to: '/contracts/services', icon: 'mdi-room-service-outline' },
      { icon: 'mdi-hammer-wrench', title: 'Mantenimiento', to: '/maintenance' },
    ]
  },
]

const parametersNavLinks = [
  { icon: 'mdi-finance', title: 'Índices', to: '/index-types' },
  { icon: 'mdi-chart-line-variant', title: 'Valores de Índices', to: '/index-values' },
  { icon: 'mdi-chart-line-variant', title: 'Comandos', to: '/admin/commands' },
]
</script>

<style>
/* --- ESTILOS PARA LA BARRA DE SCROLL DEL NAVIGATION DRAWER --- */

/* Seleccionamos el contenedor del contenido dentro del drawer */
.v-navigation-drawer__content::-webkit-scrollbar {
  width: 6px; /* Ancho de la barra */
}

/* Por defecto, hacemos la barra de scroll invisible */
.v-navigation-drawer__content::-webkit-scrollbar-thumb {
  background-color: transparent;
}

/* Cuando el mouse está sobre CUALQUIER PARTE del drawer... */
.v-navigation-drawer:hover .v-navigation-drawer__content::-webkit-scrollbar-thumb {
  /* ...hacemos visible la barra de scroll con un color suave */
  background-color: #cccccc;
  border-radius: 3px;
}

/* Compatibilidad para Firefox */
.v-navigation-drawer__content {
  /* Ocultamos la barra por defecto */
  scrollbar-width: thin;
  scrollbar-color: transparent transparent;
}

.v-navigation-drawer:hover .v-navigation-drawer__content {
  /* Mostramos la barra en hover */
  scrollbar-color: #cccccc transparent;
}
</style>
