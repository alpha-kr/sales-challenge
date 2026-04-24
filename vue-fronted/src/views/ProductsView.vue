<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useForm, useField } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { productService } from '@/services/ProductService'
import { useUiStore } from '@/stores/ui'
import { useValidationErrors } from '@/composables/useValidationErrors'
import type { Product } from '@/types/product'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseModal from '@/components/base/BaseModal.vue'
import ConfirmDialog from '@/components/base/ConfirmDialog.vue'
import ApiErrorDialog from '@/components/base/ApiErrorDialog.vue'
import SkeletonTable from '@/components/base/SkeletonTable.vue'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
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

const products = ref<Product[]>([])
const isLoading = ref(false)
const showModal = ref(false)
const editingProduct = ref<Product | null>(null)
const deletingProduct = ref<Product | null>(null)

const schema = toTypedSchema(
  z.object({
    name: z.string().min(1, 'Name is required'),
    price: z.number({ invalid_type_error: 'Price is required' }).positive('Must be positive'),
    stock: z.number({ invalid_type_error: 'Stock is required' }).int().min(0, 'Cannot be negative'),
  }),
)

const { handleSubmit, isSubmitting, resetForm } = useForm({ validationSchema: schema })
const { value: name, errorMessage: nameError } = useField<string>('name')
const { value: price, errorMessage: priceError } = useField<number>('price')
const { value: stock, errorMessage: stockError } = useField<number>('stock')

async function loadProducts(): Promise<void> {
  isLoading.value = true
  try {
    products.value = await productService.getAll()
  } catch (error) {
    handleApiError(error)
  } finally {
    isLoading.value = false
  }
}

function openCreate(): void {
  editingProduct.value = null
  clearErrors()
  resetForm()
  showModal.value = true
}

function openEdit(product: Product): void {
  editingProduct.value = product
  clearErrors()
  resetForm({ values: { name: product.name, price: product.price, stock: product.stock } })
  showModal.value = true
}

function closeModal(): void {
  showModal.value = false
  editingProduct.value = null
}

const onSubmit = handleSubmit(async (values) => {
  try {
    if (editingProduct.value) {
      const updated = await productService.update(editingProduct.value.id, values)
      const index = products.value.findIndex((p) => p.id === editingProduct.value!.id)
      if (index !== -1) products.value[index] = updated
      ui.toast.success('Product updated.')
    } else {
      const created = await productService.create(values)
      products.value.push(created)
      ui.toast.success('Product created.')
    }
    closeModal()
  } catch (error) {
    handleApiError(error)
  }
})

async function confirmDelete(): Promise<void> {
  const product = deletingProduct.value
  deletingProduct.value = null
  if (!product) return
  try {
    await productService.delete(product.id)
    products.value = products.value.filter((p) => p.id !== product.id)
    ui.toast.success('Product deleted.')
  } catch (error) {
    handleApiError(error)
  }
}

onMounted(loadProducts)
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">Products</h2>
        <p class="text-sm text-muted-foreground">Manage your product inventory</p>
      </div>
      <BaseButton @click="openCreate">+ New Product</BaseButton>
    </div>

    <Card>
      <CardContent class="p-0">
        <SkeletonTable v-if="isLoading" :cols="5" />

        <template v-else>
          <div v-if="products.length === 0" class="py-20 text-center text-muted-foreground">
            <p class="text-4xl mb-3">📦</p>
            <p class="text-sm font-medium">No products yet</p>
            <p class="text-xs mt-1">Add your first product to get started</p>
          </div>

          <Table v-else>
            <TableHeader>
              <TableRow>
                <TableHead class="pl-5">Name</TableHead>
                <TableHead>Price</TableHead>
                <TableHead>Stock</TableHead>
                <TableHead>Created</TableHead>
                <TableHead class="text-right pr-5">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="product in products" :key="product.id">
                <TableCell class="pl-5 font-medium">{{ product.name }}</TableCell>
                <TableCell class="font-mono">${{ product.price.toFixed(2) }}</TableCell>
                <TableCell>
                  <Badge :variant="product.stock === 0 ? 'destructive' : 'secondary'">
                    {{ product.stock === 0 ? 'Out of stock' : product.stock }}
                  </Badge>
                </TableCell>
                <TableCell class="text-muted-foreground">
                  {{ new Date(product.created_at).toLocaleDateString() }}
                </TableCell>
                <TableCell class="text-right pr-5">
                  <div class="flex justify-end gap-2">
                    <BaseButton variant="secondary" size="sm" @click="openEdit(product)">Edit</BaseButton>
                    <BaseButton variant="danger" size="sm" @click="deletingProduct = product">Delete</BaseButton>
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
    :title="editingProduct ? 'Edit Product' : 'New Product'"
    :open="showModal"
    @close="closeModal"
  >
    <form class="space-y-4" @submit.prevent="onSubmit">
      <BaseInput v-model="name" label="Name" :error="nameError ?? getFieldError('name')" />
      <BaseInput v-model="price" label="Price" step="0.01" type="number" :error="priceError ?? getFieldError('price')" />
      <BaseInput v-model="stock" label="Stock" type="number" :error="stockError ?? getFieldError('stock')" />
      <div class="flex justify-end gap-3 pt-2">
        <BaseButton variant="secondary" @click="closeModal">Cancel</BaseButton>
        <BaseButton type="submit" :loading="isSubmitting">
          {{ editingProduct ? 'Save Changes' : 'Create' }}
        </BaseButton>
      </div>
    </form>
  </BaseModal>

  <ConfirmDialog
    :open="!!deletingProduct"
    :title="`Delete &quot;${deletingProduct?.name}&quot;?`"
    description="This action cannot be undone."
    @confirm="confirmDelete"
    @cancel="deletingProduct = null"
  />

  <ApiErrorDialog
    :errorDialog="errorDialog"
    @close="closeErrorDialog"
  />
</template>
