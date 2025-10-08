import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';

interface FormData {
    [key: string]: any;
}

export function useFormDraft<T extends FormData>(formKey: string, initialData: T, autosaveInterval = 3000) {
    const formData = ref<T>({ ...initialData });
    const lastSaved = ref<Date | null>(null);
    const isDirty = ref(false);

    // Load draft from localStorage
    const loadDraft = (): boolean => {
        const saved = localStorage.getItem(formKey);
        if (saved) {
            try {
                const draft = JSON.parse(saved);
                formData.value = { ...initialData, ...draft.data };
                lastSaved.value = new Date(draft.savedAt);
                return true;
            } catch (e) {
                console.error('Failed to load draft:', e);
                return false;
            }
        }
        return false;
    };

    // Save draft to localStorage
    const saveDraft = () => {
        try {
            localStorage.setItem(
                formKey,
                JSON.stringify({
                    data: formData.value,
                    savedAt: new Date().toISOString(),
                }),
            );
            lastSaved.value = new Date();
            isDirty.value = false;
        } catch (e) {
            console.error('Failed to save draft:', e);
        }
    };

    // Debounced autosave
    const debouncedSave = useDebounceFn(saveDraft, autosaveInterval);

    // Watch for changes and trigger autosave
    watch(
        formData,
        () => {
            isDirty.value = true;
            debouncedSave();
        },
        { deep: true },
    );

    // Clear draft from localStorage
    const clearDraft = () => {
        localStorage.removeItem(formKey);
        formData.value = { ...initialData };
        lastSaved.value = null;
        isDirty.value = false;
    };

    // Check if draft exists
    const hasDraft = (): boolean => {
        return localStorage.getItem(formKey) !== null;
    };

    return {
        formData,
        lastSaved,
        isDirty,
        loadDraft,
        saveDraft,
        clearDraft,
        hasDraft,
    };
}
