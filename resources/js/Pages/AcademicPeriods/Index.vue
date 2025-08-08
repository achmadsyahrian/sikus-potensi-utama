<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DataTable from '@/Components/DataTable.vue';
import AcademicPeriodTableControls from './Partials/AcademicPeriodTableControls.vue';
import BaseAlert from '@/Components/BaseAlert.vue';

const props = defineProps({
    academicPeriods: Object,
    filters: Object,
});

const page = usePage();
const appName = page.props.app_name;

const lastSyncedAt = computed(() => {
    if (props.academicPeriods && props.academicPeriods.data && props.academicPeriods.data.length > 0) {
        return props.academicPeriods.data[0].formatted_last_synced_at;
    }
    return null;
});

const columns = ref([
    { label: '#', key: 'row_number', class: 'w-1', dataClass: 'text-muted fs-5' },
    { label: 'Nama Periode', key: 'name', class: '', dataClass: '' },
    { label: 'Tahun Ajaran', key: 'academic_year', class: '', dataClass: 'text-muted' },
    { label: 'Semester', key: 'semester', class: '', dataClass: '' },
    { label: 'Tanggal Mulai', key: 'formatted_start_date', class: '', dataClass: 'text-muted fs-5' },
    { label: 'Tanggal Selesai', key: 'formatted_end_date', class: '', dataClass: 'text-muted fs-5' },
    { label: 'Status Aktif', key: 'is_active', class: '', dataClass: '' },
]);
</script>

<template>
    <Head :title="`Periode Akademik - ${appName}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        Data Master
                    </div>
                    <h2 class="page-title">
                        Manajemen Periode Akademik
                    </h2>
                </div>
            </div>
        </template>
        
        <BaseAlert 
            v-if="lastSyncedAt" 
            type="info" 
            title="Data Tersinkronisasi"
            :message="`Data terakhir kali disinkronisasi pada ${lastSyncedAt}.`" 
            class="mb-4"
        />

        <AcademicPeriodTableControls :filters="props.filters" />

        <div class="card">
            <DataTable :data="academicPeriods" :columns="columns">
                <template #cell(row_number)="{ index }">
                    {{ academicPeriods.from + index }}
                </template>
                <template #cell(semester)="{ item }">
                    <span v-if="item.semester === 'Ganjil'" class="badge bg-purple-lt fs-6">{{ item.semester }}</span>
                    <span v-else-if="item.semester === 'Genap'" class="badge bg-green-lt fs-6">{{ item.semester }}</span>
                    <span v-else class="badge bg-yellow-lt fs-6">{{ item.semester }}</span>
                </template>
                <template #cell(is_active)="{ item }">
                    <span v-if="item.is_active" class="badge bg-green-lt fs-6">Aktif</span>
                    <span v-else class="badge bg-red-lt fs-6">Non-aktif</span>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>
