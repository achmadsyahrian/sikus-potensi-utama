<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DataTable from '@/Components/DataTable.vue';
import ProgramStudyTableControls from './Partials/ProgramStudyTableControls.vue';
import BaseAlert from '@/Components/BaseAlert.vue';

const props = defineProps({
    programStudies: Object,
    filters: Object,
});

const page = usePage();
const appName = page.props.app_name;

const lastSyncedAt = computed(() => {
    if (props.programStudies && props.programStudies.data && props.programStudies.data.length > 0) {
        return props.programStudies.data[0].formatted_last_synced_at;
    }
    return null;
});

const columns = ref([
    { label: '#', key: 'row_number', class: 'w-1', dataClass: 'text-muted fs-5' },
    { label: 'Kode Program Studi', key: 'program_study_code', class: '', dataClass: '' },
    { label: 'Nama', key: 'name', class: '', dataClass: 'text-muted' },
    { label: 'Kode Fakultas', key: 'faculty_code', class: '', dataClass: 'text-muted' },
    { label: 'Jenjang', key: 'degree_level', class: '', dataClass: 'text-muted' },
    { label: 'Status Aktif', key: 'is_active', class: '', dataClass: '' },
]);
</script>

<template>
    <Head :title="`Program Studi - ${appName}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        Data Master
                    </div>
                    <h2 class="page-title">
                        Manajemen Program Studi
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

        <ProgramStudyTableControls :filters="props.filters" />

        <div class="card">
            <DataTable :data="programStudies" :columns="columns">
                <template #cell(row_number)="{ index }">
                    {{ programStudies.from + index }}
                </template>
                <template #cell(is_active)="{ item }">
                    <span v-if="item.is_active" class="badge bg-green-lt fs-6">Aktif</span>
                    <span v-else class="badge bg-red-lt fs-6">Non-aktif</span>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>
