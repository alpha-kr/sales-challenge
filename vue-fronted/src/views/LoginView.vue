<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useForm, useField } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { useValidationErrors } from '@/composables/useValidationErrors'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import { AlertCircleIcon } from 'lucide-vue-next'
import AlertTitle from '@/components/ui/alert/AlertTitle.vue'
import Alert from '@/components/ui/alert/Alert.vue'
const router = useRouter()
const auth = useAuthStore()
const ui = useUiStore()
const { handleApiError,getFieldError } = useValidationErrors()

const schema = toTypedSchema(
    z.object({
        email: z.string().email('Valid email required'),
        password: z.string().min(1, 'Password is required'),
    }),
)

const { handleSubmit, isSubmitting } = useForm({ validationSchema: schema })
const { value: email, errorMessage: emailError } = useField<string>('email')
const { value: password, errorMessage: passwordError } = useField<string>('password')

const onSubmit = handleSubmit(async (values) => {
    try {
        await auth.login(values)
        ui.toast.success('Welcome back!')
        router.push('/sales')
    } catch (error) {
        handleApiError(error)
    }
})
</script>

<template>
    <div class="min-h-screen flex">

        <!-- Left: Form panel -->
        <div class="flex flex-col justify-center w-full max-w-lg px-12 py-16 bg-background">
            <div class="w-full max-w-sm mx-auto space-y-8">

                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-xl bg-primary flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" fill="none" class="size-5 text-primary-foreground"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                            <polyline points="16 7 22 7 22 13" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight">SalesApp</span>
                </div>

                <!-- Heading -->
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight">Welcome back</h1>
                    <p class="text-muted-foreground text-sm">Sign in to access your sales dashboard</p>
                </div>
                <Alert v-if="getFieldError('email') || getFieldError('password')" variant="destructive">
                    <AlertCircleIcon />
                    <AlertTitle>{{ getFieldError('email') || getFieldError('password') }}.</AlertTitle>
                </Alert>
                <!-- Form -->
                <form class="space-y-5" @submit.prevent="onSubmit">
                    <BaseInput v-model="email" label="Email" type="email" :error="emailError" />
                    <BaseInput v-model="password" label="Password" type="password" :error="passwordError" />
                    <BaseButton type="submit" class="w-full" size="lg" :loading="isSubmitting">
                        Sign in to your account
                    </BaseButton>
                </form>

            </div>
        </div>

        <!-- Right: Brand panel -->
        <div
            class="hidden lg:flex flex-1 relative overflow-hidden bg-linear-to-br from-indigo-600 via-blue-600 to-blue-500">

            <!-- Noise texture overlay -->
            <div class="absolute inset-0 opacity-[0.03]"
                style="background-image: url('data:image/svg+xml,%3Csvg viewBox=\'0 0 256 256\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cfilter id=\'noise\'%3E%3CfeTurbulence type=\'fractalNoise\' baseFrequency=\'0.9\' numOctaves=\'4\' stitchTiles=\'stitch\'/%3E%3C/filter%3E%3Crect width=\'100%25\' height=\'100%25\' filter=\'url(%23noise)\'/%3E%3C/svg%3E')">
            </div>

            <!-- Decorative circles -->
            <div class="absolute -top-32 -right-32 size-96 rounded-full bg-white/5 blur-3xl"></div>
            <div class="absolute -bottom-32 -left-32 size-96 rounded-full bg-white/5 blur-3xl"></div>

            <!-- Content -->
            <div class="relative z-10 flex flex-col justify-between p-14 w-full">

                <!-- Top logo -->
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-xl bg-white/15 flex items-center justify-center backdrop-blur-sm">
                        <svg viewBox="0 0 24 24" fill="none" class="size-5 text-white" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                            <polyline points="16 7 22 7 22 13" />
                        </svg>
                    </div>
                    <span class="text-white font-semibold text-lg">SalesApp</span>
                </div>

                <!-- Main copy -->
                <div class="space-y-6">
                    <h2 class="text-5xl font-bold text-white leading-tight tracking-tight">
                        Distelery Technical test,<br />Andy Arias.
                    </h2>
                    <p class="text-blue-100 text-lg leading-relaxed max-w-md">
                        Laravel backend with Vue 3 frontend, Tailwind CSS, and Vite. <br />
                    </p>
                </div>

                <!-- Bottom quote -->
                <p class="text-blue-200/60 text-xs">
                    &copy; {{ new Date().getFullYear() }} SalesApp. All rights reserved.
                </p>

            </div>
        </div>

    </div>
</template>
