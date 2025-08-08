<script setup>
import { router } from '@inertiajs/vue3';
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
        route('faculties.index'),
        { search: search.value },
        { preserveState: true, replace: true }
    );
}, 300);

const handleRefresh = () => {
    search.value = '';
    router.get(route('faculties.index'), {}, { preserveState: true, replace: true });
};

watch(search, () => {
    handleSearch();
}, { immediate: false });

const handleSync = () => {
    isSyncing.value = true;
    router.post(route('faculties.sync'), {}, {
        onSuccess: () => {
            isSyncing.value = false;
        },
        onError: () => {
            isSyncing.value = false;
        }
    });
};
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
            <BaseButton type="button" variant="secondary" outline label="Refresh" class="me-md-2 mb-2 mb-md-0"
                @click="handleRefresh">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
                    stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"></path>
                    <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"></path>
                </svg>
            </BaseButton>

            <!-- Tombol Sinkronisasi -->
            <BaseButton
                type="button"
                label="Sinkronisasi Data"
                variant="primary"
                :disabled="isSyncing"
                data-bs-toggle="modal"
                data-bs-target="#confirmModal"
            >
                <i class="fa-solid fa-sync me-2"></i>
                <span v-if="isSyncing">Sinkronisasi...</span>
                <span v-else>Sinkronisasi Data</span>
            </BaseButton>
        </div>
    </div>
    <ConfirmModal
        id="confirmModal"
        title="Sinkronisasi Data"
        message="Apakah Anda yakin ingin melakukan sinkronisasi data fakultas? Ini akan memperbarui data dari SIAKAD."
        confirmText="Ya, Sinkronisasi"
        confirmVariant="primary"
        @confirm="handleSync"
    />
</template>
