<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useForm, useField } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { clientService } from '@/services/ClientService'
import { useUiStore } from '@/stores/ui'
import { useValidationErrors } from '@/composables/useValidationErrors'
import type { Client } from '@/types/client'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseModal from '@/components/base/BaseModal.vue'
import ConfirmDialog from '@/components/base/ConfirmDialog.vue'
import ApiErrorDialog from '@/components/base/ApiErrorDialog.vue'
import SkeletonTable from '@/components/base/SkeletonTable.vue'
import { Badge } from '@/components/ui/badge'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Card, CardContent } from '@/components/ui/card'

const ui = useUiStore()
const { handleApiError, getFieldError, clearErrors, errorDialog, closeErrorDialog } = useValidationErrors()

const clients = ref<Client[]>([])
const isLoading = ref(false)
const showModal = ref(false)
const editingClient = ref<Client | null>(null)
const deletingClient = ref<Client | null>(null)

const schema = toTypedSchema(
  z.object({
    name: z.string().min(1, 'Name is required'),
    tax_id: z.string().min(1, 'Tax ID is required'),
  }),
)

const { handleSubmit, isSubmitting, resetForm } = useForm({ validationSchema: schema })
const { value: name, errorMessage: nameError } = useField<string>('name')
const { value: tax_id, errorMessage: taxIdError } = useField<string>('tax_id')

async function loadClients(): Promise<void> {
  isLoading.value = true
  try {
    clients.value = await clientService.getAll()
  } catch (error) {
    handleApiError(error)
  } finally {
    isLoading.value = false
  }
}

function openCreate(): void {
  editingClient.value = null
  clearErrors()
  resetForm()
  showModal.value = true
}

function openEdit(client: Client): void {
  editingClient.value = client
  clearErrors()
  resetForm({ values: { name: client.name, tax_id: client.tax_id } })
  showModal.value = true
}

function closeModal(): void {
  showModal.value = false
  editingClient.value = null
}

const onSubmit = handleSubmit(async (values) => {
  try {
    if (editingClient.value) {
      const updated = await clientService.update(editingClient.value.id, values)
      const index = clients.value.findIndex((c) => c.id === editingClient.value!.id)
      if (index !== -1) clients.value[index] = updated
      ui.toast.success('Client updated.')
    } else {
      const created = await clientService.create(values)
      clients.value.push(created)
      ui.toast.success('Client created.')
    }
    closeModal()
  } catch (error) {
    handleApiError(error)
  }
})

async function confirmDelete(): Promise<void> {
  const client = deletingClient.value
  deletingClient.value = null
  if (!client) return
  try {
    await clientService.delete(client.id)
    clients.value = clients.value.filter((c) => c.id !== client.id)
    ui.toast.success('Client deleted.')
  } catch (error) {
    handleApiError(error)
  }
}

onMounted(loadClients)
</script>

<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold tracking-tight">Clients</h2>
        <p class="text-sm text-muted-foreground">Manage your client directory</p>
      </div>
      <BaseButton @click="openCreate">+ New Client</BaseButton>
    </div>

    <Card>
      <CardContent class="p-0">
        <SkeletonTable v-if="isLoading" :cols="4" />

        <template v-else>
          <div v-if="clients.length === 0" class="py-20 text-center text-muted-foreground">
            <p class="text-4xl mb-3">👥</p>
            <p class="text-sm font-medium">No clients yet</p>
            <p class="text-xs mt-1">Add your first client to get started</p>
          </div>

          <Table v-else>
            <TableHeader>
              <TableRow>
                <TableHead class="pl-5">Name</TableHead>
                <TableHead>Tax ID</TableHead>
                <TableHead>Created</TableHead>
                <TableHead class="text-right pr-5">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="client in clients" :key="client.id">
                <TableCell class="pl-5 font-medium">{{ client.name }}</TableCell>
                <TableCell>
                  <Badge variant="secondary" class="font-mono">{{ client.tax_id }}</Badge>
                </TableCell>
                <TableCell class="text-muted-foreground">
                  {{ new Date(client.created_at).toLocaleDateString() }}
                </TableCell>
                <TableCell class="text-right pr-5">
                  <div class="flex justify-end gap-2">
                    <BaseButton variant="secondary" size="sm" @click="openEdit(client)">Edit</BaseButton>
                    <BaseButton variant="danger" size="sm" @click="deletingClient = client">Delete</BaseButton>
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
    :title="editingClient ? 'Edit Client' : 'New Client'"
    :open="showModal"
    @close="closeModal"
  >
    <form class="space-y-4" @submit.prevent="onSubmit">
      <BaseInput v-model="name" label="Name" :error="nameError ?? getFieldError('name')" />
      <BaseInput v-model="tax_id" label="Tax ID" :error="taxIdError ?? getFieldError('tax_id')" />
      <div class="flex justify-end gap-3 pt-2">
        <BaseButton variant="secondary" @click="closeModal">Cancel</BaseButton>
        <BaseButton type="submit" :loading="isSubmitting">
          {{ editingClient ? 'Save Changes' : 'Create' }}
        </BaseButton>
      </div>
    </form>
  </BaseModal>

  <ConfirmDialog
    :open="!!deletingClient"
    :title="`Delete &quot;${deletingClient?.name}&quot;?`"
    description="This action cannot be undone."
    @confirm="confirmDelete"
    @cancel="deletingClient = null"
  />

  <ApiErrorDialog
    :open="errorDialog.open"
    :message="errorDialog.message"
    :details="errorDialog.details"
    @close="closeErrorDialog"
  />
</template>
