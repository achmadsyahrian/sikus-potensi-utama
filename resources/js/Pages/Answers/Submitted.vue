<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    questionnaire: Object,
    submittedAnswers: Array,
});

// Mengambil semua pertanyaan, baik dari kategori atau langsung dari kuesioner
const allQuestions = computed(() => {
    return props.questionnaire.questions;
});

// Fungsi untuk mendapatkan objek jawaban lengkap untuk sebuah pertanyaan
const getAnswerForQuestion = (questionId) => {
    return props.submittedAnswers.find(answer => answer.question_id === questionId);
};

// Fungsi untuk mendapatkan teks opsi dari nilai jawaban
const getOptionText = (question, answer) => {
    if (question.question_type !== 'multiple_choice' || !answer) {
        return answer?.answer_value || null;
    }
    const option = props.questionnaire.options.find(o => o.option_value == answer.answer_value);
    return option ? option.option_text : null;
};

// Fungsi untuk mendapatkan tanggal dari jawaban, jika ada
const getAnswerDate = (questionId) => {
    const answer = getAnswerForQuestion(questionId);
    if (answer && answer.created_at) {
        return new Date(answer.created_at).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    return null;
};
</script>
<template>

    <Head :title="`Jawaban Kuesioner: ${questionnaire.name}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        Jawaban Kuesioner
                    </div>
                    <h2 class="page-title">
                        {{ questionnaire.name }}
                    </h2>
                </div>
            </div>
        </template>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Jawaban Anda</h3>
                <div class="card-actions">
                    <Link :href="route('answers.index')">
                    <BaseButton variant="secondary" outline>
                        <i class="fa-solid fa-arrow-left me-2"></i> Kembali
                    </BaseButton>
                    </Link>
                </div>
            </div>
            <div class="card-body">
                <div v-for="(question, index) in allQuestions" :key="question.id" class="border-bottom py-3">
                    <div class="d-flex align-items-start">
                        <div class="me-3 d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width: 32px; height: 32px;">
                            <div class="badge bg-primary rounded-circle w-100 h-100 d-flex align-items-center justify-content-center"
                                style="font-size: 0.8rem;">
                                {{ index + 1 }}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold mb-1">{{ question.question_text }}</div>
                            <small class="text-muted d-block mb-2">
                                Tipe: {{ question.question_type === 'multiple_choice' ?
                                        'Pilihan Ganda' : 'Teks Bebas' }}
                                <span v-if="getAnswerDate(question.id)">• Dijawab pada {{ getAnswerDate(question.id)
                                        }}</span>
                            </small>
                            <div class="mt-2 fw-bold pe-4">
                                <!-- <p class="mb-0">Jawaban Anda:</p> -->
                                <template v-if="question.question_type === 'multiple_choice'">
                                    <p class="text-primary"
                                        v-if="getOptionText(question, getAnswerForQuestion(question.id))">
                                        {{ getOptionText(question, getAnswerForQuestion(question.id)) }}
                                    </p>
                                    <p class="text-danger" v-else>
                                        **Jawaban tidak ditemukan**
                                    </p>
                                </template>
                                <template v-else>
                                    <p class="text-primary" v-if="getAnswerForQuestion(question.id)?.answer_value">
                                        {{ getAnswerForQuestion(question.id)?.answer_value }}
                                    </p>
                                    <p class="text-danger" v-else>
                                        **Jawaban tidak ditemukan**
                                    </p>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
