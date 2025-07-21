<template>
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
      <div>&nbsp;</div>
      <div class="mt-1">Emitido: {{ voucher.issue_date }}</div>
      <div>Vencimiento: {{ voucher.due_date || '-' }}</div>
      <div>Moneda: {{ voucher.currency }}</div>
    </v-col>
  </v-row>



        <!-- Cliente -->
        <v-row class="mt-15">
          <v-col cols="12">
            <div class="text-subtitle-1 font-weight-medium mb-1">Cliente</div>
            <div>{{ voucher.client_name }}</div>
            <div>{{ voucher.client_address }}</div>
            <div>{{ voucher.client_document_type_name }} {{ voucher.client_document_number }}</div>
            <div>{{ voucher.client_tax_condition_name }}</div>
          </v-col>
        </v-row>

        <!-- Items -->
        <v-row class="mt-15">
          <v-col cols="12">
            <div class="text-subtitle-1 font-weight-medium mb-2">Detalle</div>
            <v-table density="compact">
              <thead>
                <tr>
                  <th>Descripción</th>
                  <th class="text-right">Cantidad</th>
                  <th class="text-right">Precio Unitario</th>
                  <th class="text-right">Subtotal</th>
                  <th class="text-right">IVA</th>
                  <th class="text-right">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in voucher.items" :key="item.id">
                  <td>{{ item.description }}</td>
                  <td class="text-right">{{ item.quantity }}</td>
                  <td class="text-right">{{ format(item.unit_price) }}</td>
                  <td class="text-right">{{ format(item.subtotal) }}</td>
                  <td class="text-right">{{ format(item.vat_amount) }}</td>
                  <td class="text-right">{{ format(item.subtotal_with_vat) }}</td>
                </tr>
              </tbody>
            </v-table>
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
                  <td>{{ app.applied_voucher?.full_number || `#${app.applied_to_id}` }}</td>
                  <td class="text-right">{{ format(app.amount) }}</td>
                </tr>
              </tbody>
            </v-table>
          </v-col>
        </v-row>

        <!-- Asociaciones -->
        <v-row v-if="voucher.associations?.length">
          <v-col cols="12">
            <div class="text-subtitle-1 font-weight-medium mb-2">Comprobantes Asociados</div>
            <ul>
              <li v-for="a in voucher.associations" :key="a.id">
                {{ a.associated_voucher?.full_number || `#${a.associated_voucher_id}` }}
              </li>
            </ul>
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
                <tr v-for="p in voucher.payments" :key="p.id">
                  <td>{{ p.payment_method?.name || '' }}</td>
                  <td>{{ p.cash_account?.name || '-' }}</td>
                  <td>{{ p.reference || '-' }}</td>
                  <td class="text-right">{{ format(p.amount) }}</td>
                </tr>
              </tbody>
            </v-table>
          </v-col>
        </v-row>

        <!-- Pie: Notas + Totales -->
        <v-row class="mt-15">
          <v-col cols="12" md="6">
            <div class="text-subtitle-1 font-weight-medium mb-1">Notas</div>
            <div style="white-space: pre-line;">{{ voucher.notes || '-' }}</div>
          </v-col>
          <v-col cols="12" md="6">
            <div class="w-100">
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
              <div class="d-flex justify-end mb-1" style="font-size: 1.2rem; font-weight: bold;">
                <div class="flex-grow-1 text-right pr-4">Total:</div>
                <div class="text-right" style="min-width: 120px;">{{ formatMoney(voucher.total, voucher.currency) }}</div>
              </div>
            </div>
          </v-col>
        </v-row>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/services/axios'
import { useRoute } from 'vue-router'
import { formatMoney } from '@/utils/money'

const route = useRoute()
const voucher = ref({})

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
</script>
