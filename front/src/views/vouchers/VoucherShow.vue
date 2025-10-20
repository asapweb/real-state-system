<template>
  <!-- Botones de acción (fuera del comprobante) -->
  <v-row class="mb-4">
    <v-col cols="12">
      <div class="d-flex gap-2">
        <v-btn
          v-if="voucher.status === 'draft'"
          color="primary"
          prepend-icon="mdi-pencil"
          @click="goToEdit"
        >
          Editar
        </v-btn>
        <v-btn
          v-if="voucher.status === 'draft'"
          color="success"
          prepend-icon="mdi-check-circle"
          @click="issueVoucher"
          :loading="issuing"
        >
          Emitir
        </v-btn>
        <v-btn
          v-if="!['canceled', 'cancelled'].includes(voucher.status)"
          color="error"
          prepend-icon="mdi-cancel"
          @click="cancelVoucher"
          :loading="canceling"
        >
          Cancelar este
        </v-btn>
        <v-spacer />
        <v-btn
          color="secondary"
          prepend-icon="mdi-arrow-left"
          @click="goBack"
        >
          Volver
        </v-btn>
      </div>
    </v-col>
  </v-row>

  <!-- Contenedor del comprobante (simula papel) -->
  <v-card class="voucher-container" elevation="3">
    <v-card-text class="pa-6">
      <!-- Encabezado: logo + datos del comprobante -->
      <v-row>
        <v-col cols="6">
          <div class="d-flex align-center">
            <img src="/cassa.jpg" width="130" style="flex-shrink:0; margin-right: 16px;" />
            <div>
              <span class="text-h6">CASSA GRUPO INMOBILIARIO</span><br>
              <span class="text-body-2 text-medium-emphasis">Sarmiento 247 - 8000 Bahia Blanca</span><br>
              <span class="text-body-2 text-medium-emphasis">Tel: (291) 5103589</span><br>
              <span class="text-body-2 text-medium-emphasis">administracion@cassagrupoinmobiliario.com</span><br>
              <span class="text-body-2 text-medium-emphasis">I.V.A. RESPONSABLE MONOTRIBUTO</span>
            </div>
          </div>
        </v-col>
        <v-col cols="6" class="text-right">
          <div class="text-h6 font-weight-bold">{{ voucher.full_number }}</div>
          <div>{{ voucher.voucher_type_name }}</div>
          <div class="mt-1">Emitido: {{ voucher.issue_date }}</div>
          <div>Vencimiento: {{ voucher.due_date || '-' }}</div>
          <div>Moneda: {{ voucher.currency }}</div>
          <div v-if="voucher.cae">CAE: {{ voucher.cae }}</div>
          <div v-if="voucher.cae_expires_at">CAE Vto: {{ voucher.cae_expires_at }}</div>
        </v-col>
      </v-row>

      <v-divider class="my-4" />

      <!-- Talonario y tipo de comprobante -->
      <v-row>
        <v-col cols="12" md="6">
          <div><b>Talonario:</b> {{ voucher.booklet?.name }}</div>
          <div><b>Punto de venta:</b> {{ voucher.booklet?.formatted_sale_point_number }}</div>
          <div><b>Tipo de comprobante:</b> {{ voucher.booklet?.voucher_type?.name }}</div>
        </v-col>
        <v-col cols="12" md="6">
          <div><b>Estado:</b> {{ voucher.status }}</div>
          <div v-if="voucher.period"><b>Período:</b> {{ voucher.period }}</div>
          <div v-if="voucher.service_date_from"><b>Servicio desde:</b> {{ voucher.service_date_from }}</div>
          <div v-if="voucher.service_date_to"><b>Servicio hasta:</b> {{ voucher.service_date_to }}</div>
          <div v-if="voucher.afip_operation_type"> <b>Tipo de operación AFIP:</b> {{ voucher.afip_operation_type?.name }}</div>
        </v-col>
      </v-row>

      <v-divider class="my-4" />

      <!-- Cliente y contrato -->
      <v-row>
        <v-col cols="12" md="6">
          <div class="text-subtitle-1 font-weight-medium mb-1">Cliente</div>
          <div><b>Nombre:</b> {{ voucher.client_name }}</div>
          <div><b>Dirección:</b> {{ voucher.client_address }}</div>
          <div><b>Documento:</b> {{ voucher.client_document_type_name }} {{ voucher.client_document_number }}</div>
          <div><b>Condición IVA:</b> {{ voucher.client_tax_condition_name }}</div>
          <div v-if="voucher.client_tax_id_number"><b>CUIT:</b> {{ voucher.client_tax_id_number }}</div>
          <div v-if="voucher.client"><b>Email:</b> {{ voucher.client.email }}</div>
          <div v-if="voucher.client"><b>Teléfono:</b> {{ voucher.client.phone }}</div>
        </v-col>
        <v-col cols="12" md="6" v-if="voucher.contract">
          <div class="text-subtitle-1 font-weight-medium mb-1">Contrato</div>
          <div><b>ID:</b> {{ voucher.contract.id }}</div>
          <div><b>Propiedad:</b> {{ voucher.contract.property?.street }} {{ voucher.contract.property?.number }}</div>
          <div><b>Inicio:</b> {{ voucher.contract.start_date }}</div>
          <div><b>Fin:</b> {{ voucher.contract.end_date }}</div>
          <div><b>Monto mensual:</b> {{ formatMoney(voucher.contract.monthly_amount, voucher.contract.currency) }}</div>
        </v-col>
      </v-row>

      <!-- Aplicaciones -->
      <v-row v-if="voucher.applications?.length">
        <v-col cols="12">
          <div class="text-subtitle-1 font-weight-medium mb-2">Aplicaciones</div>
          <v-table density="compact">
            <thead>
              <tr>
                <th>Número</th>
                <th class="text-right">Importe aplicado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="app in voucher.applications" :key="app.id">
                <td>
                  <RouterLink
                    v-if="app.applied_to_id"
                    :to="`/vouchers/${app.applied_to_id}`"
                    style="text-decoration: underline; color: inherit;"
                  >
                    {{ app.applied_voucher_full_number }}
                  </RouterLink>
                  <span v-else>{{ app.applied_voucher_full_number }}</span>
                </td>
                <td class="text-right">{{ formatCurrency(app.amount) }}</td>
              </tr>
            </tbody>
          </v-table>
          <!-- Total de aplicaciones para recibos -->
          <div v-if="['RCB', 'RPG'].includes(voucher.booklet?.voucher_type?.short_name) && voucher.applications_total" class="d-flex justify-end mt-2">
            <div class="text-subtitle-2 font-weight-medium">
              Total aplicaciones: {{ formatCurrency(voucher.applications_total) }}
            </div>
          </div>
        </v-col>
      </v-row>

      <!-- Ítems -->
      <v-row v-if="voucher.items?.length">
        <v-col cols="12">
          <div class="text-subtitle-1 font-weight-medium mb-2">Ítems</div>
          <v-table density="compact">
            <thead>
              <tr>
                <th class="text-left">Descripción</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">P. Unitario</th>
                <th class="text-left">Tipo IVA</th>
                <th class="text-right">Subtotal</th>
                <th class="text-right">IVA</th>
                <th class="text-right">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in voucher.items" :key="item.id">
                <td>{{ item.description }}</td>
                <td class="text-right">{{ item.quantity }}</td>
                <td class="text-right">{{ formatCurrency(item.unit_price) }}</td>
                <td class="text-left">{{ item.tax_rate?.name || '-' }}</td>
                <td class="text-right">{{ formatCurrency(item.subtotal) }}</td>
                <td class="text-right">{{ formatCurrency(item.vat_amount) }}</td>
                <td class="text-right">{{ formatCurrency(item.subtotal_with_vat) }}</td>
              </tr>
            </tbody>
          </v-table>
          <!-- Total de ítems para recibos -->
          <div v-if="['RCB', 'RPG'].includes(voucher.booklet?.voucher_type?.short_name) && voucher.items_total" class="d-flex justify-end mt-2">
            <div class="text-subtitle-2 font-weight-medium">
              Total ítems: {{ formatCurrency(voucher.items_total) }}
            </div>
          </div>
        </v-col>
      </v-row>

      <!-- Asociaciones -->
      <v-row v-if="voucher.associations?.length">
        <v-col cols="12">
          <div class="text-subtitle-1 font-weight-medium mb-2">Comprobantes asociados</div>
          <v-table density="compact">
            <thead>
              <tr>
                <th>Número</th>
                <th class="text-right">Importe</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="assoc in voucher.associations" :key="assoc.id">
                <td>{{ assoc.associated_voucher_full_number }}</td>
                <td class="text-right">{{ formatCurrency(assoc.amount) }}</td>
              </tr>
            </tbody>
          </v-table>
        </v-col>
      </v-row>

      <!-- Pagos -->
      <v-row v-if="voucher.payments?.length">
        <v-col cols="12">
          <div class="text-subtitle-1 font-weight-medium mb-2">Pagos</div>
          <v-table density="compact">
            <thead>
              <tr>
                <th>Método</th>
                <th>Cuenta</th>
                <th>Referencia</th>
                <th class="text-right">Importe</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in voucher.payments" :key="payment.id">
                <td>{{ payment.payment_method.name }}</td>
                <td>{{ payment.cash_account.name || '-' }}</td>
                <td>{{ payment.reference || '-' }}</td>
                <td class="text-right">{{ formatCurrency(payment.amount) }}</td>
              </tr>
            </tbody>
          </v-table>
          <!-- Total de pagos para recibos -->
          <div v-if="['RCB', 'RPG'].includes(voucher.booklet?.voucher_type?.short_name) && voucher.payments_total" class="d-flex justify-end mt-2">
            <div class="text-subtitle-2 font-weight-medium">
              Total pagos: {{ formatCurrency(voucher.payments_total) }}
            </div>
          </div>
        </v-col>
      </v-row>

      <!-- Debug: mostrar qué datos están llegando -->
      <v-row v-if="false">
        <v-col cols="12">
          <div class="text-subtitle-1 font-weight-medium mb-2">Debug - Datos disponibles</div>
          <div>Applications: {{ voucher.applications?.length || 0 }}</div>
          <div>Items: {{ voucher.items?.length || 0 }}</div>
          <div>Payments: {{ voucher.payments?.length || 0 }}</div>
          <div>Associations: {{ voucher.associations?.length || 0 }}</div>
          <div>Voucher type: {{ voucher.booklet?.voucher_type?.short_name }}</div>
        </v-col>
      </v-row>

      <!-- Pie: Notas + Totales -->
      <v-row class="mt-4">
        <v-col cols="12" md="6">
          <div class="text-subtitle-1 font-weight-medium mb-1">Notas</div>
          <div style="white-space: pre-line;">{{ voucher.notes || '-' }}</div>
        </v-col>
        <v-col cols="12" md="6">
          <div class="w-100">
            <!-- Desglose detallado solo para comprobantes que lo requieren -->
            <template v-if="!['RCB', 'RPG'].includes(voucher.booklet?.voucher_type?.short_name)">
              <div class="d-flex justify-end mb-1" style="font-size: 0.95rem;">
                <div class="flex-grow-1 text-right pr-4">Importe Neto no Gravado:</div>
                <div class="text-right" style="min-width: 120px;">{{ formatMoney(voucher.subtotal_untaxed, voucher.currency) }}</div>
              </div>
              <div class="d-flex justify-end mb-1" style="font-size: 0.95rem;">
                <div class="flex-grow-1 text-right pr-4">Importe Exento:</div>
                <div class="text-right" style="min-width: 120px;">{{ formatMoney(voucher.subtotal_exempt, voucher.currency) }}</div>
              </div>
              <div class="d-flex justify-end mb-1" style="font-size: 0.95rem;">
                <div class="flex-grow-1 text-right pr-4">Importe Neto Gravado:</div>
                <div class="text-right" style="min-width: 120px;">{{ formatMoney(voucher.subtotal_taxed, voucher.currency) }}</div>
              </div>
              <div class="d-flex justify-end mb-1" style="font-size: 0.95rem;">
                <div class="flex-grow-1 text-right pr-4">Importe IVA:</div>
                <div class="text-right" style="min-width: 120px;">{{ formatMoney(voucher.subtotal_vat, voucher.currency) }}</div>
              </div>
              <div class="d-flex justify-end mb-1" style="font-size: 0.95rem;">
                <div class="flex-grow-1 text-right pr-4">Importe Otros Tributos:</div>
                <div class="text-right" style="min-width: 120px;">{{ formatMoney(voucher.subtotal_other_taxes, voucher.currency) }}</div>
              </div>
            </template>
            <!-- Total único para recibos -->
            <div class="d-flex justify-end mb-1" style="font-size: 1.2rem; font-weight: bold;">
              <div class="flex-grow-1 text-right pr-4">Total:</div>
              <div class="text-right" style="min-width: 120px;">{{ formatMoney(voucher.total, voucher.currency) }}</div>
            </div>
          </div>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/services/axios'
import { useRoute, useRouter } from 'vue-router'
import { formatMoney } from '@/utils/money'
import { RouterLink } from 'vue-router'
import { useSnackbar } from '@/composables/useSnackbar'

const route = useRoute()
const router = useRouter()
const snackbar = useSnackbar()
const voucher = ref({})
const issuing = ref(false)
const canceling = ref(false)

onMounted(async () => {
  const { data } = await axios.get(`/api/vouchers/${route.params.id}`)
  voucher.value = data.data || data
})

function format(value) {
  const n = parseFloat(value || 0)
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: voucher.value.currency || 'ARS'
  }).format(n)
}

function formatCurrency(value) {
  const n = parseFloat(value || 0)
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: voucher.value.currency || 'ARS'
  }).format(n)
}

function goToEdit() {
  router.push({ name: 'VoucherEdit', params: { id: voucher.value.id } })
}

function goBack() {
  router.push({ name: 'VoucherIndex' })
}

async function issueVoucher() {
  issuing.value = true
  try {
    await axios.post(`/api/vouchers/${voucher.value.id}/issue`)
    snackbar.success('Voucher emitido correctamente')
    // Recargar el voucher para mostrar el nuevo estado
    const { data } = await axios.get(`/api/vouchers/${route.params.id}`)
    voucher.value = data.data || data
  } catch (error) {
    console.error('Error issuing voucher:', error)
    snackbar.error(error.response?.data?.message || 'Error al emitir el voucher')
  } finally {
    issuing.value = false
  }
}

async function cancelVoucher() {
  if (!confirm('¿Estás seguro de que deseas cancelar este voucher?')) {
    return
  }

  const reason = prompt('Ingresá el motivo de la cancelación (mínimo 10 caracteres):')?.trim() ?? ''
  if (!reason) {
    snackbar.info('Se canceló la acción. No se ingresó motivo.')
    return
  }

  if (reason.length < 10) {
    snackbar.error('El motivo debe tener al menos 10 caracteres.')
    return
  }

  canceling.value = true
  try {
    const { data } = await axios.post(`/api/vouchers/${voucher.value.id}/cancel`, { reason })
    const responseVoucher = data?.data ?? {}
    const message = responseVoucher.already_cancelled
      ? 'El voucher ya se encontraba cancelado.'
      : 'Voucher cancelado correctamente'
    snackbar.success(message)

    const refreshed = await axios.get(`/api/vouchers/${route.params.id}`)
    voucher.value = refreshed.data.data || refreshed.data
  } catch (error) {
    console.error('Error canceling voucher:', error)
    const { response } = error
    if (response?.data?.message) {
      const reasons = Array.isArray(response.data.reasons) ? response.data.reasons.join(', ') : null
      snackbar.error(reasons ? `${response.data.message}: ${reasons}` : response.data.message)
    } else {
      snackbar.error('Error al cancelar el voucher')
    }
  } finally {
    canceling.value = false
  }
}
</script>

<style scoped>
.voucher-container {
  background-color: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
}

.voucher-container:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>
