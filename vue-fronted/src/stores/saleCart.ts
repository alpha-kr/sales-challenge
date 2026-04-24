import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { saleService } from '@/services/SaleService'
import type { CartItem, Sale } from '@/types/sale'

const TAX_RATE = 0.16

export const useSaleCartStore = defineStore('saleCart', () => {
  const clientId = ref<number | null>(null)
  const items = ref<CartItem[]>([])
  const isSubmitting = ref(false)

  const subtotal = computed(() =>
    items.value.reduce((sum, item) => sum + item.price * item.quantity, 0),
  )

  const taxes = computed(() => subtotal.value * TAX_RATE)

  const total = computed(() => subtotal.value + taxes.value)

  const productIdsInCart = computed(
    () => new Set(items.value.filter((i) => i.type === 'product').map((i) => i.id)),
  )

  function addItem(item: Omit<CartItem, 'quantity'>): void {
    const existing = items.value.find((i) => i.id === item.id && i.type === item.type)
    if (existing) {
      existing.quantity++
    } else {
      items.value.push({ ...item, quantity: 1 })
    }
  }

  function removeItem(id: number, type: CartItem['type']): void {
    const index = items.value.findIndex((i) => i.id === id && i.type === type)
    if (index !== -1) {
      items.value.splice(index, 1)
    }
  }

  function updateQuantity(id: number, type: CartItem['type'], quantity: number): void {
    const item = items.value.find((i) => i.id === id && i.type === type)
    if (item) {
      if (quantity <= 0) {
        removeItem(id, type)
      } else {
        item.quantity = quantity
      }
    }
  }

  function reset(): void {
    clientId.value = null
    items.value = []
  }

  async function submitSale(): Promise<Sale> {
    if (!clientId.value) {
      throw new Error('Client is required')
    }

    isSubmitting.value = true
    try {
      const saleItems = items.value.map((item) => ({
        product_id: item.type === 'product' ? item.id : null,
        service_id: item.type === 'service' ? item.id : null,
        quantity: item.quantity,
      }))

      const sale = await saleService.create({
        client_id: clientId.value,
        items: saleItems,
      })

      reset()
      return sale
    } finally {
      isSubmitting.value = false
    }
  }

  return {
    clientId,
    items,
    isSubmitting,
    subtotal,
    taxes,
    total,
    productIdsInCart,
    addItem,
    removeItem,
    updateQuantity,
    reset,
    submitSale,
  }
})
