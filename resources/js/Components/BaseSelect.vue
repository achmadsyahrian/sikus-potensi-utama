<script setup>
import { ref } from 'vue';

const props = defineProps({
    label: {
        type: String,
        required: true
    },
    modelValue: [String, Number, Array],
    options: {
        type: Array,
        required: true
    },
    error: String,
    disabled: {
        type: Boolean,
        default: false
    },
    required: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div>
        <label v-if="label" class="form-label" :class="{'required': required}" :for="`select-${label.toLowerCase().replace(/ /g, '-')}`">{{ label }}</label>
        <select
            :id="`select-${label.toLowerCase().replace(/ /g, '-')}`"
            :value="modelValue"
            @change="emit('update:modelValue', $event.target.value)"
            class="form-select"
            :class="{'is-invalid': error}"
            :disabled="disabled"
        >
            <option :value="null" disabled>Pilih {{ label }}</option>
            <option v-for="option in options" :key="option.id" :value="option.id">
                {{ option.name }}
            </option>
        </select>
        <div v-if="error" class="invalid-feedback">
            {{ error }}
        </div>
    </div>
</template>
