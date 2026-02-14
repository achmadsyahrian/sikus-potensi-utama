<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import SatisfactionCriteriaForm from './Partials/SatisfactionCriteriaForm.vue';

const form = useForm({
    label: '',
    min_value: 0,
    max_value: 100,
    color: '#206bc4', // Default blue-ish
});

const submit = () => {
    form.post(route('satisfaction-criteria.store'));
};
</script>

<template>
    <Head title="Tambah Kriteria" />

    <AuthenticatedLayout>
        <template #header>
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        Data Master
                    </div>
                    <h2 class="page-title">
                        Tambah Kriteria Baru
                    </h2>
                </div>
            </div>
        </template>

        <div class="card">
            <div class="row g-0">
                <div class="col d-flex flex-column">
                    <SatisfactionCriteriaForm :form="form" @submit="submit" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
