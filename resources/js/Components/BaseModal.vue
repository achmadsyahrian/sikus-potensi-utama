<script setup>
import { computed } from 'vue';

const props = defineProps({
    id: {
        type: String,
        required: true,
    },
    title: {
        type: String,
        default: 'Modal Title',
    },
    size: {
        type: String,
        default: 'lg', // default size
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const modalSizeClass = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'modal-sm';
        case 'lg':
            return 'modal-lg';
        case 'xl':
            return 'modal-xl';
        default:
            return '';
    }
});
</script>

<template>
    <div :id="id" class="modal modal-blur fade" tabindex="-1" role="dialog" :aria-labelledby="`${id}Label`" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" :class="modalSizeClass" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" :id="`${id}Label`">{{ title }}</h5>
                    <button v-if="closeable" type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <slot name="body"></slot>
                </div>
                <div class="modal-footer">
                    <slot name="footer">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Save changes</button>
                    </slot>
                </div>
            </div>
        </div>
    </div>
</template>
