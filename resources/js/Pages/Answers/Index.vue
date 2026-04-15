<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DataTable from '@/Components/DataTable.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseTooltip from '@/Components/BaseTooltip.vue';

const props = defineProps({
    questionnaires: Object,
    isDosen: { type: Boolean, default: false },
});

const columns = ref([
    { label: '#', key: 'row_number', class: 'w-1' },
    { label: 'Nama Kuesioner', key: 'name', class: '' },
    { label: 'Periode Akademik', key: 'academic_period.name', class: '' },
    { label: 'Status', key: 'status', class: '' },
    { label: 'Tanggal Akhir', key: 'end_date', class: '' },
]);

const tableData = computed(() => ({
    data: props.questionnaires.data,
    links: props.questionnaires.links,
    total: props.questionnaires.meta?.total ?? props.questionnaires.data?.length ?? 0,
}));
</script>

<template>
    <Head title="Daftar Kuesioner" />

    <AuthenticatedLayout>
        <template #header>
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Daftar Kuesioner</div>
                    <h2 class="page-title">Daftar Kuesioner yang Perlu Diisi</h2>
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
                        <i class="fa-solid fa-check me-1"></i> Selesai
                    </span>
                    <span v-else-if="item.status === 'Sebagian Diisi'" class="badge bg-orange-lt text-orange fs-6">
                        <i class="fa-solid fa-circle-half-stroke me-1"></i> Sebagian Diisi
                    </span>
                    <span v-else class="badge bg-yellow-lt fs-6">
                        <i class="fa-solid fa-hourglass-start me-1"></i> Belum Diisi
                    </span>
                </template>

                <template #cell(end_date)="{ item }">
                    {{ item.formatted_end_date }}
                </template>

                <!-- Info prodi khusus dosen -->
                <template #cell(name)="{ item }">
                    <div class="fw-bold">{{ item.name }}</div>
                    <div v-if="isDosen && item.prodi_info" class="mt-1 d-flex flex-wrap gap-1">
                        <span
                            v-for="prodi in item.prodi_info.prodi_list"
                            :key="prodi.id_program_studi"
                            class="badge"
                            :class="prodi.is_filled ? 'bg-success-lt text-success' : 'bg-muted-lt text-muted'"
                        >
                            <i class="fa-solid me-1" :class="prodi.is_filled ? 'fa-check' : 'fa-clock'"></i>
                            {{ prodi.program_studi }}
                            <span v-if="prodi.degree_level" class="ms-1 opacity-75">({{ prodi.degree_level }})</span>
                        </span>
                    </div>
                </template>

                <template #cell(actions)="{ item }">
                    <div class="btn-list flex-nowrap">
                        <!-- Dosen: belum isi sama sekali atau sebagian -->
                        <template v-if="isDosen && item.prodi_info">
                            <template v-if="!item.prodi_info.all_filled">
                                <BaseTooltip title="Isi Kuesioner" data-bs-placement="top">
                                    <Link :href="route('answers.show', item.id)">
                                        <BaseButton variant="primary" class="btn-icon" outline>
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </BaseButton>
                                    </Link>
                                </BaseTooltip>
                            </template>
                            <template v-if="item.prodi_info.filled_count > 0">
                                <BaseTooltip title="Lihat Jawaban" data-bs-placement="top">
                                    <Link :href="route('answers.submitted', item.id)">
                                        <BaseButton variant="info" class="btn-icon" outline>
                                            <i class="fa-solid fa-eye"></i>
                                        </BaseButton>
                                    </Link>
                                </BaseTooltip>
                            </template>
                        </template>

                        <!-- Non-dosen: logic lama -->
                        <template v-else>
                            <BaseTooltip v-if="item.status === 'Belum Diisi'" title="Isi Kuesioner" data-bs-placement="top">
                                <Link :href="route('answers.show', item.id)">
                                    <BaseButton variant="primary" class="btn-icon" outline>
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </BaseButton>
                                </Link>
                            </BaseTooltip>
                            <BaseTooltip v-else title="Lihat Jawaban" data-bs-placement="top">
                                <Link :href="route('answers.submitted', item.id)">
                                    <BaseButton variant="info" class="btn-icon" outline>
                                        <i class="fa-solid fa-eye"></i>
                                    </BaseButton>
                                </Link>
                            </BaseTooltip>
                        </template>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>
