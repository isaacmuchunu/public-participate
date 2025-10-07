<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';

interface Clause {
    id: number;
    clause_number: string;
    title: string;
}

interface Bill {
    id: number;
    title: string;
}

interface Props {
    clause: Clause;
    bill: Bill;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    submitted: [];
    cancelled: [];
}>();

const form = useForm({
    bill_id: props.bill.id,
    clause_id: props.clause.id,
    content: '',
    submission_type: 'comment' as 'support' | 'oppose' | 'neutral' | 'amendment' | 'comment',
    is_anonymous: false,
    attachments: [] as File[],
});

const lastSaved = ref<Date | null>(null);
const charCount = ref(0);

// Character counter
watch(
    () => form.content,
    (newValue) => {
        charCount.value = newValue.length;
    },
);

// Auto-save draft every 3 seconds
const saveDraft = useDebounceFn(() => {
    if (form.content.length >= 10) {
        localStorage.setItem(
            `draft-clause-${props.clause.id}`,
            JSON.stringify({
                content: form.content,
                submission_type: form.submission_type,
                is_anonymous: form.is_anonymous,
                saved_at: new Date().toISOString(),
            }),
        );
        lastSaved.value = new Date();
    }
}, 3000);

watch(() => form.content, saveDraft);
watch(() => form.submission_type, saveDraft);

// Load draft on mount
const loadDraft = () => {
    const saved = localStorage.getItem(`draft-clause-${props.clause.id}`);
    if (saved) {
        try {
            const draft = JSON.parse(saved);
            form.content = draft.content || '';
            form.submission_type = draft.submission_type || 'comment';
            form.is_anonymous = draft.is_anonymous || false;
            lastSaved.value = new Date(draft.saved_at);
        } catch (e) {
            console.error('Failed to load draft:', e);
        }
    }
};

loadDraft();

const submitComment = () => {
    form.post('/submissions', {
        preserveScroll: true,
        onSuccess: () => {
            localStorage.removeItem(`draft-clause-${props.clause.id}`);
            emit('submitted');
        },
    });
};

const cancel = () => {
    emit('cancelled');
};

const isValidLength = (length: number) => {
    return length >= 50 && length <= 10000;
};

const getCharCountColor = () => {
    if (charCount.value < 50) return 'text-muted-foreground';
    if (charCount.value > 10000) return 'text-red-600';
    return 'text-green-600';
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Comment on Clause {{ clause.clause_number }}</CardTitle>
            <CardDescription>
                {{ clause.title }}
            </CardDescription>
        </CardHeader>

        <form @submit.prevent="submitComment">
            <CardContent class="space-y-4">
                <!-- Submission Type -->
                <div class="space-y-2">
                    <Label for="submission_type">Your Position</Label>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-for="type in ['support', 'oppose', 'neutral', 'amendment', 'comment']"
                            :key="type"
                            type="button"
                            :variant="form.submission_type === type ? 'default' : 'outline'"
                            size="sm"
                            @click="form.submission_type = type as any"
                            class="capitalize"
                        >
                            {{ type }}
                        </Button>
                    </div>
                </div>

        <!-- Content Rich Text Editor -->
        <div class="space-y-2">
          <Label for="content">Your Comment</Label>
          <RichTextEditor
            id="content"
            v-model="form.content"
            placeholder="Share your thoughts on this clause..."
            :error="form.errors.content"
            :disabled="form.processing"
          />
          <InputError :message="form.errors.content" id="content-error" />
          <div class="flex justify-between text-xs">
            <p class="text-muted-foreground">
              Minimum 50 characters, maximum 10,000
            </p>
            <p :class="getCharCountColor()">
              {{ charCount }} / 10,000
            </p>
          </div>
        </div>
                </div>

        <!-- Supporting Documents -->
        <div>
          <Label>Supporting Documents (Optional)</Label>
          <AttachmentUpload
            v-model="form.attachments"
            :max-files="3"
            :max-size-mb="10"
            :disabled="form.processing"
          />
        </div>

        <!-- Anonymous Checkbox -->
        <div class="flex items-center gap-2">
          <Checkbox
            id="anonymous"
            v-model:checked="form.is_anonymous"
          />
          <Label for="anonymous" class="cursor-pointer text-sm">
            Submit anonymously (your identity will be hidden)
          </Label>
        </div>
            </CardContent>

            <CardFooter class="flex items-center justify-between">
                <!-- Draft Indicator -->
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <Icon v-if="lastSaved" name="check" class="h-3 w-3 text-green-600" />
                    <span v-if="lastSaved"> Draft saved {{ lastSaved.toLocaleTimeString() }} </span>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2">
                    <Button type="button" variant="outline" @click="cancel" :disabled="form.processing"> Cancel </Button>
                    <Button type="submit" :disabled="form.processing || !isValidLength(charCount)">
                        <Icon v-if="form.processing" name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
                        Submit Comment
                    </Button>
                </div>
            </CardFooter>
        </form>
    </Card>
</template>
