<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useForm, useField } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { serviceService } from '@/services/ServiceService'
import { productService } from '@/services/ProductService'
import { useUiStore } from '@/stores/ui'
import { useValidationErrors } from '@/composables/useValidationErrors'
import type { Service } from '@/types/service'
import type { Product } from '@/types/product'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseModal from '@/components/base/BaseModal.vue'
import ConfirmDialog from '@/components/base/ConfirmDialog.vue'
import ApiErrorDialog from '@/components/base/ApiErrorDialog.vue'
import SkeletonTable from '@/components/base/SkeletonTable.vue'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

const ui = useUiStore()
const { handleApiError, getFieldError, clearErrors, errorDialog, closeErrorDialog } = useValidationErrors()

const services = ref<Service[]>([])
const products = ref<Product[]>([])
const isLoading = ref(false)
const showModal = ref(false)
const editingService = ref<Service | null>(null)
const deletingService = ref<Service | null>(null)

const schema = toTypedSchema(
  z.object({
    name: z.string().min(1, 'Name is required'),
    price: z.number({ invalid_type_error: 'Price is required' }).positive('Must be positive'),
    required_product_id: z.number().nullable().optional(),
  }),
)

const { handleSubmit, isSubmitting, resetForm } = useForm({ validationSchema: schema })
const { value: name, errorMessage: nameError } = useField<string>('name')
const { value: price, errorMessage: priceError } = useField<number>('price')
const { value: required_product_id } = useField<number | null>('required_product_id')

async function loadData(): Promise<void> {
  isLoading.value = true
  try {
    ;[services.value, products.value] = await Promise.all([
      serviceService.getAll(),
      productService.getAll(),
    ])
  } catch (error) {
    handleApiError(error)
  } finally {
    isLoading.value = false
  }
}

function openCreate(): void {
  editingService.value = null
  clearErrors()
  resetForm()
  showModal.value = true
}

function openEdit(service: Service): void {
  editingService.value = service
  clearErrors()
  resetForm({
    values: {
      name: service.name,
      price: service.price,
      required_product_id: service.required_product?.id ?? null,
    },
  })
  showModal.value = true
}

function closeModal(): void {
  showModal.value = false
  editingService.value = null
}

const onSubmit = handleSubmit(async (values) => {
  try {
    if (editingService.value) {
      const updated = await serviceService.update(editingService.value.id, values)
      const index = services.value.findIndex((s) => s.id === editingService.value!.id)
      if (index !== -1) services.value[index] = updated
      ui.toast.success('Service updated.')
    } else {
      const created = await serviceService.create(values)
      services.value.push(created)
      ui.toast.success('Service created.')
    }
    closeModal()
  } catch (error) {
    handleApiError(error)
  }
})

async function confirmDelete(): Promise<void> {
  const service = deletingService.value
  deletingService.value = null
  if (!service) return
  try {
    await serviceService.delete(service.id)
    services.value = services.value.filter((s) => s.id !== service.id)
    ui.toast.success('Service deleted.')
  } catch (error) {
    handleApiError(error)
  }
}

onMounted(loadData)
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">Services</h2>
        <p class="text-sm text-muted-foreground">Manage your service catalog</p>
      </div>
      <BaseButton @click="openCreate">+ New Service</BaseButton>
    </div>

    <Card>
      <CardContent class="p-0">
        <SkeletonTable v-if="isLoading" :cols="5" />

        <template v-else>
          <div v-if="services.length === 0" class="py-20 text-center text-muted-foreground">
            <p class="text-4xl mb-3">⚙️</p>
            <p class="text-sm font-medium">No services yet</p>
            <p class="text-xs mt-1">Add your first service to get started</p>
          </div>

          <Table v-else>
            <TableHeader>
              <TableRow>
                <TableHead class="pl-5">Name</TableHead>
                <TableHead>Price</TableHead>
                <TableHead>Required Product</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-right pr-5">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="service in services" :key="service.id">
                <TableCell class="pl-5 font-medium">{{ service.name }}</TableCell>
                <TableCell class="font-mono">${{ service.price.toFixed(2) }}</TableCell>
                <TableCell class="text-muted-foreground">
                  {{ service.required_product?.name ?? '—' }}
                </TableCell>
                <TableCell>
                  <Badge :variant="service.disabled_at ? 'destructive' : 'default'">
                    {{ service.disabled_at ? 'Disabled' : 'Active' }}
                  </Badge>
                </TableCell>
                <TableCell class="text-right pr-5">
                  <div class="flex justify-end gap-2">
                    <BaseButton variant="secondary" size="sm" @click="openEdit(service)">Edit</BaseButton>
                    <BaseButton variant="danger" size="sm" @click="deletingService = service">Delete</BaseButton>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </template>
      </CardContent>
    </Card>
  </div>

  <BaseModal
    :title="editingService ? 'Edit Service' : 'New Service'"
    :open="showModal"
    @close="closeModal"
  >
    <form class="space-y-4" @submit.prevent="onSubmit">
      <BaseInput v-model="name" label="Name" :error="nameError ?? getFieldError('name')" />
      <BaseInput v-model="price" label="Price" type="number" step="0.01" :error="priceError ?? getFieldError('price')" />

      <div class="flex flex-col gap-1.5">
        <Label>Required Product (optional)</Label>
        <select
          v-model="required_product_id"
          class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
        >
          <option :value="null">None</option>
          <option v-for="product in products" :key="product.id" :value="product.id">
            {{ product.name }}
          </option>
        </select>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <BaseButton variant="secondary" @click="closeModal">Cancel</BaseButton>
        <BaseButton type="submit" :loading="isSubmitting">
          {{ editingService ? 'Save Changes' : 'Create' }}
        </BaseButton>
      </div>
    </form>
  </BaseModal>

  <ConfirmDialog
    :open="!!deletingService"
    :title="`Delete &quot;${deletingService?.name}&quot;?`"
    description="This action cannot be undone."
    @confirm="confirmDelete"
    @cancel="deletingService = null"
  />

  <ApiErrorDialog
    :open="errorDialog.open"
    :message="errorDialog.message"
    :details="errorDialog.details"
    @close="closeErrorDialog"
  />
</template>
