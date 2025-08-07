<script setup>
import { nextTick, ref, watch, onUpdated } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const flashMessage = ref(null);
let timeout;

const hideAlert = () => {
    flashMessage.value = null;
};

const showFlash = async () => {
    const newFlash = page.props.flash;
    clearTimeout(timeout);
    flashMessage.value = null;

    await nextTick();

    flashMessage.value = null;

    if (newFlash.success) {
        flashMessage.value = { type: 'success', message: newFlash.success };
    } else if (newFlash.error) {
        flashMessage.value = { type: 'danger', message: newFlash.error };
    }

    if (flashMessage.value) {
        timeout = setTimeout(hideAlert, 5000);
    }
};

onUpdated(() => {
    showFlash();
});

watch(() => page.props.flash, () => {
    showFlash();
}, { immediate: true });
</script>

<template>
    <div class="flash-notification-container">
        <Transition
            enter-active-class="flash-notification-enter"
            leave-active-class="flash-notification-leave"
        >
            <div v-if="flashMessage" :class="`flash-notification flash-notification-${flashMessage.type}`" role="alert">
                <div class="flash-notification-content">
                    <div class="flash-notification-icon">
                        <template v-if="flashMessage.type === 'success'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                            </svg>
                        </template>
                        <template v-else-if="flashMessage.type === 'danger'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.72 6.97a.75.75 0 10-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 101.06 1.06L12 13.06l1.72 1.72a.75.75 0 101.06-1.06L13.06 12l1.72-1.72a.75.75 0 10-1.06-1.06L12 10.94l-1.72-1.72z" clip-rule="evenodd" />
                            </svg>
                        </template>
                    </div>
                    <div class="flash-notification-message">
                        {{ flashMessage.message }}
                    </div>
                    <button type="button" class="flash-notification-close" @click="hideAlert" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                    </button>
                </div>
                <div class="flash-notification-progress" v-if="flashMessage"></div>
            </div>
        </Transition>
    </div>
</template>

<style>
.flash-notification-container {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 1050;
    max-width: 350px;
    width: 100%;
}

.flash-notification {
    position: relative;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 0.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    transition: all 0.3s ease;
}

.flash-notification-success {
    background-color: #f0fdf4;
    color: #166534;
    border-left: 4px solid #22c55e;
}

.flash-notification-danger {
    background-color: #fef2f2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

.flash-notification-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.flash-notification-icon {
    flex-shrink: 0;
    width: 1.5rem;
    height: 1.5rem;
}

.flash-notification-icon svg {
    width: 100%;
    height: 100%;
}

.flash-notification-message {
    flex-grow: 1;
    font-size: 0.875rem;
    line-height: 1.25rem;
}

.flash-notification-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
    background: transparent;
    border: none;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.2s;
    color: inherit;
}

.flash-notification-close:hover {
    opacity: 1;
}

.flash-notification-close svg {
    width: 1rem;
    height: 1rem;
}

.flash-notification-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 4px;
    background-color: currentColor;
    opacity: 0.3;
    width: 100%;
    transform-origin: left;
    animation: progress 5s linear forwards;
}

.flash-notification-enter {
    animation: slideIn 0.3s ease-out forwards;
}

.flash-notification-leave {
    animation: slideOut 0.3s ease-in forwards;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

@keyframes progress {
    from {
        transform: scaleX(1);
    }
    to {
        transform: scaleX(0);
    }
}
</style>