<script setup>
import { computed } from 'vue';
import { formatIndonesianDate } from '@/Utilities/dateFormatter.js';

const props = defineProps({
    questionnaire: Object,
});

const academicPeriodName = computed(() => {
    const period = props.questionnaire.academic_period;
    return period ? period.name : 'Tidak ditemukan';
});
</script>

<template>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-muted-lt">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-baseline mb-3">
                        <div>
                            <h4 class="card-title text-primary mb-0 me-2">{{ questionnaire.name }}</h4>
                        </div>
                        <div>
                            <span
                                :class="{ 'badge bg-success': questionnaire.is_active, 'badge bg-secondary': !questionnaire.is_active }">
                                {{ questionnaire.is_active ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </div>
                    </div>

                    <p class="card-subtitle text-muted fs-5">{{ questionnaire.description }}</p>

                    <div class="row g-2 mt-3">
                        <div class="col-md-4">
                            <label class="form-label mb-0">Periode Akademik</label>
                            <p class="form-control-plaintext">{{ academicPeriodName }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-0">Tanggal Mulai</label>
                            <p class="form-control-plaintext">{{ formatIndonesianDate(questionnaire.start_date) }}
                            </p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-0">Tanggal Selesai</label>
                            <p class="form-control-plaintext">{{ formatIndonesianDate(questionnaire.end_date) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
