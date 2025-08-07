<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { watch, ref } from 'vue';
import { debounce } from 'lodash';
import DataTable from '@/Components/DataTable.vue';
import UserTableControls from './Partials/UserTableControls.vue';

const props = defineProps({
    users: Object,
    roles: Object,
    filters: Object,
});

const page = usePage();
const appName = page.props.app_name;

const search = ref(props.filters.search);
const selectedRole = ref(props.filters.role || 'all');

const handleSearch = debounce(() => {
    router.get(
        route('users.index'),
        { search: search.value, role: selectedRole.value },
        { preserveState: true, replace: true }
    );
}, 300);

const handleRefresh = () => {
    search.value = '';
    selectedRole.value = 'all';
    router.get(route('users.index'), {}, { preserveState: true, replace: true });
};

watch(selectedRole, () => {
    handleSearch();
});

const columns = ref([
    { label: 'Nama', key: 'name', class: '', dataClass: '' },
    { label: 'Email', key: 'email', class: '', dataClass: 'text-muted' },
    { label: 'Role', key: 'roles', class: '', dataClass: 'text-muted' },
]);

const confirmUserDeletion = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus pengguna ini?')) {
        router.delete(route('users.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head :title="`Pengguna - ${appName}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        Manajemen Pengguna
                    </div>
                    <h2 class="page-title">
                        Daftar Pengguna SIKUS
                    </h2>
                </div>
            </div>
        </template>

        <UserTableControls
            :roles="props.roles"
            :search="search"
            :selectedRole="selectedRole"
            :handleSearch="handleSearch"
            :handleRefresh="handleRefresh"
            @update:search="search = $event"
            @update:selectedRole="selectedRole = $event"
        />

        <div class="card">
            <DataTable :data="users" :columns="columns">
                <template #cell(roles)="{ item }">
                    <span v-for="(role, index) in item.roles" :key="role.id">
                        {{ role.name }}
                        <span v-if="index < item.roles.length - 1">, </span>
                    </span>
                </template>
                
                <template #cell(actions)="{ item }">
                    <div class="btn-list flex-nowrap">
                        <div title="Ubah Data" data-bs-toggle="tooltip" data-bs-placement="top">
                            <Link :href="route('users.edit', item.id)" class="btn btn-icon btn-outline-info">
                                <i class="fa-solid fa-pencil-alt"></i>
                            </Link>
                        </div>
                        <div title="Hapus Pengguna" data-bs-toggle="tooltip" data-bs-placement="top">
                            <Link :href="route('users.destroy', item.id)" method="delete" as="button" @click.prevent="confirmUserDeletion(item.id)" class="btn btn-icon btn-outline-danger">
                                <i class="fa-solid fa-trash"></i>
                            </Link>
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>