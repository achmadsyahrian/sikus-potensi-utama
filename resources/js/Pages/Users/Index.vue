<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { watch, ref } from 'vue';
import { debounce } from 'lodash';
import DataTable from '@/Components/DataTable.vue';
import UserTableControls from './Partials/UserTableControls.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseTooltip from '@/Components/BaseTooltip.vue';

const props = defineProps({
    users: Object,
    roles: Object,
    filters: Object,
});

const page = usePage();
const appName = page.props.app_name;

const search = ref(props.filters.search);
const selectedRole = ref(props.filters.role || 'all');
const userToDelete = ref(null);
const userToReset = ref(null);
const confirmDeleteModal = ref(null);
const confirmResetModal = ref(null);

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
    { label: '#', key: 'row_number', class: 'w-1', dataClass: 'text-muted fs-5' },
    { label: 'Nama', key: 'name', class: '', dataClass: '' },
    { label: 'Email', key: 'email', class: '', dataClass: 'text-muted' },
    { label: 'Autentikasi', key: 'auth_provider', class: '', dataClass: 'text-muted' },
    { label: 'Role', key: 'roles', class: '', dataClass: 'text-muted' },
]);

// Fungsi ini hanya untuk menyimpan item yang akan dihapus, modal dipicu oleh data-bs-toggle
const showConfirmDeletionModal = (user) => {
    userToDelete.value = user;
};

// Fungsi ini hanya untuk menyimpan item yang akan direset, modal dipicu oleh data-bs-toggle
const showPasswordResetModal = (user) => {
    userToReset.value = user;
};

const deleteUser = () => {
    if (userToDelete.value) {
        router.delete(route('users.destroy', userToDelete.value.id), {
            preserveScroll: true
        });
    }
};

const resetPassword = () => {
    if (userToReset.value) {
        router.post(route('users.reset-password', userToReset.value.id), {}, {
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
                        Daftar Pengguna
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
                <template #cell(row_number)="{ index }">
                    {{ users.from + index }}
                </template>
                
                <template #cell(auth_provider)="{ item }">
                    <span v-if="item.auth_provider === 'sevima'" class="badge bg-blue-lt fs-6 text-capitalize">
                        {{ item.auth_provider }}
                    </span>
                    <span v-else class="badge bg-purple-lt fs-6 text-capitalize">
                        {{ item.auth_provider }}
                    </span>
                </template>
                
                <template #cell(roles)="{ item }">
                    <span v-for="(role, index) in item.roles" :key="role.id">
                        {{ role.name }}
                        <span v-if="index < item.roles.length - 1">, </span>
                    </span>
                </template>
                
                <template #cell(actions)="{ item }">
                    <div class="btn-list flex-nowrap">
                        <BaseTooltip title="Ubah Data" data-bs-toggle="tooltip" data-bs-placement="top">
                            <Link :href="route('users.edit', item.id)">
                                <BaseButton variant="info" class="btn-icon" outline>
                                    <i class="fa-solid fa-pencil-alt"></i>
                                </BaseButton>
                            </Link>
                        </BaseTooltip>
                        <BaseTooltip v-if="item.auth_provider !== 'sevima'" title="Reset Kata Sandi" data-bs-toggle="tooltip" data-bs-placement="top">
                            <BaseButton 
                                variant="warning" 
                                class="btn-icon" 
                                outline 
                                data-bs-toggle="modal" 
                                data-bs-target="#confirmResetModal"
                                @click.prevent="showPasswordResetModal(item)"
                            >
                                <i class="fa-solid fa-key"></i>
                            </BaseButton>
                        </BaseTooltip>
                        <BaseTooltip title="Hapus Pengguna" data-bs-toggle="tooltip" data-bs-placement="top">
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
    
    <ConfirmModal 
        id="confirmDeleteModal" 
        title="Hapus Pengguna"
        :message="`Apakah Anda yakin ingin menghapus pengguna '${userToDelete?.name}'? Tindakan ini tidak dapat dibatalkan.`"
        confirmText="Ya, Hapus"
        @confirm="deleteUser" 
    />
    
    <ConfirmModal 
        id="confirmResetModal"
        title="Reset Kata Sandi"
        :message="`Apakah Anda yakin ingin mereset kata sandi pengguna '${userToReset?.name}'? Kata sandi akan diatur ulang menjadi 12345678.`"
        confirmText="Ya, Reset"
        confirmVariant="warning"
        @confirm="resetPassword"
    />
</template>
