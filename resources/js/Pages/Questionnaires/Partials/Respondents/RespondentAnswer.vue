<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import axios from 'axios';
import BaseButton from '@/Components/BaseButton.vue';
import { capitalizeWords } from '../../../../Utilities/string';

const props = defineProps({
    questionnaire: Object,
    respondentType: String,
    respondentId: Number,
});

const emit = defineEmits(['back']);

const isLoading = ref(true);
const errorMessage = ref('');
const respondentData = ref(null);
const rawAnswers = ref([]);

const activeRoleId = ref(null);
const questionSearch = ref('');

const fetchAnswers = async () => {
    isLoading.value = true;
    errorMessage.value = '';
    try {
        const response = await axios.get(route('questionnaires.respondent-answers', [
            props.questionnaire.id,
            props.respondentType,
            props.respondentId
        ]));

        respondentData.value = response.data.respondent;
        rawAnswers.value = response.data.answers;
    } catch (error) {
        console.error(error);
        errorMessage.value = 'Gagal memuat data jawaban. Pastikan Route dan Controller sudah benar.';
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchAnswers();
});

const userRoles = computed(() => {
    if (props.respondentType === 'internal') {
        const roleMap = new Map();
        rawAnswers.value.forEach(a => {
            if (a.role) {
                roleMap.set(a.role.id, { id: a.role.id, name: a.role.name });
            }
        });
        return Array.from(roleMap.values());
    } else {
        const ext = respondentData.value;
        if (!ext) return [{ id: 'external', name: 'Eksternal' }];

        let roleName = 'Eksternal';
        if (ext.role === 'alumni') roleName = 'Alumni';
        else if (ext.role === 'mitra') roleName = 'Mitra Kerjasama';
        else if (ext.role === 'pengguna_lulusan') roleName = 'Pengguna Lulusan';

        return [{ id: 'external', name: roleName }];
    }
});

watch(userRoles, (newRoles) => {
    if (newRoles && newRoles.length > 0) {
        const currentRoleIsValid = newRoles.find(r => r.id === activeRoleId.value);
        if (!currentRoleIsValid) {
            activeRoleId.value = newRoles[0].id;
        }
    } else {
        activeRoleId.value = null;
    }
}, { immediate: true, deep: true });

const filteredQuestions = computed(() => {
    const questions = props.questionnaire.questions || [];
    if (!questionSearch.value) return questions;

    const query = questionSearch.value.toLowerCase();
    return questions.filter(q =>
        q.question_text.toLowerCase().includes(query) ||
        (q.category?.name || '').toLowerCase().includes(query)
    );
});

const activeAnswers = computed(() => {
    if (props.respondentType === 'internal') {
        if (!activeRoleId.value) return [];
        return rawAnswers.value.filter(a => a.role_id === activeRoleId.value);
    } else {
        return rawAnswers.value;
    }
});

const respondentName = computed(() => {
    if (isLoading.value) return 'Menarik Data...';
    return respondentData.value?.name || 'Responden Tidak Ditemukan';
});

const respondentDetails = computed(() => {
    const data = respondentData.value;
    if (!data) return '-';

    if (props.respondentType === 'internal') {
        let details = `<i class="fa-solid fa-envelope me-1 text-muted"></i> ${data.email}`;
        if (data.student_detail) details += `<br><i class="fa-solid fa-id-card me-1 text-muted mt-1"></i> NIM: <strong>${data.student_detail.nim}</strong>`;
        if (data.lecturer_detail) details += `<br><i class="fa-solid fa-id-card me-1 text-muted mt-1"></i> NIDN: <strong>${data.lecturer_detail.nidn}</strong>`;
        return details;
    } else {
        const instansi = data.role === 'alumni' ? 'Lulusan UPU' : (data.company_or_institution || '-');
        return `
            <div class="mb-1"><i class="fa-solid fa-building me-1 text-muted"></i> ${instansi}</div>
            <div><i class="fa-solid fa-phone me-1 text-muted"></i> ${data.contact_number}</div>
        `;
    }
});

const getAnswerForQuestion = (questionId) => {
    return activeAnswers.value.find(a => a.question_id === questionId);
};

const getOptionText = (optionValue) => {
    const option = props.questionnaire.options.find(o => o.option_value == optionValue);
    return option ? option.option_text : `Nilai: ${optionValue}`;
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};
</script>

<template>
    <div class="card border-0 shadow-sm mt-3">

        <div v-if="isLoading" class="p-5 text-center text-muted">
            <i class="fa-solid fa-spinner fa-spin fa-2x mb-3 text-primary"></i>
            <p>Menarik data jawaban...</p>
        </div>

        <div v-else-if="errorMessage" class="p-5 text-center text-danger">
            <i class="fa-solid fa-triangle-exclamation fa-3x mb-3"></i>
            <h4>Oops! Terjadi Kesalahan</h4>
            <p>{{ errorMessage }}</p>
            <BaseButton type="button" variant="secondary" outline @click="emit('back')">Kembali</BaseButton>
        </div>

        <div v-else class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                <div>
                    <h3 class="card-title mb-1">
                        <i class="fa-solid fa-file-contract me-2 text-primary"></i> Review Jawaban Detail
                    </h3>
                </div>
                <BaseButton type="button" variant="secondary" outline @click="emit('back')" size="sm">
                    <i class="fa-solid fa-arrow-left me-2"></i> Kembali ke Daftar
                </BaseButton>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-7">
                    <div class="card bg-primary-lt border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center">
                            <span class="avatar avatar-lg rounded-circle bg-white text-primary fs-2 me-3 shadow-sm border border-primary-lt">
                                {{ respondentName.charAt(0).toUpperCase() }}
                            </span>
                            <div>
                                <h2 class="card-title mb-1 text-primary">{{ capitalizeWords(respondentName) }}</h2>
                                <div class="text-muted small lh-sm" v-html="respondentDetails"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card border border-primary-lt h-100 shadow-sm">
                        <div class="card-body">
                            <div class="text-uppercase text-primary small fw-bold mb-2">
                                <i class="fa-solid fa-user-tag me-1"></i> Peran Saat Mengisi
                            </div>

                            <div v-if="respondentType === 'internal' && userRoles.length > 1">
                                <select v-model="activeRoleId" class="form-select border-primary text-primary fw-bold shadow-none">
                                    <option v-for="role in userRoles" :key="role.id" :value="role.id">
                                        {{ role.name }} (Klik untuk lihat)
                                    </option>
                                </select>
                                <div class="form-text text-muted mt-2" style="font-size: 11px;">
                                    Responden ini mengisi kuesioner sebagai <strong>{{ userRoles.length }} peran berbeda</strong>.
                                </div>
                            </div>

                            <div v-else class="mt-3">
                                <span class="badge bg-blue fs-5 p-2 w-100 text-center">
                                    {{ userRoles[0]?.name || 'Tidak Ada Role' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-3 bg-light p-2 rounded border">
                <div class="fw-bold px-2 text-muted">
                    <i class="fa-solid fa-list-ol me-2"></i> Rekaman Jawaban
                </div>
                <div class="w-auto">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" v-model="questionSearch" class="form-control form-control-sm border-0 shadow-sm" placeholder="Cari isi pertanyaan..." style="width: 250px;">
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <div v-for="(question, index) in filteredQuestions" :key="question.id" class="card card-sm border shadow-none mb-3">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="me-3">
                                <span class="badge bg-blue-lt px-2 py-1">{{ index + 1 }}</span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark mb-2">{{ question.question_text }}</div>

                                <template v-if="getAnswerForQuestion(question.id)">
                                    <div class="bg-light p-3 rounded-2 border border-dashed position-relative">

                                        <div v-if="question.question_type === 'multiple_choice'">
                                            <div class="d-flex align-items-center text-primary fw-bold">
                                                <i class="fa-regular fa-circle-check me-2 fs-3"></i>
                                                {{ getOptionText(getAnswerForQuestion(question.id).answer_value) }}
                                            </div>
                                        </div>

                                        <div v-else>
                                            <div class="text-dark fst-italic">
                                                "{{ getAnswerForQuestion(question.id).answer_value }}"
                                            </div>
                                        </div>

                                        <div class="mt-3 text-end">
                                            <span class="text-muted" style="font-size: 10px;">
                                                <i class="fa-regular fa-clock me-1"></i> Disubmit pada {{ formatDate(getAnswerForQuestion(question.id).created_at) }}
                                            </span>
                                        </div>
                                    </div>
                                </template>

                                <template v-else>
                                    <div class="bg-red-lt p-2 rounded-2 text-danger small d-inline-flex align-items-center">
                                        <i class="fa-solid fa-circle-xmark me-2"></i>
                                        <em>Tidak dijawab pada sesi ini.</em>
                                    </div>
                                </template>

                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="filteredQuestions.length === 0" class="empty py-5">
                    <div class="empty-icon text-muted"><i class="fa-solid fa-magnifying-glass fa-2x"></i></div>
                    <p class="empty-title mt-3">Tidak ditemukan</p>
                    <p class="empty-subtitle text-muted small">Tidak ada pertanyaan yang cocok dengan pencarian Anda.</p>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
.space-y-3 > * + * { margin-top: 1rem; }
.bg-primary-lt { background-color: #eef6ff !important; color: #182433; }
.bg-red-lt { background-color: #fce8e8 !important; }
</style>
