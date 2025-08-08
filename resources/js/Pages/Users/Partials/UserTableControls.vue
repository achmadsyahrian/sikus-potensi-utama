<script setup>
import BaseButton from '@/Components/BaseButton.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    roles: Object,
    search: String,
    selectedRole: String,
    handleSearch: Function,
    handleRefresh: Function,
});

const emit = defineEmits(['update:search', 'update:selectedRole']);
</script>

<template>
    <div class="card mb-3">
        <div class="card-body d-flex flex-column flex-md-row align-items-md-center">
            <!-- Filter by Role -->
            <select
                class="form-select w-md-auto me-md-2 mb-2 mb-md-0"
                :value="selectedRole"
                @change="emit('update:selectedRole', $event.target.value)"
            >
                <option value="all">Semua</option>
                <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
            </select>

            <!-- Search Input -->
            <div class="input-icon w-100 w-md-auto me-md-2 mb-2 mb-md-0">
                <input
                    type="text"
                    class="form-control"
                    placeholder="Cari..."
                    :value="search"
                    @keyup="handleSearch"
                    @input="emit('update:search', $event.target.value)"
                >
                <span class="input-icon-addon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"></path>
                        <path d="M21 21l-6 -6"></path>
                    </svg>
                </span>
            </div>
            
            <!-- Tombol Refresh -->
            <BaseButton type="button" variant="secondary" outline label="Refresh" class="me-md-2 mb-2 mb-md-0" @click="handleRefresh">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"></path>
                    <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"></path>
                </svg>
            </BaseButton>
            
            <!-- Tombol Tambah Pengguna -->
            <Link :href="route('users.create')">
                <BaseButton type="button" variant="primary" label="Tambah Pengguna" full>
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M12 5l0 14"></path>
                        <path d="M5 12l14 0"></path>
                    </svg>
                </BaseButton>
            </Link>
        </div>
    </div>
</template>