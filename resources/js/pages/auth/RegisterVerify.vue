<script setup lang="ts">
import RegistrationOtpController from '@/actions/App/Http/Controllers/Auth/RegistrationOtpController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { Form, Head, router } from '@inertiajs/vue3';
import { LoaderCircle, RefreshCw } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    contact: {
        email?: string | null;
        phone?: string | null;
    };
    status?: string;
}

const props = defineProps<Props>();

const resending = ref(false);

const resendCode = async () => {
    if (resending.value) {
        return;
    }

    resending.value = true;

    router.post(RegistrationOtpController.resend.url(), undefined, {
        preserveScroll: true,
        onFinish: () => {
            resending.value = false;
        },
    });
};
</script>

<template>
    <AuthBase
        title="Verify your account"
        description="Enter the six-digit code we sent to confirm your participation profile before signing in."
    >
        <Head title="Verify registration" />

        <div class="space-y-6">
            <div
                v-if="status"
                class="rounded-2xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-800 dark:text-emerald-200"
            >
                {{ status }}
            </div>

            <div class="space-y-3 rounded-2xl border border-border/50 bg-muted/30 px-4 py-4 text-sm text-muted-foreground">
                <p>We sent your verification code to:</p>
                <ul class="space-y-1 text-foreground">
                    <li v-if="props.contact.email">Email: <span class="font-medium">{{ props.contact.email }}</span></li>
                    <li v-if="props.contact.phone">Mobile: <span class="font-medium">{{ props.contact.phone }}</span></li>
                </ul>
                <p>Codes expire after 10 minutes for your security.</p>
            </div>

            <Form v-bind="RegistrationOtpController.store.form()" class="space-y-6" v-slot="{ errors, processing }">
                <div class="space-y-2">
                    <Label for="otp" class="text-sm font-medium">Verification code</Label>
                    <Input
                        id="otp"
                        name="otp"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        required
                        autofocus
                        class="tracking-[0.4em]"
                        placeholder="••••••"
                    />
                    <InputError :message="errors.otp" />
                </div>

                <Button type="submit" class="h-12 w-full text-base font-semibold" :disabled="processing">
                    <LoaderCircle v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                    Confirm my account
                </Button>
            </Form>

            <div class="flex items-center justify-between rounded-2xl border border-dashed border-border/60 bg-background/80 px-4 py-3 text-xs text-muted-foreground">
                <span>Didn’t receive the code?</span>
                <Button variant="ghost" class="gap-2 text-xs font-medium" :disabled="resending" @click="resendCode">
                    <RefreshCw v-if="resending" class="h-4 w-4 animate-spin" />
                    <span>{{ resending ? 'Sending…' : 'Resend code' }}</span>
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already verified?
                <TextLink :href="login()" class="font-medium">Sign in</TextLink>
            </div>
        </div>
    </AuthBase>
</template>
