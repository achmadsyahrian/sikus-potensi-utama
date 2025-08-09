<script setup>
import BaseInput from '@/Components/BaseInput.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseSelect from '@/Components/BaseSelect.vue';
import QuestionnaireTargetForm from './QuestionnaireTargetForm.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    form: Object,
    roles: Array,
    academicPeriods: Array,
    faculties: Array,
    programStudies: Array,
    isDisabled: {
        type: Boolean,
        default: false,
    },
    isEditing: {
        type: Boolean,
        default: false,
    },
    isCreate: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits(['submit', 'editToggle']);

const formattedStartDate = computed({
    get() {
        return props.form.start_date ? props.form.start_date.substring(0, 10) : null;
    },
    set(value) {
        props.form.start_date = value;
    }
});

const formattedEndDate = computed({
    get() {
        return props.form.end_date ? props.form.end_date.substring(0, 10) : null;
    },
    set(value) {
        props.form.end_date = value;
    }
});

const isActiveComputed = computed({
    get() {
        return props.form.is_active === 1;
    },
    set(value) {
        props.form.is_active = value ? 1 : 0;
    }
});
</script>

<template>
    <form @submit.prevent="$emit('submit')">
        <div class="card-body">
            <!-- Bagian 1: Informasi Dasar -->
            <div class="card-title text-primary">Informasi Dasar</div>
            <p class="card-subtitle mb-4">Informasi dasar kuesioner seperti nama, deskripsi, dan waktu aktif.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <BaseInput label="Nama Kuesioner" type="text" v-model="form.name" :error="form.errors.name"
                        :disabled="isDisabled" required />
                </div>
                <div class="col-md-6">
                    <BaseSelect label="Periode Akademik" v-model="form.academic_period_id" :options="academicPeriods"
                        :error="form.errors.academic_period_id" :disabled="isDisabled" required />
                </div>
                <div class="col-md-12">
                    <BaseInput label="Deskripsi" type="textarea" v-model="form.description"
                        :error="form.errors.description" :disabled="isDisabled" />
                </div>
                <div class="col-12 mt-4">
                    <h5 class="card-title text-primary">Status Kuesioner</h5>
                    <div class="form-check form-switch d-inline-block">
                        <input type="checkbox" v-model="isActiveComputed" class="form-check-input" id="is-active-check"
                            :disabled="isDisabled">
                        <label class="form-check-label" for="is-active-check">Aktif</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <BaseInput label="Tanggal Mulai" type="date" v-model="formattedStartDate"
                        :error="form.errors.start_date" :disabled="isDisabled" required />
                </div>
                <div class="col-md-6">
                    <BaseInput label="Tanggal Selesai" type="date" v-model="formattedEndDate"
                        :error="form.errors.end_date" :disabled="isDisabled" required />
                </div>
            </div>
        </div>
        <hr>

        <!-- Bagian 2: Penargetan Kuesioner -->
        <div class="card-body">
            <QuestionnaireTargetForm :form="form" :roles="roles" :faculties="faculties" :programStudies="programStudies"
                :is-disabled="isDisabled" />
        </div>

        <div class="card-footer bg-transparent mt-auto text-end">
            <Link :href="route('questionnaires.index')" class="me-2">
                <BaseButton type="button" label="Kembali" variant="secondary" outline />
            </Link>

            <!-- Tombol untuk halaman Create -->
            <template v-if="isCreate">
                <BaseButton type="submit" label="Simpan Kuesioner" variant="primary" :disabled="form.processing" />
            </template>
            <!-- Tombol untuk halaman Show -->
            <template v-else>
                <template v-if="isEditing">
                    <BaseButton type="button" label="Batal" variant="secondary" outline @click="emit('editToggle')" class="me-2" />
                    <BaseButton type="submit" label="Simpan Perubahan" variant="primary" :disabled="form.processing" />
                </template>
                <template v-else>
                    <BaseButton type="button" label="Edit Kuesioner" variant="primary" @click="emit('editToggle')" />
                </template>
            </template>
        </div>
    </form>
</template>
