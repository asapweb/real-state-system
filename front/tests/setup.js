import { vi } from 'vitest'

// Mock global de window para evitar errores
global.window = Object.create(window)
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation(() => ({
    matches: false,
    addListener: vi.fn(),
    removeListener: vi.fn(),
  })),
})

// Mock del router
vi.mock('@/router', () => ({
  default: {
    push: vi.fn(),
    replace: vi.fn(),
  },
}))

// Mock de axios
vi.mock('@/services/axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))
