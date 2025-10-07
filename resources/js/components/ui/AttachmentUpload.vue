<script setup lang="ts">
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import Progress from '@/components/ui/progress/Progress.vue'
import Icon from '@/components/Icon.vue'
import { Upload, X, File, AlertCircle } from 'lucide-vue-next'

interface Props {
  modelValue: File[]
  maxFiles?: number
  maxSizeMb?: number
  acceptedTypes?: string
  disabled?: boolean
}

interface Emits {
  (e: 'update:modelValue', value: File[]): void
}

const props = withDefaults(defineProps<Props>(), {
  maxFiles: 3,
  maxSizeMb: 10,
  acceptedTypes: 'image/*,.pdf,.doc,.docx,.txt',
  disabled: false,
})

const emit = defineEmits<Emits>()

const isDragOver = ref(false)
const uploadProgress = ref<Record<string, number>>({})

const files = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const canAddMore = computed(() => files.value.length < props.maxFiles)

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const validateFile = (file: File): string | null => {
  if (file.size > props.maxSizeMb * 1024 * 1024) {
    return `File size must be less than ${props.maxSizeMb}MB`
  }

  const acceptedTypesArray = props.acceptedTypes.split(',')
  const isValidType = acceptedTypesArray.some(type => {
    if (type.startsWith('.')) {
      return file.name.toLowerCase().endsWith(type.toLowerCase())
    }
    return file.type.match(type.replace('*', '.*'))
  })

  if (!isValidType) {
    return `File type not supported. Allowed: ${props.acceptedTypes}`
  }

  return null
}

const handleFileSelect = (event: Event) => {
  if (props.disabled || !canAddMore.value) return

  const target = event.target as HTMLInputElement
  const selectedFiles = Array.from(target.files || [])

  selectedFiles.forEach(file => {
    const error = validateFile(file)
    if (error) {
      // Show error toast or emit error event
      console.error(error)
      return
    }

    if (!files.value.find(f => f.name === file.name && f.size === file.size)) {
      files.value.push(file)
    }
  })

  target.value = ''
}

const handleDrop = (event: DragEvent) => {
  event.preventDefault()
  isDragOver.value = false

  if (props.disabled || !canAddMore.value) return

  const droppedFiles = Array.from(event.dataTransfer?.files || [])

  droppedFiles.forEach(file => {
    const error = validateFile(file)
    if (error) {
      console.error(error)
      return
    }

    if (!files.value.find(f => f.name === file.name && f.size === file.size)) {
      files.value.push(file)
    }
  })
}

const handleDragOver = (event: DragEvent) => {
  event.preventDefault()
  if (!props.disabled && canAddMore.value) {
    isDragOver.value = true
  }
}

const handleDragLeave = (event: DragEvent) => {
  event.preventDefault()
  isDragOver.value = false
}

const removeFile = (index: number) => {
  files.value.splice(index, 1)
}

const getFileIcon = (file: File) => {
  if (file.type.startsWith('image/')) return 'image'
  if (file.type === 'application/pdf') return 'file-text'
  if (file.type.includes('document') || file.type.includes('text')) return 'file-text'
  return 'file'
}
</script>

<template>
  <div class="space-y-4">
    <!-- Upload Area -->
    <Card
      class="border-2 border-dashed transition-colors"
      :class="[
        isDragOver && canAddMore ? 'border-primary bg-primary/5' : 'border-muted-foreground/25',
        disabled ? 'opacity-50' : ''
      ]"
      @drop="handleDrop"
      @dragover="handleDragOver"
      @dragleave="handleDragLeave"
    >
      <CardContent class="flex flex-col items-center justify-center p-8 text-center">
        <Upload class="h-12 w-12 text-muted-foreground mb-4" />
        <div class="space-y-2">
          <p class="text-sm font-medium">
            Drop files here or click to browse
          </p>
          <p class="text-xs text-muted-foreground">
            Up to {{ maxFiles }} files, max {{ maxSizeMb }}MB each
          </p>
          <p class="text-xs text-muted-foreground">
            Supported: {{ acceptedTypes }}
          </p>
        </div>
        <Button
          type="button"
          variant="outline"
          class="mt-4"
          :disabled="disabled || !canAddMore"
          as-child
        >
          <label class="cursor-pointer">
            <input
              type="file"
              multiple
              :accept="acceptedTypes"
              @change="handleFileSelect"
              class="sr-only"
              :disabled="disabled || !canAddMore"
            />
            Choose Files
          </label>
        </Button>
      </CardContent>
    </Card>

    <!-- File List -->
    <div v-if="files.length > 0" class="space-y-2">
      <p class="text-sm font-medium">
        {{ files.length }} file{{ files.length !== 1 ? 's' : '' }} selected
      </p>

      <div class="space-y-2">
        <div
          v-for="(file, index) in files"
          :key="`${file.name}-${file.size}-${index}`"
          class="flex items-center gap-3 p-3 border rounded-lg bg-muted/30"
        >
          <Icon :name="getFileIcon(file)" class="h-5 w-5 text-muted-foreground flex-shrink-0" />

          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">{{ file.name }}</p>
            <p class="text-xs text-muted-foreground">{{ formatFileSize(file.size) }}</p>

            <!-- Upload Progress (if uploading) -->
            <div v-if="uploadProgress[file.name]" class="mt-2">
              <Progress :value="uploadProgress[file.name]" class="h-1" />
              <p class="text-xs text-muted-foreground mt-1">
                {{ uploadProgress[file.name] }}% uploaded
              </p>
            </div>
          </div>

          <Button
            type="button"
            variant="ghost"
            size="sm"
            @click="removeFile(index)"
            :disabled="disabled"
            class="h-8 w-8 p-0 text-muted-foreground hover:text-destructive"
          >
            <X class="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="files.length >= maxFiles" class="flex items-center gap-2 text-sm text-muted-foreground">
      <AlertCircle class="h-4 w-4" />
      Maximum {{ maxFiles }} files allowed
    </div>
  </div>
</template>