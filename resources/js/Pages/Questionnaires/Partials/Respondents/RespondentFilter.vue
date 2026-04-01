<script setup>
import BaseSelect from '@/Components/BaseSelect.vue';

const props = defineProps({
    searchQuery: String,
    selectedRole: String,
    selectedProdi: String,
    roles: Array,
    programStudies: Array
});

const emit = defineEmits(['update:searchQuery', 'update:selectedRole', 'update:selectedProdi']);
</script>

<template>
    <div class="card card-md mb-4 shadow-sm border-0">
        <div class="card-status-top bg-primary"></div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold text-muted small text-uppercase">Cari Responden</label>
                    <div class="input-icon w-100 w-md-auto me-md-2 mb-2 mb-md-0">
                        <input
                            type="text"
                            :value="searchQuery"
                            @input="emit('update:searchQuery', $event.target.value)"
                            class="form-control"
                            placeholder="Ketik lalu Enter (Nama, NIM...)"
                            @keyup.enter="emit('update:searchQuery', $event.target.value)"
                        >
                        <span class="input-icon-addon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"></path>
                                <path d="M21 21l-6 -6"></path>
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold text-muted small text-uppercase">Peran</label>
                    <select class="form-select" :value="selectedRole" @change="emit('update:selectedRole', $event.target.value)">
                        <option value="all">Semua Peran</option>
                        <optgroup label="Internal">
                            <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.name }}</option>
                        </optgroup>
                        <optgroup label="Eksternal">
                            <option value="alumni">Alumni</option>
                            <option value="mitra">Mitra Kerjasama</option>
                            <option value="pengguna_lulusan">Pengguna Lulusan</option>
                        </optgroup>
                    </select>
                </div>

                <div class="col-md-5" v-if="selectedRole === 'Mahasiswa' || selectedRole === 'all'">
                    <BaseSelect
                        label="Program Studi"
                        :modelValue="selectedProdi"
                        @update:modelValue="emit('update:selectedProdi', $event)"
                        :options="[
                            {id: 'all', name: 'Semua Program Studi'},
                            ...programStudies.map(p => ({
                                id: p.program_study_code,
                                name: `${p.degree_level} - ${p.name}`
                            }))
                        ]"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.card-status-top { height: 3px; }
</style>
