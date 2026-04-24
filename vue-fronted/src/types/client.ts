export interface Client {
  id: number
  name: string
  tax_id: string
  created_at: string
  updated_at: string
}

export interface CreateClientPayload {
  name: string
  tax_id: string
}

export type UpdateClientPayload = Partial<CreateClientPayload>
