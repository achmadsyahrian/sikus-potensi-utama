<script setup>
import { onMounted, onUnmounted, ref } from 'vue';

const tooltipContainer = ref(null);
let tooltipInstance = null; // Menambahkan variabel untuk instance tooltip

onMounted(() => {
    if (tooltipContainer.value && window.bootstrap && window.bootstrap.Tooltip) {
        tooltipInstance = new bootstrap.Tooltip(tooltipContainer.value, {
            trigger: 'hover focus',
            // delay: { 'hide': 500 }
        });
    }
});

onUnmounted(() => {
    if (tooltipInstance) {
        tooltipInstance.dispose();
    }
});
</script>

<template>
    <div ref="tooltipContainer">
        <slot />
    </div>
</template>
