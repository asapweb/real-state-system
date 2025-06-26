/**
 * Configuración del plugin de Vuetify.
 *
 * Este archivo crea y configura la instancia de Vuetify para la aplicación,
 * incluyendo la importación de componentes y directivas, y la definición
 * de valores por defecto para los componentes (como la densidad de VTextField).
 */

import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import 'vuetify/styles';

const vuetify = createVuetify({
  components,
  directives,
  defaults: {
    VTextField: {
      // density: 'comfortable',
    },
    global: {
      density: 'compact',
    },
    VSwitch: {
      color: 'primary', // o 'primary' si quieres usar el color primario de tu tema
    },

    // Aquí puedes agregar defaults para otros componentes si lo necesitas
  },
});

export default vuetify;
