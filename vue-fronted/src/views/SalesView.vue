<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { clientService } from '@/services/ClientService'
import { saleService } from '@/services/SaleService'
import type { PaginationMeta } from '@/types/api'
import { useValidationErrors } from '@/composables/useValidationErrors'
import { formatMoney } from '@/lib/utils'
import type { Sale } from '@/types/sale'
import type { Client } from '@/types/client'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
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
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination'
import { PlusCircle, Check, ChevronsUpDown } from 'lucide-vue-next'

const router = useRouter()
const { handleApiError } = useValidationErrors()

const sales = ref<Sale[]>([])
const clients = ref<Client[]>([])
const isLoading = ref(false)
const currentPage = ref(1)
const meta = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

const dateFrom = ref('')
const dateTo = ref('')
const selectedClientId = ref<number | null>(null)
const clientOpen = ref(false)

const selectedClient = computed(() =>
  clients.value.find((c) => c.id === selectedClientId.value) ?? null,
)

const totalRevenue = computed(() => sales.value.reduce((sum, s) => sum + Number(s.total), 0))

function selectClient(client: Client): void {
  selectedClientId.value = client.id
  clientOpen.value = false
}

function clearClient(): void {
  selectedClientId.value = null
  clientOpen.value = false
}

async function loadSales(): Promise<void> {
  isLoading.value = true
  try {
    const result = await saleService.getAll({
      client_id: selectedClientId.value ?? undefined,
      date_from: dateFrom.value || undefined,
      date_to: dateTo.value || undefined,
      page: currentPage.value,
    })
    sales.value = result.data
    meta.value = result.meta
  } catch (error) {
    handleApiError(error)
  } finally {
    isLoading.value = false
  }
}

function goToPage(page: number): void {
  currentPage.value = page
  loadSales()
}

function resetFilters(): void {
  dateFrom.value = ''
  dateTo.value = ''
  selectedClientId.value = null
  currentPage.value = 1
  loadSales()
}

async function loadData(): Promise<void> {
  try {
    clients.value = await clientService.getAll()
  } catch (error) {
    handleApiError(error)
  }
  await loadSales()
}

onMounted(loadData)
</script>

<template>
  <div class="p-6 space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">Sales History</h2>
        <p class="text-sm text-muted-foreground">View and filter all sales records</p>
      </div>
      <Button @click="router.push('/sales/new')">
        <PlusCircle class="size-4" />
        New Sale
      </Button>
    </div>

    <!-- 2-column layout -->
    <div class="grid grid-cols-1 xl:grid-cols-[300px_1fr] gap-5 items-start">

      <!-- Left: Filters -->
      <Card class="sticky top-6">
        <div class="px-5 py-4 border-b">
          <h3 class="font-semibold text-sm">Filters</h3>
        </div>
        <CardContent class="p-5 space-y-4">

          <div class="space-y-1.5">
            <Label>From</Label>
            <Input v-model="dateFrom" type="date" />
          </div>

          <div class="space-y-1.5">
            <Label>To</Label>
            <Input v-model="dateTo" type="date" />
          </div>

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
                    {{ selectedClient ? selectedClient.name : 'All clients' }}
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
                  <CommandInput placeholder="Search client..." />
                  <CommandList>
                    <CommandEmpty>No client found.</CommandEmpty>
                    <CommandGroup>
                      <CommandItem value="all" @select="clearClient">
                        <Check
                          class="size-4 shrink-0"
                          :class="!selectedClientId ? 'opacity-100' : 'opacity-0'"
                        />
                        <span>All clients</span>
                      </CommandItem>
                      <CommandItem
                        v-for="client in clients"
                        :key="client.id"
                        :value="`${client.name} ${client.tax_id}`"
                        @select="selectClient(client)"
                      >
                        <Check
                          class="size-4 shrink-0"
                          :class="selectedClientId === client.id ? 'opacity-100' : 'opacity-0'"
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

          <div class="flex flex-col gap-2 pt-1">
            <Button class="w-full" :loading="isLoading" @click="currentPage = 1; loadSales()">Apply Filters</Button>
            <Button variant="outline" class="w-full" @click="resetFilters">Reset</Button>
          </div>

          <Separator />

          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-muted-foreground">Results</span>
              <span class="font-semibold">{{ meta.total }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Revenue</span>
              <span class="font-mono font-semibold">{{ formatMoney(totalRevenue) }}</span>
            </div>
          </div>

        </CardContent>
      </Card>

      <!-- Right: Chart + Table -->
      <div class="space-y-5">
        <!-- Sales table -->
        <Card>
          <div class="px-5 py-4 border-b">
            <h3 class="font-semibold text-sm">Sales</h3>
          </div>
          <CardContent class="p-0">
            <SkeletonTable v-if="isLoading" :cols="5" :rows="8" />
            <div v-else-if="sales.length === 0" class="py-12 text-center text-sm text-muted-foreground">
              No sales found.
            </div>
            <Table v-else>
              <TableHeader>
                <TableRow>
                  <TableHead class="pl-5">#</TableHead>
                  <TableHead>Client</TableHead>
                  <TableHead>Date</TableHead>
                  <TableHead class="text-center">Items</TableHead>
                  <TableHead class="text-right pr-5">Total</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="sale in sales" :key="sale.id">
                  <TableCell class="pl-5 font-mono text-muted-foreground text-sm">
                    #{{ sale.daily_sequence }}
                  </TableCell>
                  <TableCell class="font-medium">
                    {{ sale.client?.name ?? `Client #${sale.client_id}` }}
                  </TableCell>
                  <TableCell class="text-sm text-muted-foreground">
                    {{ new Date(sale.created_at).toLocaleString() }}
                  </TableCell>
                  <TableCell class="text-center">
                    <Badge variant="secondary">{{ sale.details.length }}</Badge>
                  </TableCell>
                  <TableCell class="text-right pr-5 font-mono font-semibold">
                    {{ formatMoney(sale.total) }}
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        <!-- Pagination -->
        <Pagination
          v-if="meta.last_page > 1"
          :page="currentPage"
          :total="meta.total"
          :items-per-page="meta.per_page"
          :sibling-count="1"
          show-edges
          @update:page="goToPage"
        >
          <PaginationContent v-slot="{ items }">
            <PaginationPrevious />
            <template v-for="item in items" :key="item.type === 'page' ? item.value : item.type">
              <PaginationItem v-if="item.type === 'page'" :value="item.value">
                <PaginationLink :is-active="item.value === currentPage">
                  {{ item.value }}
                </PaginationLink>
              </PaginationItem>
              <PaginationEllipsis v-else />
            </template>
            <PaginationNext />
          </PaginationContent>
        </Pagination>

      </div>
    </div>
  </div>
</template>
