<template>
    <div class="flex flex-col mb-4">
        <label v-if="label" class="mb-2" for="">{{ label }}</label>
        <input
            class="border rounded-md p-2 border-[#6F6F6E]"
            v-model=model
            :placeholder=placeholder
            ref="input"
            :type=type
        />
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';

defineProps({
    label: String,
    type: {
      type: String,
      default: 'text',
    },
    placeholder: String,
});

const model = defineModel({
    type: String,
    required: true, 
});

const input: any = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>
