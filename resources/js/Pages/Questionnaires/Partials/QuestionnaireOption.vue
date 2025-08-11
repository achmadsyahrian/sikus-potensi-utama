<script setup>
import { defineProps } from 'vue';
import QuestionnaireInfoCard from './QuestionnaireInfoCard.vue';
import QuestionnaireOptionTable from './QuestionnaireOptionTable.vue';

const props = defineProps({
    questionnaire: Object,
    questionOptions: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div class="card-body">

        <QuestionnaireInfoCard :questionnaire="questionnaire" />
        <div class="d-flex align-items-center justify-content-between pt-2 pb-4">
            <div>
                <h3 class="fw-bold mb-1">Manajemen Opsi</h3>
                <h6 class="op-7 mb-2">Kelola opsi jawaban yang dapat digunakan kembali untuk pertanyaan kuesioner.</h6>
            </div>
        </div>
        <QuestionnaireOptionTable
            :questionnaire="questionnaire"
            :questionOptions="questionOptions"
        />
    </div>
</template>
