/**
 * Configuración del plugin de Vuetify.
 *
 * Este archivo crea y configura la instancia de Vuetify para la aplicación,
 * incluyendo la importación de componentes y directivas, y la definición
 * de valores por defecto para los componentes (como la densidad de VTextField).
 */

import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import 'vuetify/styles'

const vuetify = createVuetify({
  components,
  directives,
  defaults: {
    /* --- valores para componentes específicos --- */
    VTextField:  { density: 'compact' },
    VSelect:     { density: 'compact' },
    VAutocomplete:{ density: 'compact' },
    VCombobox:   { density: 'compact' },
    VSwitch:     { color: 'primary' },

    /* --- fallback para todo lo demás --- */
    global: {

    },
  },
})

export default vuetify
