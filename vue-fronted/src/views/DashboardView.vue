<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { AgCharts } from 'ag-charts-vue3'
import { ModuleRegistry, AllCommunityModule } from 'ag-charts-community'
import type { AgChartOptions } from 'ag-charts-community'

ModuleRegistry.registerModules([AllCommunityModule])
import { dashboardService } from '@/services/DashboardService'
import { formatMoney } from '@/lib/utils'
import { useValidationErrors } from '@/composables/useValidationErrors'
import type { DashboardStats } from '@/types/dashboard'
import { Card, CardContent } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'

const { handleApiError } = useValidationErrors()

const stats = ref<DashboardStats | null>(null)
const isLoading = ref(false)
const error = ref<string | null>(null)

const stockOptions = computed(() => ({
  data: stats.value?.products_stock ?? [],
  series: [{ type: 'bar', xKey: 'name', yKey: 'stock', yName: 'Stock' }],
}) as unknown as AgChartOptions)

const monthlyOptions = computed(() => ({
  data: stats.value?.sales_by_month ?? [],
  series: [{
    type: 'bar',
    xKey: 'label',
    yKey: 'total',
    yName: 'Revenue',
    tooltip: { renderer: ({ datum }: { datum: { label: string; total: number } }) => ({ content: formatMoney(datum.total) }) },
  }],
}) as unknown as AgChartOptions)

const productPieOptions = computed(() => ({
  data: stats.value?.sales_by_product ?? [],
  series: [{
    type: 'pie',
    angleKey: 'total',
    legendItemKey: 'name',
    tooltip: { renderer: ({ datum }: { datum: { name: string; total: number } }) => ({ content: `${datum.name}: ${formatMoney(datum.total)}` }) },
  }],
}) as unknown as AgChartOptions)

const servicePieOptions = computed(() => ({
  data: stats.value?.sales_by_service ?? [],
  series: [{
    type: 'pie',
    angleKey: 'total',
    legendItemKey: 'name',
    tooltip: { renderer: ({ datum }: { datum: { name: string; total: number } }) => ({ content: `${datum.name}: ${formatMoney(datum.total)}` }) },
  }],
}) as unknown as AgChartOptions)

onMounted(async () => {
  isLoading.value = true
  try {
    stats.value = await dashboardService.getStats()
  } catch (err) {
    handleApiError(err)
    error.value = 'Failed to load dashboard data.'
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="p-6 space-y-6">
    <div>
      <h2 class="text-2xl font-bold tracking-tight">Dashboard</h2>
      <p class="text-sm text-muted-foreground">Sales and inventory overview</p>
    </div>

    <div v-if="isLoading" class="grid grid-cols-1 xl:grid-cols-2 gap-5">
      <Card v-for="i in 4" :key="i">
        <div class="px-5 py-4 border-b">
          <Skeleton class="h-4 w-32" />
        </div>
        <CardContent class="p-5">
          <Skeleton class="h-[350px] w-full" />
        </CardContent>
      </Card>
    </div>

    <div v-else-if="error" class="text-sm text-destructive">{{ error }}</div>

    <div v-else class="grid grid-cols-1 xl:grid-cols-2 gap-5">
      <Card>
        <div class="px-5 py-4 border-b">
          <h3 class="font-semibold text-sm">Product Stock</h3>
          <p class="text-xs text-muted-foreground">Current stock levels by product</p>
        </div>
        <CardContent class="p-5">
          <AgCharts :options="stockOptions" style="height: 350px" />
        </CardContent>
      </Card>

      <Card>
        <div class="px-5 py-4 border-b">
          <h3 class="font-semibold text-sm">Sales by Month</h3>
          <p class="text-xs text-muted-foreground">Monthly revenue totals</p>
        </div>
        <CardContent class="p-5">
          <AgCharts :options="monthlyOptions" style="height: 350px" />
        </CardContent>
      </Card>

      <Card>
        <div class="px-5 py-4 border-b">
          <h3 class="font-semibold text-sm">Sales by Product</h3>
          <p class="text-xs text-muted-foreground">Revenue distribution across products</p>
        </div>
        <CardContent class="p-5">
          <AgCharts :options="productPieOptions" style="height: 350px" />
        </CardContent>
      </Card>

      <Card>
        <div class="px-5 py-4 border-b">
          <h3 class="font-semibold text-sm">Sales by Service</h3>
          <p class="text-xs text-muted-foreground">Revenue distribution across services</p>
        </div>
        <CardContent class="p-5">
          <AgCharts :options="servicePieOptions" style="height: 350px" />
        </CardContent>
      </Card>
    </div>
  </div>
</template>
