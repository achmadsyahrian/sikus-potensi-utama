<script setup>
import BaseInput from '@/Components/BaseInput.vue';
import BaseSelect from '@/Components/BaseSelect.vue';
import { computed } from 'vue';
import { formatIndonesianDate } from '@/Utilities/dateFormatter.js';
import BaseAlert from '@/Components/BaseAlert.vue'; // BARU: Impor BaseAlert

const props = defineProps({
    form: Object,
    questionnaire: Object, // BARU: Tambahkan prop questionnaire
    academicPeriods: Array,
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

const academicPeriodName = computed(() => {
    const period = props.academicPeriods.find(p => p.id === props.form.academic_period_id);
    return period ? period.name : 'Tidak ditemukan';
});

// BARU: Computed property untuk mengecek apakah kuesioner siap diaktifkan
const canBeActivated = computed(() => {
    if (!props.questionnaire) {
        return false;
    }
    return props.questionnaire.questions.length > 0 && props.questionnaire.options.length > 0;
});

// BARU: Computed property untuk pesan peringatan
const activationWarning = computed(() => {
    if (!props.questionnaire) {
        return 'Data kuesioner belum lengkap.';
    }
    if (props.questionnaire.questions.length === 0 && props.questionnaire.options.length === 0) {
        return 'Kuesioner harus memiliki setidaknya satu pertanyaan dan satu opsi jawaban untuk dapat diaktifkan!';
    } else if (props.questionnaire.questions.length === 0) {
        return 'Kuesioner harus memiliki setidaknya satu pertanyaan untuk dapat diaktifkan!';
    } else if (props.questionnaire.options.length === 0) {
        return 'Kuesioner harus memiliki setidaknya satu opsi jawaban untuk dapat diaktifkan!';
    }
    return null;
});
</script>

<template>
    <div class="card-body">
        <div class="card-title text-primary">Informasi Dasar</div>
        <p class="card-subtitle mb-4">Informasi dasar kuesioner seperti nama, deskripsi, dan waktu aktif.</p>
        <div class="row g-3">
            <div class="col-md-6">
                <template v-if="isEditing || isCreate">
                    <BaseInput label="Nama Kuesioner" type="text" v-model="form.name" :error="form.errors.name" :disabled="isDisabled" required />
                </template>
                <template v-else>
                    <label class="form-label mb-0">Nama Kuesioner</label>
                    <p class="form-control-plaintext">{{ form.name }}</p>
                </template>
            </div>
            <div class="col-md-6">
                <template v-if="isEditing || isCreate">
                    <BaseSelect label="Periode Akademik" v-model="form.academic_period_id" :options="academicPeriods" :error="form.errors.academic_period_id" :disabled="isDisabled" required />
                </template>
                <template v-else>
                    <label class="form-label mb-0">Periode Akademik</label>
                    <p class="form-control-plaintext">{{ academicPeriodName }}</p>
                </template>
            </div>
            <div class="col-md-12">
                <template v-if="isEditing || isCreate">
                    <BaseInput label="Deskripsi" type="textarea" v-model="form.description" :error="form.errors.description" :disabled="isDisabled" />
                </template>
                <template v-else>
                    <label class="form-label mb-0">Deskripsi</label>
                    <p class="form-control-plaintext">{{ form.description || '-' }}</p>
                </template>
            </div>
            <div class="col-md-6">
                <template v-if="isEditing || isCreate">
                    <BaseInput label="Tanggal Mulai" type="date" v-model="formattedStartDate" :error="form.errors.start_date" :disabled="isDisabled" required />
                </template>
                <template v-else>
                    <label class="form-label mb-0">Tanggal Mulai</label>
                    <p class="form-control-plaintext">{{ formatIndonesianDate(formattedStartDate) }}</p>
                </template>
            </div>
            <div class="col-md-6">
                <template v-if="isEditing || isCreate">
                    <BaseInput label="Tanggal Selesai" type="date" v-model="formattedEndDate" :error="form.errors.end_date" :disabled="isDisabled" required />
                </template>
                <template v-else>
                    <label class="form-label mb-0">Tanggal Selesai</label>
                    <p class="form-control-plaintext">{{ formatIndonesianDate(formattedEndDate) }}</p>
                </template>
            </div>
            <div class="col-12 mt-4">
                <h5 class="card-title text-primary" v-if="!isCreate">Status Kuesioner</h5>
                <template v-if="isEditing">
                    <!-- <BaseAlert v-if="!canBeActivated && !form.is_active" type="warning" :message="activationWarning" class="mb-3" /> -->
                    <p v-if="!canBeActivated && !form.is_active" class="text-danger fs-5">{{ activationWarning }}</p>

                    <div class="form-check form-switch d-inline-block">
                        <input 
                            type="checkbox" 
                            v-model="isActiveComputed" 
                            class="form-check-input" 
                            id="is-active-check" 
                            :disabled="isDisabled || (!canBeActivated && !isActiveComputed)"
                        >
                        <label class="form-check-label" for="is-active-check">Aktif</label>
                    </div>
                </template>
                <template v-else-if="!isEditing && !isCreate">
                    <span :class="{'badge bg-success': isActiveComputed, 'badge bg-secondary': !isActiveComputed}">
                        {{ isActiveComputed ? 'Aktif' : 'Tidak Aktif' }}
                    </span>
                </template>
            </div>
        </div>
    </div>
</template>