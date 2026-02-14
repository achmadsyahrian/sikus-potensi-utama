<script setup>
import BaseInput from '@/Components/BaseInput.vue';
import BaseButton from '@/Components/BaseButton.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    form: Object,
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
            <h3 class="card-title text-primary">Informasi Kriteria</h3>
            <p class="card-subtitle mb-4">Tentukan label dan rentang nilai persentase.</p>

            <div class="row g-3">
                <div class="col-md-12">
                    <BaseInput
                        label="Label Kriteria"
                        placeholder="Contoh: Sangat Baik"
                        type="text"
                        v-model="form.label"
                        :error="form.errors.label"
                        :disabled="disabled"
                        required
                    />
                </div>

                <div class="col-md-6">
                    <BaseInput
                        label="Nilai Minimum (%)"
                        type="number"
                        step="0.1"
                        v-model="form.min_value"
                        :error="form.errors.min_value"
                        :disabled="disabled"
                        required
                    />
                </div>
                <div class="col-md-6">
                    <BaseInput
                        label="Nilai Maksimum (%)"
                        type="number"
                        step="0.1"
                        v-model="form.max_value"
                        :error="form.errors.max_value"
                        :disabled="disabled"
                        required
                    />
                </div>

                <div class="col-md-12">
                    <label class="form-label">Warna Indikator</label>
                    <input
                        type="color"
                        class="form-control form-control-color w-100"
                        v-model="form.color"
                        title="Pilih warna representasi"
                        :disabled="disabled"
                    >
                    <div v-if="form.errors.color" class="invalid-feedback d-block">
                        {{ form.errors.color }}
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer bg-transparent mt-auto text-end">
            <Link :href="route('satisfaction-criteria.index')" class="me-2">
                <BaseButton type="button" label="Kembali" variant="secondary" outline />
            </Link>
            <BaseButton
                type="submit"
                label="Simpan Data"
                variant="primary"
                :disabled="form.processing"
            />
        </div>
    </form>
</template>
