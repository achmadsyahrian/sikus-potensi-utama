<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
    label: {
        type: String,
        required: true,
    },
    variant: {
        type: String,
        default: 'primary',
    },
    outline: {
        type: Boolean,
        default: false,
    },
    full: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <button
        :type="type"
        class="btn"
        :class="[
            {'w-100': full},
            outline ? `btn-outline-${variant}` : `btn-${variant}`
        ]"
    >
        {{ label }}
    </button>
</template>