<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DataTable from '@/Components/DataTable.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseTooltip from '@/Components/BaseTooltip.vue';

const props = defineProps({
    questionnaires: Object,
});

const columns = ref([
    { label: '#', key: 'row_number', class: 'w-1' },
    { label: 'Nama Kuesioner', key: 'name', class: '' },
    { label: 'Periode Akademik', key: 'academic_period.name', class: '' },
    { label: 'Status', key: 'status', class: '' },
    { label: 'Tanggal Akhir', key: 'end_date', class: '' }, // Menggunakan end_date karena kamu sudah punya `dueDate` di backend
]);

const tableData = computed(() => {
    return {
        data: props.questionnaires,
        links: [], // Karena kamu tidak menggunakan pagination, links dikosongkan
        total: props.questionnaires.length,
    };
});
</script>

<template>

    <Head title="Daftar Kuesioner" />

    <AuthenticatedLayout>
        <template #header>
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        Daftar Kuesioner
                    </div>
                    <h2 class="page-title">
                        Daftar Kuesioner yang Perlu Diisi
                    </h2>
                </div>
            </div>
        </template>

        <div class="card">
            <DataTable :data="tableData" :columns="columns">
                <template #cell(row_number)="{ index }">
                    {{ index + 1 }}
                </template>

                <template #cell(status)="{ item }">
                    <span v-if="item.status === 'Diisi'" class="badge bg-green-lt fs-6">
                        <i class="fa-solid fa-check me-1"></i> Diisi
                    </span>
                    <span v-else class="badge bg-yellow-lt fs-6">
                        <i class="fa-solid fa-hourglass-start me-1"></i> Belum Diisi
                    </span>
                </template>

                <template #cell(end_date)="{ item }">
                    {{ item.formatted_end_date }}
                </template>

                <template #cell(actions)="{ item }">
                    <div class="btn-list flex-nowrap">
                        <BaseTooltip v-if="item.status === 'Belum Diisi'" title="Isi Kuesioner" data-bs-toggle="tooltip"
                            data-bs-placement="top">
                            <Link :href="route('answers.show', item.id)">
                            <BaseButton variant="primary" class="btn-icon" outline>
                                <i class="fa-solid fa-pen-to-square"></i>
                            </BaseButton>
                            </Link>
                        </BaseTooltip>
                        <BaseTooltip v-else title="Lihat Jawaban" data-bs-toggle="tooltip" data-bs-placement="top">
                            <Link :href="route('answers.submitted', item.id)">
                            <BaseButton variant="info" class="btn-icon" outline>
                                <i class="fa-solid fa-eye"></i>
                            </BaseButton>
                            </Link>
                        </BaseTooltip>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>