<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import QuestionnaireForm from './Partials/QuestionnaireForm.vue';

const props = defineProps({
    roles: {
        type: Array,
        required: true,
    },
    academicPeriods: {
        type: Array,
        required: true,
    },
    faculties: {
        type: Array,
        required: true,
    },
    programStudies: {
        type: Array,
        required: true,
    }
});

const form = useForm({
    name: '',
    description: '',
    academic_period_id: null,
    is_active: true,
    start_date: '',
    end_date: '',
    targets: [],
});

const submit = () => {
    form.post(route('questionnaires.store'));
};
</script>

<template>

    <Head title="Tambah Kuesioner" />

    <AuthenticatedLayout>
        <template #header>
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        Daftar Kuesioner
                    </div>
                    <h2 class="page-title">
                        Tambah Kuesioner Baru
                    </h2>
                </div>
            </div>
        </template>

        <div class="card">
            <div class="row g-0">
                <div class="col d-flex flex-column">
                    <QuestionnaireForm :form="form" :roles="props.roles" :academicPeriods="props.academicPeriods"
                        :faculties="props.faculties" :isCreate="true" :programStudies="props.programStudies" @submit="submit" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>