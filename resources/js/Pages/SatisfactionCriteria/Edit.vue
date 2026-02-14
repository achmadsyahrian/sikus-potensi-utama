<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import SatisfactionCriteriaForm from './Partials/SatisfactionCriteriaForm.vue';

const props = defineProps({
    criterion: {
        type: Object,
        required: true,
    }
});

const form = useForm({
    label: props.criterion.label,
    min_value: props.criterion.min_value,
    max_value: props.criterion.max_value,
    color: props.criterion.color,
});

const submit = () => {
    form.put(route('satisfaction-criteria.update', props.criterion.id));
};
</script>

<template>
    <Head title="Ubah Kriteria" />

    <AuthenticatedLayout>
        <template #header>
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        Data Master
                    </div>
                    <h2 class="page-title">
                        Ubah Kriteria Kepuasan
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
