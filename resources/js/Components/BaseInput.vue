<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: String,
    type: {
        type: String,
        default: 'text',
    },
    modelValue: [String, Number, Boolean],
    error: String,
    disabled: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits(['update:modelValue']);

const computedValue = computed({
    get: () => props.modelValue,
    set: (value) => {
        emit('update:modelValue', value);
    }
});
</script>

<template>
    <div>
        <label v-if="label" class="form-label" :class="{'required': required}">{{ label }}</label>
        <input
            :type="type"
            class="form-control"
            :class="{ 'is-invalid': error }"
            v-model="computedValue"
            :disabled="disabled"
        />
        <div v-if="error" class="invalid-feedback">{{ error }}</div>
    </div>
</template>
