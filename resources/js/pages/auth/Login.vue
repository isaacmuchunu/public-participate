<script setup lang="ts">
import AuthenticatedSessionController from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { register } from '@/routes';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <div class="min-h-screen bg-background py-12 px-4 sm:px-6 lg:px-8">
        <Head title="Sign in" />

        <div class="mx-auto max-w-md">
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold tracking-tight">Welcome back</h1>
                <p class="mt-2 text-base text-muted-foreground">
                    Sign in to orchestrate public participation programmes and track engagement intelligence.
                </p>
            </div>

            <div class="space-y-6">
                <div
                    v-if="status"
                    class="rounded-2xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-800 dark:text-emerald-200"
                >
                    {{ status }}
                </div>

                <div class="rounded-2xl border border-[hsl(var(--info-border))] bg-[hsl(var(--info-background))] px-4 py-3 text-sm text-[hsl(var(--info-foreground))]">
                    Use your organisation email address to access the participation control centre. Contact the system administrator if you need to
                    restore access.
                </div>

                <Form
                    v-bind="AuthenticatedSessionController.store.form()"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                    class="space-y-6"
                >
                    <div class="grid gap-5">
                        <div class="space-y-2">
                            <Label for="email" class="text-sm font-medium">Work email</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autofocus
                                autocomplete="email"
                                placeholder="you@pps.ke"
                                class="h-11"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="space-y-2">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <Label for="password" class="text-sm font-medium">Password</Label>
                                <TextLink v-if="canResetPassword" :href="request()" class="text-sm hover:underline">Forgot password?</TextLink>
                            </div>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                                class="h-11"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border/50 bg-background/80 px-4 py-3">
                            <Label for="remember" class="flex items-center gap-3 text-sm font-medium">
                                <Checkbox id="remember" name="remember" />
                                <span>Keep me signed in on this device</span>
                            </Label>
                            <p class="text-xs text-muted-foreground">Only select this on secure, personal devices.</p>
                        </div>
                    </div>

                    <div class="flex justify-center">
                        <Button type="submit" class="h-11 w-full max-w-xs text-base font-semibold" :disabled="processing">
                            <LoaderCircle v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                            Access dashboard
                        </Button>
                    </div>

                    <div class="text-center text-sm text-muted-foreground">
                        New to the platform?
                        <TextLink :href="register()" class="font-medium text-primary hover:underline">Request an account</TextLink>
                    </div>
                </Form>
            </div>
        </div>
    </div>
</template>
