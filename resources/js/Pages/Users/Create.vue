<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import UserForm from './Partials/UserForm.vue';

const props = defineProps({
    roles: {
        type: Array,
        required: true,
    }
});

const form = useForm({
    name: '',
    email: '',
    roles: [],
});

const submit = () => {
    form.post(route('users.store'));
};
</script>

<template>

    <Head title="Tambah Pengguna" />

    <AuthenticatedLayout>
        <template #header>
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        Manajemen Pengguna
                    </div>
                    <h2 class="page-title">
                        Tambah Pengguna Baru
                    </h2>
                </div>
            </div>
        </template>

        <div class="card">
            <div class="row g-0">
                <div class="col d-flex flex-column">
                    <UserForm :form="form" :roles="props.roles" @submit="submit" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>