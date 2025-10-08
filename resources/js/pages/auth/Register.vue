<script setup lang="ts">
import RegisteredUserController from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login } from '@/routes';
import geo from '@/routes/api/geo';
import { AppPageProps } from '@/types';
import { Form, Head, usePage } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

interface GeoOption {
    id: number;
    name: string;
    code?: string | null;
}

interface Props {
    counties: GeoOption[];
}

const props = defineProps<Props>();

const page = usePage<AppPageProps<{ old?: Record<string, unknown> }>>();

const old = computed(() => (page.props.old ?? {}) as Record<string, string>);

const selectedCountyId = ref(old.value?.county_id ?? '');
const selectedConstituencyId = ref(old.value?.constituency_id ?? '');
const selectedWardId = ref(old.value?.ward_id ?? '');

const constituencies = ref<GeoOption[]>([]);
const wards = ref<GeoOption[]>([]);

const isLoadingConstituencies = ref(false);
const isLoadingWards = ref(false);

const fetchJson = async (url: string): Promise<GeoOption[]> => {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to load data');
    }

    const payload = await response.json();

    return payload.data ?? [];
};

const resetConstituencies = () => {
    constituencies.value = [];
    selectedConstituencyId.value = '';
};

const resetWards = () => {
    wards.value = [];
    selectedWardId.value = '';
};

watch(selectedCountyId, async (countyId) => {
    resetConstituencies();
    resetWards();

    if (!countyId) {
        return;
    }

    isLoadingConstituencies.value = true;

    try {
        const data = await fetchJson(geo.constituencies.url({ county: Number(countyId) }));
        constituencies.value = data;
    } catch (error) {
        console.error('Failed to load constituencies', error);
        constituencies.value = [];
    } finally {
        isLoadingConstituencies.value = false;
    }
});

watch(selectedConstituencyId, async (constituencyId) => {
    resetWards();

    if (!constituencyId) {
        return;
    }

    isLoadingWards.value = true;

    try {
        const data = await fetchJson(geo.wards.url({ constituency: Number(constituencyId) }));
        wards.value = data;
    } catch (error) {
        console.error('Failed to load wards', error);
        wards.value = [];
    } finally {
        isLoadingWards.value = false;
    }
});

onMounted(async () => {
    if (selectedCountyId.value) {
        try {
            isLoadingConstituencies.value = true;
            const data = await fetchJson(geo.constituencies.url({ county: Number(selectedCountyId.value) }));
            constituencies.value = data;
        } catch (error) {
            console.error('Failed to load constituencies', error);
            constituencies.value = [];
        } finally {
            isLoadingConstituencies.value = false;
        }
    }

    if (selectedConstituencyId.value) {
        try {
            isLoadingWards.value = true;
            const data = await fetchJson(geo.wards.url({ constituency: Number(selectedConstituencyId.value) }));
            wards.value = data;
        } catch (error) {
            console.error('Failed to load wards', error);
            wards.value = [];
        } finally {
            isLoadingWards.value = false;
        }
    }
});
</script>

<template>
    <div class="bg-background min-h-screen px-4 py-12 sm:px-6 lg:px-8">
        <Head title="Register" />

        <div class="mx-auto max-w-7xl">
            <div class="mb-8 text-center">
                <h1 class="text-4xl font-bold tracking-tight">Create your workspace profile</h1>
                <p class="text-muted-foreground mt-2 text-lg">
                    Register to share insights, monitor participation milestones, and collaborate with legislative teams.
                </p>
            </div>

            <div class="mx-auto max-w-6xl space-y-8">
                <div
                    class="rounded-2xl border border-[hsl(var(--info-border))] bg-[hsl(var(--info-background))] px-6 py-5 text-sm text-[hsl(var(--info-foreground))]"
                >
                    Provide accurate contact and residency details. We will send a one-time passcode to your phone and email to confirm your identity
                    before your account is activated.
                </div>

                <Form
                    v-bind="RegisteredUserController.store.form()"
                    :reset-on-success="['password', 'password_confirmation']"
                    v-slot="{ errors, processing }"
                    class="space-y-8"
                >
                    <!-- Row 1: Names -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="first_name" class="text-base font-medium">First Name</Label>
                            <Input
                                id="first_name"
                                type="text"
                                required
                                autofocus
                                autocomplete="given-name"
                                name="first_name"
                                placeholder="Jane"
                                class="h-12 text-base"
                            />
                            <InputError :message="errors.first_name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="last_name" class="text-base font-medium">Last Name</Label>
                            <Input
                                id="last_name"
                                type="text"
                                required
                                autocomplete="family-name"
                                name="last_name"
                                placeholder="Wanjiku"
                                class="h-12 text-base"
                            />
                            <InputError :message="errors.last_name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="surname" class="text-base font-medium">Surname</Label>
                            <Input
                                id="surname"
                                type="text"
                                autocomplete="additional-name"
                                name="surname"
                                placeholder="Middle name (optional)"
                                class="h-12 text-base"
                            />
                            <InputError :message="errors.surname" />
                        </div>
                    </div>

                    <!-- Row 2: Contact -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="email" class="text-base font-medium">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                required
                                autocomplete="email"
                                name="email"
                                placeholder="you@pps.ke"
                                class="h-12 text-base"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="space-y-2">
                            <Label for="phone" class="text-base font-medium">Phone</Label>
                            <Input
                                id="phone"
                                name="phone"
                                type="tel"
                                required
                                inputmode="tel"
                                autocomplete="tel"
                                placeholder="07########"
                                class="h-12 text-base"
                            />
                            <p class="text-muted-foreground text-sm">Use your active Kenyan mobile number to receive the verification code.</p>
                            <InputError :message="errors.phone" />
                        </div>

                        <div class="space-y-2">
                            <Label for="national_id" class="text-base font-medium">National ID</Label>
                            <Input
                                id="national_id"
                                name="national_id"
                                type="text"
                                required
                                maxlength="12"
                                autocomplete="off"
                                placeholder="Enter your ID number"
                                class="h-12 text-base"
                            />
                            <InputError :message="errors.national_id" />
                        </div>
                    </div>

                    <!-- Row 3: Location -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="county_id" class="text-base font-medium">County</Label>
                            <select
                                id="county_id"
                                name="county_id"
                                class="border-border/60 bg-background focus:border-primary focus:ring-primary/20 h-12 w-full rounded-xl border px-4 py-3 text-base focus:outline-none focus:ring-2"
                                v-model="selectedCountyId"
                            >
                                <option value="" disabled>Select your county</option>
                                <option v-for="county in props.counties" :key="county.id" :value="String(county.id)">{{ county.name }}</option>
                            </select>
                            <InputError :message="errors.county_id" />
                        </div>

                        <div class="space-y-2">
                            <Label for="constituency_id" class="text-base font-medium">Constituency</Label>
                            <select
                                id="constituency_id"
                                name="constituency_id"
                                class="border-border/60 bg-background focus:border-primary focus:ring-primary/20 h-12 w-full rounded-xl border px-4 py-3 text-base focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:opacity-50"
                                v-model="selectedConstituencyId"
                                :disabled="!selectedCountyId || isLoadingConstituencies"
                            >
                                <option value="" disabled>
                                    {{
                                        !selectedCountyId
                                            ? 'Select your county first'
                                            : isLoadingConstituencies
                                              ? 'Loading constituencies...'
                                              : 'Select your constituency'
                                    }}
                                </option>
                                <option v-for="constituency in constituencies" :key="constituency.id" :value="String(constituency.id)">
                                    {{ constituency.name }}
                                </option>
                            </select>
                            <InputError :message="errors.constituency_id" />
                        </div>

                        <div class="space-y-2">
                            <Label for="ward_id" class="text-base font-medium">Ward</Label>
                            <select
                                id="ward_id"
                                name="ward_id"
                                class="border-border/60 bg-background focus:border-primary focus:ring-primary/20 h-12 w-full rounded-xl border px-4 py-3 text-base focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:opacity-50"
                                v-model="selectedWardId"
                                :disabled="!selectedConstituencyId || isLoadingWards"
                            >
                                <option value="" disabled>
                                    {{
                                        !selectedConstituencyId
                                            ? 'Select your constituency first'
                                            : isLoadingWards
                                              ? 'Loading wards...'
                                              : 'Select your ward'
                                    }}
                                </option>
                                <option v-for="ward in wards" :key="ward.id" :value="String(ward.id)">{{ ward.name }}</option>
                            </select>
                            <InputError :message="errors.ward_id" />
                        </div>
                    </div>

                    <!-- Row 4: Passwords -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="password" class="text-base font-medium">Password</Label>
                            <Input
                                id="password"
                                type="password"
                                required
                                autocomplete="new-password"
                                name="password"
                                placeholder="Create a strong password"
                                class="h-12 text-base"
                            />
                            <p class="text-muted-foreground text-sm">Use at least 10 characters with a mix of letters, numbers, and symbols.</p>
                            <InputError :message="errors.password" />
                        </div>

                        <div class="space-y-2">
                            <Label for="password_confirmation" class="text-base font-medium">Confirm Password</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                name="password_confirmation"
                                placeholder="Repeat the password"
                                class="h-12 text-base"
                            />
                            <InputError :message="errors.password_confirmation" />
                        </div>

                        <div class="space-y-2">
                            <!-- Empty cell for grid alignment -->
                        </div>
                    </div>

                    <div class="flex justify-center">
                        <Button type="submit" class="h-14 w-full max-w-md text-lg font-semibold" :disabled="processing">
                            <LoaderCircle v-if="processing" class="mr-2 h-5 w-5 animate-spin" />
                            REGISTER
                        </Button>
                    </div>

                    <div class="border-border/60 bg-background/80 text-muted-foreground rounded-2xl border border-dashed px-6 py-4 text-sm">
                        By creating an account you agree to comply with the Public Participation Code of Conduct and consent to secure processing of
                        your submissions.
                    </div>

                    <div class="text-muted-foreground text-center text-base">
                        Already have access?
                        <TextLink :href="login()" class="text-primary font-medium hover:underline">Sign in</TextLink>
                    </div>
                </Form>
            </div>
        </div>
    </div>
</template>
