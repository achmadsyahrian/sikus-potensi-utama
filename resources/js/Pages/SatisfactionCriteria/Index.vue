<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import DataTable from '@/Components/DataTable.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseTooltip from '@/Components/BaseTooltip.vue';
import SatisfactionCriteriaTableControls from './Partials/SatisfactionCriteriaTableControls.vue';

const props = defineProps({
    criteria: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const itemToDelete = ref(null);

const handleSearch = debounce(() => {
    router.get(
        route('satisfaction-criteria.index'),
        { search: search.value },
        { preserveState: true, replace: true }
    );
}, 300);

const handleRefresh = () => {
    search.value = '';
    router.get(route('satisfaction-criteria.index'), {}, { preserveState: true, replace: true });
};

const showConfirmDeletionModal = (item) => {
    itemToDelete.value = item;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        router.delete(route('satisfaction-criteria.destroy', itemToDelete.value.id), {
            preserveScroll: true,
        });
    }
};

const columns = ref([
    { label: '#', key: 'row_number', class: 'w-1', dataClass: 'text-muted' },
    { label: 'Label', key: 'label' },
    { label: 'Rentang Nilai', key: 'range' },
    { label: 'Warna', key: 'color' },
]);
</script>

<template>
    <Head title="Kriteria Kepuasan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">Kriteria Kepuasan</h2>
                </div>
            </div>
        </template>

        <SatisfactionCriteriaTableControls
            :search="search"
            :handleSearch="handleSearch"
            :handleRefresh="handleRefresh"
            @update:search="search = $event"
        />

        <div class="card">
            <DataTable :data="criteria" :columns="columns">
                <template #cell(row_number)="{ index }">
                    {{ criteria.from + index }}
                </template>

                <template #cell(range)="{ item }">
                    {{ item.min_value }}% - {{ item.max_value }}%
                </template>

                <template #cell(color)="{ item }">
                    <span
                        class="badge text-white"
                        :style="{ backgroundColor: item.color || '#6c757d' }"
                    >
                        {{ item.color || 'Default' }}
                    </span>
                </template>

                <template #cell(actions)="{ item }">
                    <div class="btn-list flex-nowrap">
                        <BaseTooltip title="Ubah Data" data-bs-toggle="tooltip">
                            <Link :href="route('satisfaction-criteria.edit', item.id)">
                                <BaseButton variant="info" class="btn-icon" outline>
                                    <i class="fa-solid fa-pencil-alt"></i>
                                </BaseButton>
                            </Link>
                        </BaseTooltip>
                        <BaseTooltip title="Hapus Data" data-bs-toggle="tooltip">
                            <BaseButton
                                variant="danger"
                                class="btn-icon"
                                outline
                                data-bs-toggle="modal"
                                data-bs-target="#confirmDeleteModal"
                                @click="showConfirmDeletionModal(item)"
                            >
                                <i class="fa-solid fa-trash"></i>
                            </BaseButton>
                        </BaseTooltip>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>

    <ConfirmModal
        id="confirmDeleteModal"
        title="Hapus Kriteria"
        :message="`Apakah Anda yakin ingin menghapus kriteria '${itemToDelete?.label}'?`"
        confirmText="Ya, Hapus"
        @confirm="deleteItem"
    />
</template>
