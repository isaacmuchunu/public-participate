<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import {
  Bold,
  Italic,
  Strikethrough,
  Code,
  Heading1,
  Heading2,
  List,
  ListOrdered,
  Quote,
  Undo,
  Redo
} from 'lucide-vue-next'
import { computed, watch, onUnmounted } from 'vue'

interface Props {
  modelValue: string
  placeholder?: string
  error?: string
  disabled?: boolean
}

interface Emits {
  (e: 'update:modelValue', value: string): void
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Start writing...',
  disabled: false,
})

const emit = defineEmits<Emits>()

const editor = useEditor({
  content: props.modelValue,
  editable: !props.disabled,
  extensions: [
    StarterKit,
    Placeholder.configure({
      placeholder: props.placeholder,
    }),
  ],
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  },
  editorProps: {
    attributes: {
      class: 'prose prose-sm sm:prose-base lg:prose-lg xl:prose-2xl mx-auto focus:outline-none min-h-[150px] p-4 border rounded-lg',
    },
  },
})

const isActive = (type: string, options?: any) => {
  return computed(() => {
    if (!editor.value) return false
    return editor.value.isActive(type, options)
  })
}

const commands = {
  bold: () => editor.value?.chain().focus().toggleBold().run(),
  italic: () => editor.value?.chain().focus().toggleItalic().run(),
  strike: () => editor.value?.chain().focus().toggleStrike().run(),
  code: () => editor.value?.chain().focus().toggleCode().run(),
  heading1: () => editor.value?.chain().focus().toggleHeading({ level: 1 }).run(),
  heading2: () => editor.value?.chain().focus().toggleHeading({ level: 2 }).run(),
  bulletList: () => editor.value?.chain().focus().toggleBulletList().run(),
  orderedList: () => editor.value?.chain().focus().toggleOrderedList().run(),
  blockquote: () => editor.value?.chain().focus().toggleBlockquote().run(),
  undo: () => editor.value?.chain().focus().undo().run(),
  redo: () => editor.value?.chain().focus().redo().run(),
}

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  if (editor.value && editor.value.getHTML() !== newValue) {
    editor.value.commands.setContent(newValue)
  }
}, { immediate: true })

onUnmounted(() => {
  editor.value?.destroy()
})
</script>

<template>
  <div class="space-y-2">
    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-1 p-2 border rounded-t-lg bg-muted/30">
      <Button
        type="button"
        variant="ghost"
        size="sm"
        @click="commands.undo"
        :disabled="!editor?.can().undo()"
        class="h-8 w-8 p-0"
      >
        <Undo class="h-4 w-4" />
      </Button>
      <Button
        type="button"
        variant="ghost"
        size="sm"
        @click="commands.redo"
        :disabled="!editor?.can().redo()"
        class="h-8 w-8 p-0"
      >
        <Redo class="h-4 w-4" />
      </Button>

      <Separator orientation="vertical" class="h-6" />

      <Button
        type="button"
        variant="ghost"
        size="sm"
        @click="commands.bold"
        :class="{ 'bg-muted': isActive('bold').value }"
        class="h-8 w-8 p-0"
      >
        <Bold class="h-4 w-4" />
      </Button>
      <Button
        type="button"
        variant="ghost"
        size="sm"
        @click="commands.italic"
        :class="{ 'bg-muted': isActive('italic').value }"
        class="h-8 w-8 p-0"
      >
        <Italic class="h-4 w-4" />
      </Button>
      <Button
        type="button"
        variant="ghost"
        size="sm"
        @click="commands.strike"
        :class="{ 'bg-muted': isActive('strike').value }"
        class="h-8 w-8 p-0"
      >
        <Strikethrough class="h-4 w-4" />
      </Button>
      <Button
        type="button"
        variant="ghost"
        size="sm"
        @click="commands.code"
        :class="{ 'bg-muted': isActive('code').value }"
        class="h-8 w-8 p-0"
      >
        <Code class="h-4 w-4" />
      </Button>

      <Separator orientation="vertical" class="h-6" />

      <Button
        type="button"
        variant="ghost"
        size="sm"
        @click="commands.heading1"
        :class="{ 'bg-muted': isActive('heading', { level: 1 }).value }"
        class="h-8 w-8 p-0"
      >
        <Heading1 class="h-4 w-4" />
      </Button>
      <Button
        type="button"
        variant="ghost"
        size="sm"
        @click="commands.heading2"
        :class="{ 'bg-muted': isActive('heading', { level: 2 }).value }"
        class="h-8 w-8 p-0"
      >
        <Heading2 class="h-4 w-4" />
      </Button>

      <Separator orientation="vertical" class="h-6" />

      <Button
        type="button"
        variant="ghost"
        size="sm"
        @click="commands.bulletList"
        :class="{ 'bg-muted': isActive('bulletList').value }"
        class="h-8 w-8 p-0"
      >
        <List class="h-4 w-4" />
      </Button>
      <Button
        type="button"
        variant="ghost"
        size="sm"
        @click="commands.orderedList"
        :class="{ 'bg-muted': isActive('orderedList').value }"
        class="h-8 w-8 p-0"
      >
        <ListOrdered class="h-4 w-4" />
      </Button>
      <Button
        type="button"
        variant="ghost"
        size="sm"
        @click="commands.blockquote"
        :class="{ 'bg-muted': isActive('blockquote').value }"
        class="h-8 w-8 p-0"
      >
        <Quote class="h-4 w-4" />
      </Button>
    </div>

    <!-- Editor -->
    <div class="relative">
      <EditorContent
        :editor="editor"
        class="border rounded-b-lg focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2"
        :class="{ 'opacity-50': disabled }"
      />
    </div>

    <!-- Error Message -->
    <p v-if="error" class="text-sm text-destructive">
      {{ error }}
    </p>

    <!-- Character Count -->
    <div class="flex justify-between text-xs text-muted-foreground">
      <span>Rich text editor with formatting options</span>
      <span>{{ modelValue.length }} characters</span>
    </div>
  </div>
</template>