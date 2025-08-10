import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'
import ContractExpenseTable from '@/views/contract-expenses/components/ContractExpenseTable.vue'

// 🔧 Mock axios para evitar requests reales
vi.mock('@/services/axios', () => ({
  default: {
    get: vi.fn(() => Promise.resolve({ data: { data: [], meta: { total: 0 } } })),
    post: vi.fn(),
  },
}))

// 🔧 Mock utils
vi.mock('@/utils/date-formatter', () => ({ formatDate: (d) => d }))
vi.mock('@/utils/money', () => ({ formatMoney: (amt) => `$${amt}` }))
vi.mock('@/utils/collections-formatter', () => ({
  statusColor: () => 'grey',
  formatStatus: (s) => s,
}))
vi.mock('@/utils/models-formatter', () => ({ formatModelId: (id) => `CON-${id}` }))

describe('ContractExpenseTable.vue', () => {
  const factory = () => {
    return mount(ContractExpenseTable, {
      props: { contractId: null },
      global: {
        stubs: {
          // ❌ Eliminar 'v-data-table-server': true
          'v-icon': true,
          'v-btn': true,
          'v-menu': true,
          'v-list': true,
          'v-list-item': true,
          'v-list-item-title': true,
          'v-chip': true,
          'v-dialog': true,
          'v-card': true,
          'v-card-title': true,
          'v-card-text': true,
          'v-card-actions': true,
          'v-text-field': true,
          'v-form': true,
          'v-alert': true,
          'v-spacer': true,
          'RouterLink': true,
        },
      },
    })
  }
  

  it('muestra solo "Registrar pago" para tenant→tenant pendiente', async () => {
    const wrapper = factory()

    // Setear expenses directamente
    wrapper.vm.expenses = [
      { id: 1, status: { value: 'pending', label: 'Pendiente' }, paid_by: 'tenant', responsible_party: 'tenant', is_paid: false }
    ]
    await wrapper.vm.$nextTick()

    expect(wrapper.html()).toContain('mdi-cash-check') // Registrar pago
    expect(wrapper.html()).not.toContain('mdi-file-document-edit') // No facturar
    expect(wrapper.html()).not.toContain('mdi-file-document-minus') // No NC
    expect(wrapper.html()).not.toContain('mdi-file-document-check') // No liquidar
  })

  it('muestra "Facturar" para owner→tenant pendiente', async () => {
    const wrapper = factory()
    wrapper.vm.expenses = [
      { id: 2, status: { value: 'pending', label: 'Pendiente' }, paid_by: 'owner', responsible_party: 'tenant', is_paid: false }
    ]
    await wrapper.vm.$nextTick()

    expect(wrapper.html()).toContain('mdi-file-document-edit') // Facturar
  })

  it('muestra "NC" para tenant→owner pendiente', async () => {
    const wrapper = factory()
    wrapper.vm.expenses = [
      { id: 3, status: { value: 'pending', label: 'Pendiente' }, paid_by: 'tenant', responsible_party: 'owner', is_paid: false }
    ]
    await wrapper.vm.$nextTick()

    expect(wrapper.html()).toContain('mdi-file-document-minus') // NC
  })

  it('muestra "Liquidar" para tenant→owner con estado credited', async () => {
    const wrapper = factory()
    wrapper.vm.expenses = [
      { id: 4, status: { value: 'credited', label: 'NC Generada' }, paid_by: 'tenant', responsible_party: 'owner', is_paid: false }
    ]
    await wrapper.vm.$nextTick()

    expect(wrapper.html()).toContain('mdi-file-document-check') // Liquidar
  })
})
