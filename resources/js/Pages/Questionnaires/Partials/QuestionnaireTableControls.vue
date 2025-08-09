<script setup>
import { router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import BaseButton from '@/Components/BaseButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { debounce } from 'lodash';
import { watch } from 'vue';

const props = defineProps({
    filters: Object,
});

const search = ref(props.filters.search);
const isSyncing = ref(false);
const confirmModal = ref(null);

const handleSearch = debounce(() => {
    router.get(
        route('questionnaires.index'),
        { search: search.value },
        { preserveState: true, replace: true }
    );
}, 300);

const handleRefresh = () => {
    search.value = '';
    router.get(route('questionnaires.index'), {}, { preserveState: true, replace: true });
};

watch(search, () => {
    handleSearch();
}, { immediate: false });

</script>

<template>
    <div class="card mb-3">
        <div class="card-body d-flex flex-column flex-md-row align-items-md-center">
            <!-- Search Input -->
            <div class="input-icon w-100 w-md-auto me-md-2 mb-2 mb-md-0">
                <input type="text" class="form-control" placeholder="Cari..." v-model="search">
                <span class="input-icon-addon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
                        stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"></path>
                        <path d="M21 21l-6 -6"></path>
                    </svg>
                </span>
            </div>

            <!-- Tombol Refresh -->
            <BaseButton type="button" variant="secondary" outline class="me-md-2 mb-2 mb-md-0"
                @click="handleRefresh">
                 <i class="fa-solid fa-arrow-rotate-right me-2"></i>
                Refresh
            </BaseButton>

            <!-- Tombol Tambah Kuesioner -->
            <Link :href="route('questionnaires.create')">
                <BaseButton type="button" variant="primary" full>
                    <i class="fa-solid fa-plus me-2"></i>
                    Tambah Kuesioner
                </BaseButton>
            </Link>
        </div>
    </div>
</template>
