<script setup lang="ts">
import { ref, computed, defineComponent, h, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { AgGridVue } from '@ag-grid-community/vue3'
import { ModuleRegistry } from '@ag-grid-community/core'
import { ClientSideRowModelModule } from '@ag-grid-community/client-side-row-model'
import type { ColDef } from '@ag-grid-community/core'
import 'ag-grid-community/styles/ag-grid.css'
import 'ag-grid-community/styles/ag-theme-quartz.css'
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
import { PlusCircle, Check, ChevronsUpDown, Eye } from 'lucide-vue-next'

ModuleRegistry.registerModules([ClientSideRowModelModule])

const router = useRouter()
const { handleApiError } = useValidationErrors()

// --- Cell renderers ---

const ItemsCellRenderer = defineComponent({
  props: ['params'],
  setup(props) {
    return () =>
      h('div', { class: 'flex items-center justify-center h-full' },
        h(Badge, { variant: 'secondary' }, () => String(props.params.value)),
      )
  },
})

const ActionsCellRenderer = defineComponent({
  props: ['params'],
  setup(props) {
    return () =>
      h('div', { class: 'flex items-center h-full' },
        h(Button, {
          variant: 'ghost',
          size: 'icon',
          class: 'size-8',
          onClick: () => router.push({ name: 'sales-show', params: { id: props.params.data?.id } }),
        }, () => h(Eye, { class: 'size-4' })),
      )
  },
})

// --- Column definitions ---

const columnDefs: ColDef<Sale>[] = [
  {
    headerName: '#',
    field: 'daily_sequence',
    valueFormatter: ({ value }) => `#${value}`,
    width: 80,
  },
  {
    headerName: 'Client',
    valueGetter: ({ data }) => data?.client?.name ?? `Client #${data?.client_id}`,
    flex: 1,
    minWidth: 150,
  },
  {
    headerName: 'Date',
    field: 'created_at',
    valueFormatter: ({ value }) => new Date(value).toLocaleString(),
    flex: 1,
    minWidth: 180,
  },
  {
    headerName: 'Items',
    valueGetter: ({ data }) => data?.details?.length ?? 0,
    width: 90,
    cellRenderer: ItemsCellRenderer,
  },
  {
    headerName: 'Total',
    field: 'total',
    valueFormatter: ({ value }) => formatMoney(value),
    width: 140,
    type: 'rightAligned',
  },
  {
    headerName: '',
    cellRenderer: ActionsCellRenderer,
    width: 64,
    resizable: false,
  },
]

const defaultColDef: ColDef = {
  resizable: false,
  sortable: false,
  filter: false,
  suppressMovable: true,
}

// --- State ---

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

// --- Actions ---

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

      <!-- Right: Table -->
      <div class="space-y-5">
        <Card>
          <div class="px-5 py-4 border-b">
            <h3 class="font-semibold text-sm">Sales</h3>
          </div>
          <CardContent class="p-0">
            <SkeletonTable v-if="isLoading" :cols="6" :rows="8" />
            <div v-else-if="sales.length === 0" class="py-12 text-center text-sm text-muted-foreground">
              No sales found.
            </div>
            <AgGridVue
              v-else
              class="ag-theme-quartz"
              style="width: 100%"
              domLayout="autoHeight"
              :columnDefs="columnDefs"
              :rowData="sales"
              :defaultColDef="defaultColDef"
              :headerHeight="40"
              :rowHeight="48"
              :suppressCellFocus="true"
              :suppressMovableColumns="true"
              :suppressColumnVirtualisation="true"
            />
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

<style>
.ag-theme-quartz {
  --ag-background-color: var(--card);
  --ag-header-background-color: var(--card);
  --ag-foreground-color: var(--foreground);
  --ag-header-foreground-color: var(--muted-foreground);
  --ag-border-color: var(--border);
  --ag-row-hover-color: var(--muted);
  --ag-font-size: 14px;
  --ag-font-family: inherit;
  --ag-cell-horizontal-padding: 16px;
}

.ag-theme-quartz .ag-root-wrapper {
  border: none;
  border-radius: 0;
}

.ag-theme-quartz .ag-header-cell-text {
  font-size: 13px;
  font-weight: 500;
}
</style>
