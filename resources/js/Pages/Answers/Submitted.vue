<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    questionnaire: Object,
    submittedAnswers: Array,
    isDosen: { type: Boolean, default: false },
    prodiList: { type: Array, default: () => [] },
});

const selectedProdi      = ref(null);
const dosenAnswers       = ref([]);
const isLoadingAnswers   = ref(false);

const selectProdi = async (prodi) => {
    selectedProdi.value  = prodi;
    isLoadingAnswers.value = true;
    dosenAnswers.value   = [];
    try {
        const res = await axios.get(route('answers.submitted.prodi', props.questionnaire.id), {
            params: { prodi_code: prodi.program_study_code }
        });
        dosenAnswers.value = res.data;
    } catch (e) {
        console.error(e);
    } finally {
        isLoadingAnswers.value = false;
    }
};

const activeAnswers = computed(() => props.isDosen ? dosenAnswers.value : props.submittedAnswers);

const allQuestions = computed(() => props.questionnaire.questions);

const getAnswerForQuestion = (questionId) => {
    return activeAnswers.value.find(a => a.question_id === questionId);
};

const getOptionText = (question, answer) => {
    if (question.question_type !== 'multiple_choice' || !answer) return answer?.answer_value || null;
    const option = props.questionnaire.options.find(o => o.option_value == answer.answer_value);
    return option ? option.option_text : null;
};

const getAnswerDate = (questionId) => {
    const answer = getAnswerForQuestion(questionId);
    if (answer?.created_at) {
        return new Date(answer.created_at).toLocaleDateString('id-ID', {
            year: 'numeric', month: 'long', day: 'numeric'
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
                    <div class="page-pretitle">Jawaban Kuesioner</div>
                    <h2 class="page-title">{{ questionnaire.name }}</h2>
                </div>
            </div>
        </template>

        <div class="container-xl">

            <!-- Step pilih prodi (khusus dosen) -->
            <template v-if="isDosen && !selectedProdi">
                <div class="card">
                    <div class="card-status-top bg-teal"></div>
                    <div class="card-header border-0">
                        <div>
                            <h3 class="card-title text-teal">
                                <i class="fa-solid fa-chalkboard-user me-2"></i>
                                Pilih Program Studi
                            </h3>
                            <p class="card-subtitle text-muted small">
                                Pilih program studi untuk melihat jawaban yang sudah Anda isi.
                            </p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div
                                v-for="prodi in prodiList"
                                :key="prodi.program_study_code"
                                class="col-12 col-sm-6 col-lg-4"
                            >
                                <div
                                    class="card card-sm border-2 border-teal h-100 cursor-pointer card-hover"
                                    @click="selectProdi(prodi)"
                                >
                                    <div class="card-body d-flex align-items-center gap-3">
                                        <span class="avatar avatar-md bg-teal-lt rounded">
                                            <i class="fa-solid fa-building-columns text-teal"></i>
                                        </span>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">{{ prodi.program_studi }}</div>
                                            <div class="text-muted small">
                                                <span v-if="prodi.degree_level" class="badge bg-azure-lt text-azure me-1">{{ prodi.degree_level }}</span>
                                                {{ prodi.program_study_code }}
                                            </div>
                                        </div>
                                        <i class="fa-solid fa-chevron-right text-teal"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <Link :href="route('answers.index')">
                            <button class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-arrow-left me-2"></i> Kembali
                            </button>
                        </Link>
                    </div>
                </div>
            </template>

            <!-- Tampil jawaban -->
            <template v-else>
                <!-- Info prodi yang dipilih (dosen) -->
                <div v-if="isDosen && selectedProdi" class="alert alert-info d-flex align-items-center gap-2 mb-3">
                    <i class="fa-solid fa-circle-info"></i>
                    Jawaban sebagai dosen Program Studi
                    <strong>{{ selectedProdi.program_studi }}</strong>
                    <span v-if="selectedProdi.degree_level" class="badge bg-azure-lt text-azure ms-1">{{ selectedProdi.degree_level }}</span>
                    <button class="btn btn-sm btn-ghost-secondary ms-auto" @click="selectedProdi = null">
                        <i class="fa-solid fa-arrow-left me-1"></i> Ganti Prodi
                    </button>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Jawaban Anda</h3>
                        <div class="card-actions">
                            <Link :href="route('answers.index')">
                                <button class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-arrow-left me-2"></i> Kembali
                                </button>
                            </Link>
                        </div>
                    </div>

                    <div v-if="isLoadingAnswers" class="card-body text-center py-5 text-muted">
                        <i class="fa-solid fa-spinner fa-spin me-2"></i> Memuat jawaban...
                    </div>

                    <div v-else class="card-body">
                        <div
                            v-for="(question, index) in allQuestions"
                            :key="question.id"
                            class="border-bottom py-3"
                        >
                            <div class="d-flex align-items-start">
                                <div class="me-3 flex-shrink-0" style="width: 32px; height: 32px;">
                                    <div class="badge bg-primary rounded-circle w-100 h-100 d-flex align-items-center justify-content-center" style="font-size: 0.8rem;">
                                        {{ index + 1 }}
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold mb-1">{{ question.question_text }}</div>
                                    <small class="text-muted d-block mb-2">
                                        Tipe: {{ question.question_type === 'multiple_choice' ? 'Pilihan Ganda' : 'Teks Bebas' }}
                                        <span v-if="getAnswerDate(question.id)">• Dijawab pada {{ getAnswerDate(question.id) }}</span>
                                    </small>
                                    <div class="mt-2 fw-bold pe-4">
                                        <template v-if="question.question_type === 'multiple_choice'">
                                            <p class="text-primary" v-if="getOptionText(question, getAnswerForQuestion(question.id))">
                                                {{ getOptionText(question, getAnswerForQuestion(question.id)) }}
                                            </p>
                                            <p class="text-danger" v-else>Jawaban tidak ditemukan</p>
                                        </template>
                                        <template v-else>
                                            <p class="text-primary" v-if="getAnswerForQuestion(question.id)?.answer_value">
                                                {{ getAnswerForQuestion(question.id)?.answer_value }}
                                            </p>
                                            <p class="text-danger" v-else>Jawaban tidak ditemukan</p>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
