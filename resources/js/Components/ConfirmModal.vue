<script setup>
const props = defineProps({
    title: {
        type: String,
        default: 'Konfirmasi'
    },
    message: {
        type: String,
        default: 'Apakah Anda yakin ingin melanjutkan?'
    },
    confirmText: {
        type: String,
        default: 'Ya, Hapus'
    },
    cancelText: {
        type: String,
        default: 'Batal'
    },
    confirmVariant: {
        type: String,
        default: 'danger'
    }
});

const emit = defineEmits(['confirm']);

const handleConfirm = () => {
    emit('confirm');
};
</script>

<template>
    <div class="modal modal-blur fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div :class="`modal-status bg-${props.confirmVariant}`"></div>
                <div class="modal-body text-center py-4 d-flex flex-column align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon mb-2 text-danger icon-lg" width="24" height="24"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path
                            d="M10.24 3.957l-8.422 14.06a1.989 1.989 0 0 0 1.7 2.983h16.845a1.989 1.989 0 0 0 1.7 -2.983l-8.423 -14.06a1.989 1.989 0 0 0 -3.4 0z" />
                        <path d="M12 9v4" />
                        <path d="M12 17h.01" />
                    </svg>
                    <h3>{{ props.title }}</h3>
                    <div class="text-muted">{{ props.message }}</div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <a href="#" class="btn w-100" data-bs-dismiss="modal">{{ props.cancelText }}</a>
                            </div>
                            <div class="col">
                                <a href="#" :class="`btn btn-${props.confirmVariant} w-100`" data-bs-dismiss="modal" @click.prevent="handleConfirm">{{ props.confirmText }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
