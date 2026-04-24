<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { clientService } from '@/services/ClientService'
import { productService } from '@/services/ProductService'
import { serviceService } from '@/services/ServiceService'
import { useSaleCartStore } from '@/stores/saleCart'
import { useUiStore } from '@/stores/ui'
import { useValidationErrors } from '@/composables/useValidationErrors'
import type { Client } from '@/types/client'
import type { Product } from '@/types/product'
import type { Service } from '@/types/service'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { Card, CardContent } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import SkeletonTable from '@/components/base/SkeletonTable.vue'
import { ArrowLeft, ChevronsUpDown, Check, Minus, Plus, Trash2 } from 'lucide-vue-next'

const router = useRouter()
const cart = useSaleCartStore()
const ui = useUiStore()
const { handleApiError } = useValidationErrors()

const clients = ref<Client[]>([])
const products = ref<Product[]>([])
const services = ref<Service[]>([])
const isLoading = ref(false)
const clientOpen = ref(false)

const selectedClient = computed(() =>
  clients.value.find((c) => c.id === cart.clientId) ?? null,
)

function hasMissingProduct(service: Service): boolean {
  if (!service.required_product) return false
  return !cart.productIdsInCart.has(service.required_product.id)
}

function cartQty(id: number, type: 'product' | 'service'): number {
  return cart.items.find((i) => i.id === id && i.type === type)?.quantity ?? 0
}

function selectClient(client: Client): void {
  cart.clientId = client.id
  clientOpen.value = false
}

function addProduct(product: Product): void {
  if (product.stock === 0) return
  cart.addItem({ type: 'product', id: product.id, name: product.name, price: product.price })
}

function addService(service: Service): void {
  if (service.disabled_at) return
  cart.addItem({
    type: 'service',
    id: service.id,
    name: service.name,
    price: service.price,
    required_product_id: service.required_product?.id ?? null,
  })
}

async function submitSale(): Promise<void> {
  try {
    const sale = await cart.submitSale()
    ui.toast.success(`Sale #${sale.daily_sequence} created successfully!`)
    router.push('/sales')
  } catch (error) {
    handleApiError(error)
  }
}

async function loadData(): Promise<void> {
  isLoading.value = true
  try {
    ;[clients.value, products.value, services.value] = await Promise.all([
      clientService.getAll(),
      productService.getAll(),
      serviceService.getAll(),
    ])
  } catch (error) {
    handleApiError(error)
  } finally {
    isLoading.value = false
  }
}

onMounted(loadData)
</script>

<template>
  <div class="p-6 space-y-5">

    <!-- Header -->
    <div class="flex items-center gap-4">
      <Button variant="ghost" size="icon" @click="router.back()">
        <ArrowLeft class="size-4" />
      </Button>
      <div>
        <h2 class="text-2xl font-bold tracking-tight">New Sale</h2>
        <p class="text-sm text-muted-foreground">Select a client and add products or services</p>
      </div>
    </div>

    <SkeletonTable v-if="isLoading" :cols="4" :rows="6" />

    <div v-else class="grid grid-cols-1 xl:grid-cols-3 gap-5">

      <!-- Left column -->
      <div class="xl:col-span-2 space-y-5">

        <!-- Client combobox -->
        <Card>
          <CardContent class="p-5">
            <div class="space-y-1.5">
              <Label>Client</Label>
              <Popover v-model:open="clientOpen">
                <PopoverTrigger as-child>
                  <Button
                    variant="outline"
                    role="combobox"
                    class="w-full justify-between font-normal"
                  >
                    <span :class="!selectedClient ? 'text-muted-foreground' : ''">
                      {{ selectedClient ? `${selectedClient.name} — ${selectedClient.tax_id}` : 'Search and select a client...' }}
                    </span>
                    <ChevronsUpDown class="size-4 shrink-0 opacity-50" />
                  </Button>
                </PopoverTrigger>
                <PopoverContent
                  class="p-0"
                  align="start"
                  :style="{ width: 'var(--reka-popover-trigger-width)' }"
                >
                  <Command>
                    <CommandInput placeholder="Search client by name or tax ID..." />
                    <CommandList>
                      <CommandEmpty>No client found.</CommandEmpty>
                      <CommandGroup>
                        <CommandItem
                          v-for="client in clients"
                          :key="client.id"
                          :value="`${client.name} ${client.tax_id}`"
                          @select="selectClient(client)"
                        >
                          <Check
                            class="size-4 shrink-0"
                            :class="cart.clientId === client.id ? 'opacity-100' : 'opacity-0'"
                          />
                          <span class="flex-1 truncate min-w-0">{{ client.name }}</span>
                          <span class="ml-3 shrink-0 text-xs text-muted-foreground font-mono">{{ client.tax_id }}</span>
                        </CommandItem>
                      </CommandGroup>
                    </CommandList>
                  </Command>
                </PopoverContent>
              </Popover>
            </div>
          </CardContent>
        </Card>

        <!-- Products table -->
        <Card>
          <div class="px-5 py-4 border-b">
            <h3 class="font-semibold text-sm">Products</h3>
          </div>
          <CardContent class="p-0">
            <div v-if="products.length === 0" class="py-12 text-center text-sm text-muted-foreground">
              No products available.
            </div>
            <Table v-else>
              <TableHeader>
                <TableRow>
                  <TableHead class="pl-5">Name</TableHead>
                  <TableHead>Price</TableHead>
                  <TableHead>Stock</TableHead>
                  <TableHead class="text-center">In order</TableHead>
                  <TableHead class="pr-5"></TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="product in products" :key="product.id">
                  <TableCell class="pl-5 font-medium">{{ product.name }}</TableCell>
                  <TableCell class="font-mono text-muted-foreground text-sm">
                    ${{ product.price.toFixed(2) }}
                  </TableCell>
                  <TableCell>
                    <Badge :variant="product.stock === 0 ? 'destructive' : 'secondary'">
                      {{ product.stock === 0 ? 'Out of stock' : product.stock }}
                    </Badge>
                  </TableCell>
                  <TableCell class="text-center">
                    <span v-if="cartQty(product.id, 'product') > 0" class="font-semibold text-primary text-sm">
                      × {{ cartQty(product.id, 'product') }}
                    </span>
                    <span v-else class="text-muted-foreground text-sm">—</span>
                  </TableCell>
                  <TableCell class="pr-5">
                    <div class="flex items-center justify-end gap-1">
                      <Button
                        v-if="cartQty(product.id, 'product') > 0"
                        variant="outline"
                        size="icon"
                        class="size-7"
                        @click="cart.updateQuantity(product.id, 'product', cartQty(product.id, 'product') - 1)"
                      >
                        <Minus class="size-3" />
                      </Button>
                      <Button
                        size="sm"
                        :disabled="product.stock === 0"
                        @click="addProduct(product)"
                      >
                        Add
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        <!-- Services table -->
        <Card>
          <div class="px-5 py-4 border-b">
            <h3 class="font-semibold text-sm">Services</h3>
          </div>
          <CardContent class="p-0">
            <div v-if="services.length === 0" class="py-12 text-center text-sm text-muted-foreground">
              No services available.
            </div>
            <Table v-else>
              <TableHeader>
                <TableRow>
                  <TableHead class="pl-5">Name</TableHead>
                  <TableHead>Price</TableHead>
                  <TableHead>Required product</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead class="text-center">In order</TableHead>
                  <TableHead class="pr-5"></TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="service in services" :key="service.id">
                  <TableCell class="pl-5 font-medium">{{ service.name }}</TableCell>
                  <TableCell class="font-mono text-muted-foreground text-sm">
                    ${{ service.price.toFixed(2) }}
                  </TableCell>
                  <TableCell>
                    <div v-if="service.required_product">
                      <p class="text-sm">{{ service.required_product.name }}</p>
                      <p
                        v-if="hasMissingProduct(service)"
                        class="text-xs text-amber-600 flex items-center gap-1 mt-0.5"
                      >
                        <span>⚠</span> Not in order
                      </p>
                    </div>
                    <span v-else class="text-muted-foreground text-sm">—</span>
                  </TableCell>
                  <TableCell>
                    <Badge :variant="service.disabled_at ? 'destructive' : 'default'">
                      {{ service.disabled_at ? 'Disabled' : 'Active' }}
                    </Badge>
                  </TableCell>
                  <TableCell class="text-center">
                    <span v-if="cartQty(service.id, 'service') > 0" class="font-semibold text-primary text-sm">
                      × {{ cartQty(service.id, 'service') }}
                    </span>
                    <span v-else class="text-muted-foreground text-sm">—</span>
                  </TableCell>
                  <TableCell class="pr-5">
                    <div class="flex items-center justify-end gap-1">
                      <Button
                        v-if="cartQty(service.id, 'service') > 0"
                        variant="outline"
                        size="icon"
                        class="size-7"
                        @click="cart.updateQuantity(service.id, 'service', cartQty(service.id, 'service') - 1)"
                      >
                        <Minus class="size-3" />
                      </Button>
                      <Button
                        size="sm"
                        :disabled="!!service.disabled_at"
                        @click="addService(service)"
                      >
                        Add
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>

      <!-- Right: Order summary -->
      <div>
        <Card class="sticky top-6">
          <div class="px-5 py-4 border-b">
            <h3 class="font-semibold text-sm">Order Summary</h3>
            <p v-if="selectedClient" class="text-sm font-medium text-primary mt-0.5">
              {{ selectedClient.name }}
            </p>
            <p v-else class="text-sm text-muted-foreground mt-0.5">No client selected</p>
          </div>

          <CardContent class="p-5 space-y-4">
            <!-- Empty state -->
            <div v-if="cart.items.length === 0" class="py-8 text-center text-muted-foreground">
              <p class="text-3xl mb-2">🛒</p>
              <p class="text-sm">No items added yet</p>
            </div>

            <!-- Items list -->
            <div v-else class="space-y-2">
              <div
                v-for="item in cart.items"
                :key="`${item.type}-${item.id}`"
                class="flex items-center gap-2 rounded-lg border bg-muted/30 p-2.5"
              >
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium truncate">{{ item.name }}</p>
                  <p class="text-xs text-muted-foreground font-mono">
                    ${{ item.price.toFixed(2) }} × {{ item.quantity }}
                  </p>
                </div>
                <div class="flex items-center gap-0.5 shrink-0">
                  <Button
                    variant="ghost"
                    size="icon"
                    class="size-6"
                    @click="cart.updateQuantity(item.id, item.type, item.quantity - 1)"
                  >
                    <Minus class="size-3" />
                  </Button>
                  <span class="w-5 text-center text-sm font-semibold">{{ item.quantity }}</span>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="size-6"
                    @click="cart.updateQuantity(item.id, item.type, item.quantity + 1)"
                  >
                    <Plus class="size-3" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="size-6 text-muted-foreground hover:text-destructive"
                    @click="cart.removeItem(item.id, item.type)"
                  >
                    <Trash2 class="size-3" />
                  </Button>
                </div>
              </div>
            </div>

            <!-- Totals -->
            <div v-if="cart.items.length > 0" class="space-y-2 text-sm">
              <Separator />
              <div class="flex justify-between text-muted-foreground">
                <span>Subtotal</span>
                <span class="font-mono">${{ cart.subtotal.toFixed(2) }}</span>
              </div>
              <div class="flex justify-between text-muted-foreground">
                <span>Tax (16%)</span>
                <span class="font-mono">${{ cart.taxes.toFixed(2) }}</span>
              </div>
              <Separator />
              <div class="flex justify-between font-bold text-base">
                <span>Total</span>
                <span class="font-mono">${{ cart.total.toFixed(2) }}</span>
              </div>
            </div>

            <!-- Actions -->
            <div class="space-y-2">
              <Button
                class="w-full"
                :disabled="cart.items.length === 0 || !cart.clientId"
                :loading="cart.isSubmitting"
                @click="submitSale"
              >
                Confirm Sale
              </Button>
              <Button
                v-if="cart.items.length > 0"
                variant="ghost"
                size="sm"
                class="w-full"
                @click="cart.reset()"
              >
                Clear order
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>

    </div>
  </div>
</template>
