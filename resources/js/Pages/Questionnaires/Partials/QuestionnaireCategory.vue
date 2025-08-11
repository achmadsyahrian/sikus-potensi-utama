<script setup>
import { defineProps, computed } from 'vue';
import QuestionnaireCategoryTable from './QuestionnaireCategoryTable.vue';
import { formatIndonesianDate } from '@/Utilities/dateFormatter.js';
import QuestionnaireInfoCard from './QuestionnaireInfoCard.vue';

const props = defineProps({
    questionnaire: Object,
    questionCategories: {
        type: Array,
        default: () => [],
    },
});

const academicPeriodName = computed(() => {
    const period = props.questionnaire.academic_period;
    return period ? period.name : 'Tidak ditemukan';
});
</script>

<template>
    <div class="card-body">

        <!-- Informasi Kuesioner di Bagian Atas -->
        <QuestionnaireInfoCard :questionnaire="questionnaire" />

        <!-- Header dan Deskripsi -->
        <div class="d-flex align-items-center justify-content-between pt-2 pb-4">
            <div>
                <h3 class="fw-bold mb-1">Manajemen Kategori</h3>
                <h5 class="op-7 mb-2 text-muted">Kelola urutan dan nama kategori kuesioner.</h5>
            </div>
        </div>

        <!-- Memuat komponen baru untuk tabel kategori -->
        <QuestionnaireCategoryTable :questionnaire="questionnaire" :questionCategories="questionCategories" />
    </div>
</template>
