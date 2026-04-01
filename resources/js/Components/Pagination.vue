<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div v-if="links.length > 3" class="d-flex justify-content-end align-items-center mt-4">
        <ul class="pagination m-0">
            <template v-for="(link, index) in links" :key="index">
                <li
                    class="page-item"
                    :class="{ 'active': link.active, 'disabled': link.url === null }"
                >
                    <Link
                        v-if="link.url"
                        class="page-link"
                        :href="link.url"
                        v-html="link.label"
                        preserve-scroll
                    />
                    <span
                        v-else
                        class="page-link"
                        v-html="link.label"
                    ></span>
                </li>
            </template>
        </ul>
    </div>
</template>

<style scoped>
.page-link {
    box-shadow: none !important;
    transition: all 0.2s ease-in-out;
}
.page-item.active .page-link {
    background-color: #206bc4;
    border-color: #206bc4;
    color: white;
}
</style>
