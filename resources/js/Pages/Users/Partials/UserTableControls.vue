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
            <BaseButton type="button" variant="secondary" outline class="me-md-2 mb-2 mb-md-0" @click="handleRefresh">
                <i class="fa-solid fa-arrow-rotate-right me-2"></i>
                Refresh
            </BaseButton>
            
            <!-- Tombol Tambah Pengguna -->
            <Link :href="route('users.create')">
                <BaseButton type="button" variant="primary" full>
                    <i class="fa-solid fa-plus me-2"></i>
                    Tambah Pengguna
                </BaseButton>
            </Link>
        </div>
    </div>
</template>