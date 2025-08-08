<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import UserForm from './Partials/UserForm.vue';
import BaseAlert from '@/Components/BaseAlert.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    roles: {
        type: Array,
        required: true,
    }
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    roles: props.user.roles.map(role => role.id),
});

const submit = () => {
    form.put(route('users.update', props.user.id));
};

const isSevimaUser = props.user.auth_provider === 'sevima';
</script>

<template>

    <Head title="Ubah Pengguna" />

    <AuthenticatedLayout>
        <template #header>
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        Manajemen Pengguna
                    </div>
                    <h2 class="page-title">
                        Ubah Pengguna: {{ props.user.name }}
                    </h2>
                </div>
            </div>
        </template>

        <BaseAlert 
            v-if="isSevimaUser" 
            type="info" 
            title="Tidak Dapat Diubah"
            message="Pengguna ini disinkronisasi dari SIAKAD Sevima. Data nama dan email tidak dapat diubah secara manual." 
            class="mb-4"
        />

        <div class="card">
            <div class="row g-0">
                <div class="col d-flex flex-column">
                    <UserForm :form="form" :roles="props.roles" @submit="submit" :disabled="isSevimaUser" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
