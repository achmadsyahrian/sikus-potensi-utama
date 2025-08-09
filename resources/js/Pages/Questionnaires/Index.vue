<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, Head, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DataTable from '@/Components/DataTable.vue';
import QuestionnaireTableControls from './Partials/QuestionnaireTableControls.vue';
import BaseAlert from '@/Components/BaseAlert.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseTooltip from '@/Components/BaseTooltip.vue';

const props = defineProps({
    questionnaires: Object,
    filters: Object,
});

const page = usePage();
const appName = page.props.app_name;

const columns = ref([
    { label: '#', key: 'row_number', class: 'w-1', dataClass: 'text-muted fs-5' },
    { label: 'Nama', key: 'name', class: '', dataClass: 'text-muted' },
    { label: 'Periode Akademik', key: 'academic_period.name', class: '', dataClass: 'text-muted' },
    { label: 'Tanggal Mulai', key: 'formatted_start_date', class: '', dataClass: 'text-muted' },
    { label: 'Tanggal Akhir', key: 'formatted_end_date', class: '', dataClass: 'text-muted' },
    { label: 'Status Aktif', key: 'is_active', class: '', dataClass: '' },
]);
</script>

<template>
    <Head :title="`Kuesioner - ${appName}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        Manajemen Kuesioner
                    </div>
                    <h2 class="page-title">
                        Daftar Kuesioner
                    </h2>
                </div>
            </div>
        </template>

        <QuestionnaireTableControls :filters="props.filters" />

        <div class="card">
            <DataTable :data="questionnaires" :columns="columns">
                <template #cell(row_number)="{ index }">
                    {{ questionnaires.from + index }}
                </template>

                <template #cell(is_active)="{ item }">
                    <span v-if="item.is_active" class="badge bg-green-lt fs-6">Aktif</span>
                    <span v-else class="badge bg-red-lt fs-6">Non-aktif</span>
                </template>

                <template #cell(actions)="{ item }">
                    <div class="btn-list flex-nowrap">
                        <BaseTooltip title="Lihat Detail" data-bs-toggle="tooltip" data-bs-placement="top">
                            <Link :href="route('questionnaires.show', item)">
                                <BaseButton variant="info" class="btn-icon" outline>
                                    <i class="fa-solid fa-eye"></i>
                                </BaseButton>
                            </Link>
                        </BaseTooltip>
                        <BaseTooltip title="Hapus Kuesioner" data-bs-toggle="tooltip" data-bs-placement="top">
                            <BaseButton 
                                variant="danger" 
                                class="btn-icon" 
                                outline 
                                data-bs-toggle="modal" 
                                data-bs-target="#confirmDeleteModal" 
                                @click.prevent="showConfirmDeletionModal(item)"
                            >
                                <i class="fa-solid fa-trash"></i>
                            </BaseButton>
                        </BaseTooltip>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>
