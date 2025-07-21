<template>
      <v-row class="mt-10">
        <v-col cols="12" md="6">
          <v-textarea v-model="form.notes" label="Notas" rows="4" />
        </v-col>

        <v-col cols="12" md="6">
          <v-container>
            <v-row no-gutters class="justify-end mb-1" style="font-size: 0.95rem;">
              <v-col cols="auto" class="text-right pr-2">
                <span>Importe Neto no Gravado:</span>
              </v-col>
              <v-col cols="auto" class="text-right">
                <span>$ {{ formatCurrency(totals.subtotal_untaxed || 0) }}</span>
              </v-col>
            </v-row>
            <v-row no-gutters class="justify-end mb-1" style="font-size: 0.95rem;">
              <v-col cols="auto" class="text-right pr-2">
                <span>Importe Exento:</span>
              </v-col>
              <v-col cols="auto" class="text-right">
                <span>$ {{ formatCurrency(totals.subtotal_exempt || 0) }}</span>
              </v-col>
            </v-row>
            <v-row no-gutters class="justify-end mb-1" style="font-size: 0.95rem;">
              <v-col cols="auto" class="text-right pr-2">
                <span>Importe Neto Gravado:</span>
              </v-col>
              <v-col cols="auto" class="text-right">
                <span>$ {{ formatCurrency(totals.subtotal_taxed || 0) }}</span>
              </v-col>
            </v-row>
            <v-row no-gutters class="justify-end mb-1" style="font-size: 0.95rem;">
              <v-col cols="auto" class="text-right pr-2">
                <span>Importe IVA:</span>
              </v-col>
              <v-col cols="auto" class="text-right">
                <span>$ {{ formatCurrency(totals.subtotal_vat || 0) }}</span>
              </v-col>
            </v-row>
            <v-row no-gutters class="justify-end mb-3" style="font-size: 0.95rem;">
              <v-col cols="auto" class="text-right pr-2">
                <span>Importe Otros Tributos:</span>
              </v-col>
              <v-col cols="auto" class="text-right">
                <span>$ {{ formatCurrency(totals.subtotal_other_taxes || 0) }}</span>
              </v-col>
            </v-row>
            <v-row no-gutters class="justify-end mb-1 font-weight-medium" style="font-size: 1.2rem;">
              <v-col cols="auto" class="text-right pr-2">
                <span>Total:</span>
              </v-col>
              <v-col cols="auto" class="text-right">
                <span>$ {{ formatCurrency(totals.total || 0) }}</span>
              </v-col>
            </v-row>
          </v-container>
        </v-col>
      </v-row>
</template>

<script setup>
const props = defineProps({
  form: Object,
  totals: Object,
})

function formatCurrency(amount) {
  return Number(amount || 0).toFixed(2).replace('.', ',')
}
</script>
