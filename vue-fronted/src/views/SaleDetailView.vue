<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { saleService } from '@/services/SaleService'
import { useValidationErrors } from '@/composables/useValidationErrors'
import { formatMoney } from '@/lib/utils'
import type { Sale } from '@/types/sale'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { ArrowLeft, User, CalendarDays, Hash } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const { handleApiError } = useValidationErrors()

const sale = ref<Sale | null>(null)
const isLoading = ref(true)

function ordinal(n: number): string {
  const s = ['th', 'st', 'nd', 'rd']
  const v = n % 100
  return n + (s[(v - 20) % 10] ?? s[v] ?? s[0])
}

function itemName(detail: Sale['details'][number]): string {
  return detail.product_name ?? detail.service_name ?? `Item #${detail.id}`
}

async function load(): Promise<void> {
  try {
    sale.value = await saleService.getOne(Number(route.params.id))
  } catch (error) {
    handleApiError(error)
  } finally {
    isLoading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="p-6 space-y-5 max-w-4xl">

    <!-- Back -->
    <Button variant="ghost" class="-ml-2 text-muted-foreground" @click="router.push({ name: 'sales' })">
      <ArrowLeft class="size-4" />
      Back to Sales
    </Button>

    <!-- Loading skeleton -->
    <template v-if="isLoading">
      <div class="space-y-4 animate-pulse">
        <div class="h-8 w-48 bg-muted rounded" />
        <div class="h-32 bg-muted rounded-lg" />
        <div class="h-48 bg-muted rounded-lg" />
      </div>
    </template>

    <template v-else-if="sale">

      <!-- Header -->
      <div>
        <h2 class="text-2xl font-bold tracking-tight">Sale #{{ sale.id }}</h2>
        <p class="text-sm text-muted-foreground mt-0.5">
          {{ ordinal(sale.daily_sequence) }} purchase of the day for
          <span class="font-medium text-foreground">{{ sale.client?.name ?? `Client #${sale.client_id}` }}</span>
        </p>
      </div>

      <!-- Info card -->
      <Card>
        <CardContent class="p-5">
          <dl class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="flex items-start gap-3">
              <User class="size-4 mt-0.5 text-muted-foreground shrink-0" />
              <div>
                <dt class="text-xs text-muted-foreground uppercase tracking-wide">Client</dt>
                <dd class="font-medium mt-0.5">{{ sale.client?.name ?? `Client #${sale.client_id}` }}</dd>
                <dd class="text-xs text-muted-foreground font-mono">{{ sale.client?.tax_id }}</dd>
              </div>
            </div>

            <div class="flex items-start gap-3">
              <CalendarDays class="size-4 mt-0.5 text-muted-foreground shrink-0" />
              <div>
                <dt class="text-xs text-muted-foreground uppercase tracking-wide">Date</dt>
                <dd class="font-medium mt-0.5">{{ new Date(sale.created_at).toLocaleDateString() }}</dd>
                <dd class="text-xs text-muted-foreground">{{ new Date(sale.created_at).toLocaleTimeString() }}</dd>
              </div>
            </div>

            <div class="flex items-start gap-3">
              <Hash class="size-4 mt-0.5 text-muted-foreground shrink-0" />
              <div>
                <dt class="text-xs text-muted-foreground uppercase tracking-wide">Daily sequence</dt>
                <dd class="font-medium mt-0.5">{{ ordinal(sale.daily_sequence) }} purchase of the day</dd>
              </div>
            </div>
          </dl>
        </CardContent>
      </Card>

      <!-- Items -->
      <Card>
        <div class="px-5 py-4 border-b">
          <h3 class="font-semibold text-sm">Items</h3>
        </div>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="pl-5">Type</TableHead>
                <TableHead>Item</TableHead>
                <TableHead class="text-right">Qty</TableHead>
                <TableHead class="text-right">Unit Price</TableHead>
                <TableHead class="text-right pr-5">Subtotal</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="detail in sale.details" :key="detail.id">
                <TableCell class="pl-5">
                  <Badge :variant="detail.product_id ? 'secondary' : 'outline'" class="text-xs">
                    {{ detail.product_id ? 'Product' : 'Service' }}
                  </Badge>
                </TableCell>
                <TableCell class="font-medium">{{ itemName(detail) }}</TableCell>
                <TableCell class="text-right text-muted-foreground">{{ detail.quantity }}</TableCell>
                <TableCell class="text-right font-mono">{{ formatMoney(detail.unit_price) }}</TableCell>
                <TableCell class="text-right pr-5 font-mono">{{ formatMoney(detail.subtotal) }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>

        <Separator />

        <div class="px-5 py-4 flex items-center justify-between">
          <span class="text-sm font-medium">Total</span>
          <span class="text-lg font-bold font-mono">{{ formatMoney(sale.total) }}</span>
        </div>
      </Card>

    </template>

  </div>
</template>
