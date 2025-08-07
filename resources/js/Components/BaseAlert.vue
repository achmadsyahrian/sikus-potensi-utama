<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'info',
    },
    title: {
        type: String,
        required: true,
    },
    message: {
        type: String,
        required: true,
    },
});

const alertClass = computed(() => `alert-${props.type}`);

const iconClass = computed(() => {
    switch (props.type) {
        case 'success':
            return 'fa-solid fa-circle-check';
        case 'danger':
            return 'fa-solid fa-circle-xmark';
        case 'warning':
            return 'fa-solid fa-triangle-exclamation';
        case 'info':
        default:
            return 'fa-solid fa-circle-info';
    }
});
</script>

<template>
    <div class="alert d-flex align-items-center mb-4" :class="alertClass">
        <i class="me-2" :class="iconClass"></i>
        <div>
            <strong>{{ title }}</strong><br>
            {{ message }}
        </div>
    </div>
</template>