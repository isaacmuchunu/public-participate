<script setup lang="ts">
import InvitationAcceptanceController from '@/actions/App/Http/Controllers/Auth/InvitationAcceptanceController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle, UserCheck } from 'lucide-vue-next';

const props = defineProps<{
    token: string;
    user: {
        name: string;
        email: string;
        role: string;
    };
}>();

const roleDisplayNames: Record<string, string> = {
    mp: 'Member of Parliament',
    senator: 'Senator',
    clerk: 'Clerk',
    admin: 'Administrator',
};

const roleDisplay = roleDisplayNames[props.user.role] || props.user.role;
</script>

<template>
    <AuthLayout title="Accept Invitation" :description="`Welcome, ${user.name}! Set your password to activate your account.`">
        <Head title="Accept Invitation" />

        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950">
            <div class="flex items-center gap-3">
                <UserCheck class="h-5 w-5 text-green-600 dark:text-green-400" />
                <div>
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ roleDisplay }} Account</p>
                    <p class="text-xs text-green-600 dark:text-green-400">{{ user.email }}</p>
                </div>
            </div>
        </div>

        <Form
            v-bind="InvitationAcceptanceController.store.form()"
            :transform="(data) => ({ ...data, token })"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        class="mt-1 block w-full"
                        autofocus
                        placeholder="Enter a strong password"
                        required
                    />
                    <InputError :message="errors.password" />
                    <p class="text-muted-foreground text-xs">Must be at least 8 characters with uppercase, lowercase, and numbers</p>
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm Password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        autocomplete="new-password"
                        class="mt-1 block w-full"
                        placeholder="Confirm your password"
                        required
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button type="submit" class="mt-4 w-full" :disabled="processing">
                    <LoaderCircle v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                    Activate Account & Sign In
                </Button>
            </div>
        </Form>

        <div class="text-muted-foreground mt-6 text-center text-xs">
            <p>By activating your account, you agree to the platform's terms of service.</p>
        </div>
    </AuthLayout>
</template>
