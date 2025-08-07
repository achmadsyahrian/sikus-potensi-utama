<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    type: {
        type: String,
        default: 'text',
    },
    placeholder: {
        type: String,
        default: '',
    },
    modelValue: {
        required: true,
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="mb-3">
        <label class="form-label">{{ label }}</label>
        <input 
            :type="type" 
            class="form-control" 
            :placeholder="placeholder"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
        >
    </div>
</template>