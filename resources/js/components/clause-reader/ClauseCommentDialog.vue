<script setup lang="ts">
/**
 * ClauseCommentDialog Component
 *
 * Modal dialog for submitting comments on specific clauses.
 * Features:
 * - Rich textarea for comment input
 * - Character count with minimum requirement
 * - Anonymous submission option
 * - Form validation and error handling
 * - Accessible dialog with proper focus management
 */

import InputError from '@/components/InputError.vue';
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Checkbox } from '@/components/ui/checkbox';
import Dialog from '@/components/ui/dialog/Dialog.vue';
import DialogContent from '@/components/ui/dialog/DialogContent.vue';
import DialogDescription from '@/components/ui/dialog/DialogDescription.vue';
import DialogFooter from '@/components/ui/dialog/DialogFooter.vue';
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue';
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue';
import { Label } from '@/components/ui/label';
import * as submissionRoutes from '@/routes/submissions';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { toast } from 'vue3-toastify';

interface Clause {
    id: number;
    clause_number: string;
    title: string;
    content: string;
}

interface Bill {
    id: number;
    title: string;
}

interface Props {
    open: boolean;
    clause: Clause;
    bill: Bill;
}

interface Emits {
    (e: 'close'): void;
    (e: 'submit'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const MIN_COMMENT_LENGTH = 50;
const MAX_COMMENT_LENGTH = 5000;

const form = useForm({
    bill_id: props.bill.id,
    clause_id: props.clause.id,
    submission_type: 'comment',
    content: '',
    is_anonymous: false,
});

const commentLength = computed(() => form.content.length);
const isValidLength = computed(() => commentLength.value >= MIN_COMMENT_LENGTH && commentLength.value <= MAX_COMMENT_LENGTH);

const characterCountColor = computed(() => {
    if (commentLength.value < MIN_COMMENT_LENGTH) {
        return 'text-amber-600';
    }
    if (commentLength.value > MAX_COMMENT_LENGTH - 100) {
        return 'text-rose-600';
    }
    return 'text-emerald-600';
});

const handleClose = () => {
    emit('close');
};

const handleSubmit = () => {
    if (!isValidLength.value) {
        toast.error(`Comment must be between ${MIN_COMMENT_LENGTH} and ${MAX_COMMENT_LENGTH} characters`);
        return;
    }

    form.post(submissionRoutes.store().url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Your comment has been submitted successfully');
            form.reset();
            emit('submit');
        },
        onError: (errors) => {
            const errorMessage = errors.content || 'Failed to submit comment. Please try again.';
            toast.error(errorMessage);
        },
    });
};

// Reset form when dialog closes
watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            form.reset();
        }
    },
);

// Update form IDs when clause changes
watch(
    () => props.clause.id,
    (newClauseId) => {
        form.clause_id = newClauseId;
    },
);
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>Comment on Clause {{ clause.clause_number }}</DialogTitle>
                <DialogDescription> Share your thoughts, concerns, or suggestions about this specific clause. </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit">
                <div class="space-y-6">
                    <!-- Clause Context -->
                    <Card class="bg-emerald-50/50">
                        <CardContent class="pt-4">
                            <p class="text-sm font-medium text-emerald-900">{{ clause.title }}</p>
                            <p class="mt-2 line-clamp-3 text-xs text-emerald-800/70">
                                {{ clause.content }}
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Comment Input -->
                    <div class="space-y-2">
                        <Label for="comment-content" class="text-sm font-semibold text-emerald-900">
                            Your Comment
                            <span class="text-rose-600">*</span>
                        </Label>
                        <textarea
                            id="comment-content"
                            v-model="form.content"
                            rows="8"
                            :maxlength="MAX_COMMENT_LENGTH"
                            :aria-invalid="!!form.errors.content"
                            :aria-describedby="form.errors.content ? 'comment-error' : 'comment-help'"
                            placeholder="Explain your perspective on this clause. Be specific and constructive in your feedback..."
                            class="w-full rounded-lg border border-emerald-200 bg-white px-3 py-2 text-sm text-emerald-900 outline-none transition focus:border-emerald-400 focus:ring-2 focus:ring-emerald-200"
                        ></textarea>

                        <div class="flex items-center justify-between">
                            <p id="comment-help" class="text-xs text-emerald-800/70">Minimum {{ MIN_COMMENT_LENGTH }} characters</p>
                            <p :class="['text-xs font-medium', characterCountColor]">{{ commentLength }} / {{ MAX_COMMENT_LENGTH }}</p>
                        </div>

                        <InputError v-if="form.errors.content" :id="'comment-error'" :message="form.errors.content" />
                    </div>

                    <!-- Anonymous Option -->
                    <div class="flex items-center gap-2">
                        <Checkbox id="anonymous" v-model:checked="form.is_anonymous" aria-describedby="anonymous-help" />
                        <Label for="anonymous" class="cursor-pointer text-sm font-normal text-emerald-900"> Submit anonymously </Label>
                    </div>
                    <p id="anonymous-help" class="text-xs text-emerald-800/70">
                        Your identity will be hidden from legislators and the public, but clerks can still verify your submission.
                    </p>
                </div>

                <DialogFooter class="mt-6">
                    <Button type="button" variant="outline" class="border-emerald-200 text-emerald-700 hover:border-emerald-400" @click="handleClose">
                        Cancel
                    </Button>
                    <Button type="submit" class="bg-emerald-600 text-white hover:bg-emerald-700" :disabled="form.processing || !isValidLength">
                        <span v-if="form.processing">Submitting...</span>
                        <span v-else>Submit Comment</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
