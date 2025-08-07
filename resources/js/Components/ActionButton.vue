<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        default: 'get',
    },
    variant: {
        type: String,
        default: 'primary',
    },
    tooltip: {
        type: String,
        default: '',
    },
    iconName: {
        type: String,
        required: true,
    },
});

const iconPath = computed(() => {
    switch (props.iconName) {
        case 'edit':
            return `
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"></path>
                    <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.415 -8.385z"></path>
                    <path d="M16 5l3 3"></path>
                </svg>
            `;
        case 'trash':
            return `
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M4 7l16 0"></path>
                    <path d="M10 11l0 6"></path>
                    <path d="M14 11l0 6"></path>
                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                </svg>
            `;
        default:
            return '';
    }
});
</script>

<template>
    <Link 
        :href="href"
        :method="method"
        as="button"
        class="btn btn-icon"
        :class="`btn-outline-${variant}`"
        data-bs-toggle="tooltip"
        :title="tooltip"
    >
        <span v-html="iconPath"></span>
    </Link>
</template>