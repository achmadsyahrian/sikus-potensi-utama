<script setup>
import BaseInput from '@/Components/BaseInput.vue';
import BaseButton from '@/Components/BaseButton.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    form: Object,
    roles: Array,
    disabled: {
        type: Boolean,
        default: false,
    }
});

defineEmits(['submit']);
</script>

<template>
    <form @submit.prevent="$emit('submit')">
        <div class="card-body">
            <h3 class="card-title">Informasi Dasar</h3>
            <p class="card-subtitle">Informasi dasar pengguna.</p>
            <div class="row g-3">
                <div class="col-md">
                    <BaseInput label="Nama Lengkap" type="text" v-model="form.name" :error="form.errors.name"
                        :disabled="disabled" />
                </div>
                <div class="col-md">
                    <BaseInput label="Alamat Email" type="email" v-model="form.email" :error="form.errors.email"
                        :disabled="disabled" />
                </div>
            </div>

            <h3 class="card-title mt-4">Manajemen Peran</h3>
            <p class="card-subtitle">Pilih peran yang akan diberikan kepada pengguna.</p>
            <div class="row g-3">
                <div class="col-md">
                    <div v-if="props.roles.length > 0" class="form-selectgroup">
                        <label v-for="role in props.roles" :key="role.id" class="form-selectgroup-item">
                            <input type="checkbox" :value="role.id" class="form-selectgroup-input" v-model="form.roles">
                            <span class="form-selectgroup-label d-flex align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check"
                                    width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M5 12l5 5l10 -10"></path>
                                </svg>
                                <span class="ms-2">{{ role.name }}</span>
                            </span>
                        </label>
                    </div>
                    <div v-else class="text-muted fst-italic fs-5">
                        Tidak ada peran yang dapat dikelola.
                    </div>
                    <div v-if="form.errors.roles" class="text-danger mt-1">
                        {{ form.errors.roles }}
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer bg-transparent mt-auto text-end">
            <Link :href="route('users.index')" class="me-2">
                <BaseButton type="button" label="Kembali" variant="secondary" outline />
            </Link>
            <BaseButton type="submit" label="Simpan Pengguna" variant="primary"
                :disabled="form.processing" />
        </div>
    </form>
</template>
