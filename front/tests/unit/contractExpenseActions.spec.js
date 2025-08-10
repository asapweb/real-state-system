import { describe, it, expect } from 'vitest'

const canBill = (expense) =>
  ['pending', 'validated'].includes(expense.status) &&
  expense.responsible_party === 'tenant' &&
  expense.paid_by !== 'tenant'

const canRegisterPayment = (expense) =>
  expense.status === 'pending' &&
  expense.paid_by === 'tenant' &&
  expense.responsible_party === 'tenant'

const canCredit = (expense) =>
  expense.status === 'pending' &&
  expense.paid_by === 'tenant' &&
  expense.responsible_party === 'owner'

const canLiquidate = (expense) =>
  (expense.status === 'credited' && expense.paid_by === 'tenant' && expense.responsible_party === 'owner') ||
  (expense.status === 'pending' && expense.paid_by === 'agency' && expense.responsible_party === 'owner') ||
  (expense.status === 'pending' && expense.paid_by === 'owner' && expense.responsible_party === 'owner')

describe('Contract Expense Actions', () => {
  it('tenant→tenant: solo registrar pago', () => {
    const expense = { status: 'pending', paid_by: 'tenant', responsible_party: 'tenant' }
    expect(canRegisterPayment(expense)).toBe(true)
    expect(canBill(expense)).toBe(false)
    expect(canCredit(expense)).toBe(false)
    expect(canLiquidate(expense)).toBe(false)
  })

  it('owner→tenant: puede facturar', () => {
    const expense = { status: 'pending', paid_by: 'owner', responsible_party: 'tenant' }
    expect(canBill(expense)).toBe(true)
  })

  it('tenant→owner: genera NC', () => {
    const expense = { status: 'pending', paid_by: 'tenant', responsible_party: 'owner' }
    expect(canCredit(expense)).toBe(true)
    expect(canLiquidate(expense)).toBe(false)
  })

  it('tenant→owner: liquidar solo tras NC', () => {
    const expense = { status: 'credited', paid_by: 'tenant', responsible_party: 'owner' }
    expect(canLiquidate(expense)).toBe(true)
  })

  it('owner→owner: liquidar directo', () => {
    const expense = { status: 'pending', paid_by: 'owner', responsible_party: 'owner' }
    expect(canLiquidate(expense)).toBe(true)
  })
})
