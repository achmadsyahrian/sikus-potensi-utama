<script setup>
defineProps({
    id: {
        type: String,
        required: true,
    },
    title: {
        type: String,
        default: 'Detail',
    },
    width: {
        type: String,
        default: '500px',
    },
});
</script>

<template>
    <div class="offcanvas offcanvas-end shadow" tabindex="-1" :id="id" :aria-labelledby="`${id}Label`" :style="{ width: width }">
        <div class="offcanvas-header border-bottom">
            <h2 class="offcanvas-title" :id="`${id}Label`">{{ title }}</h2>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0 d-flex flex-column">
            <slot name="header"></slot>
            <div class="flex-fill overflow-auto">
                <slot name="body"></slot>
            </div>
            <div class="offcanvas-footer p-3 border-top bg-white">
                <slot name="footer">
                    <button type="button" class="btn btn-primary w-100" data-bs-dismiss="offcanvas">Tutup</button>
                </slot>
            </div>
        </div>
    </div>
</template>
