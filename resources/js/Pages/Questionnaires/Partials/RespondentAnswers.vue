<script setup>
import { computed, ref, watch } from 'vue';
import QuestionnaireInfoCard from './QuestionnaireInfoCard.vue';
import BaseButton from '@/Components/BaseButton.vue';

const props = defineProps({
    questionnaire: Object,
    respondentType: String, // 'internal' | 'external'
    respondentId: Number,   // user_id OR respondent_external_id
});

const emit = defineEmits(['back']);
const activeRoleId = ref(null);
const questionSearch = ref('');

// 1. Helper: Cari Data Responden (User / External)
const selectedRespondent = computed(() => {
    if (props.respondentType === 'internal') {
        const answer = props.questionnaire.answers.find(a => a.user_id === Number(props.respondentId));
        return answer ? answer.user : null;
    } else {
        const answer = props.questionnaire.answers.find(a => a.respondent_external_id === Number(props.respondentId));
        return answer ? answer.respondent_external : null;
    }
});

// 2. Helper: List Role Unik User di Kuesioner Ini
const userRoles = computed(() => {
    if (props.respondentType === 'internal') {
        // Ambil semua jawaban user ini
        const answers = props.questionnaire.answers.filter(a => a.user_id === Number(props.respondentId));

        // Map untuk menyaring role yang unik saja
        const roleMap = new Map();
        answers.forEach(a => {
            if (a.role) {
                // Key = ID Role, Value = Object Role
                roleMap.set(a.role.id, { id: a.role.id, name: a.role.name });
            }
        });

        // Kembalikan array role unik
        return Array.from(roleMap.values());
    } else {
        // Jika Eksternal, ambil role dari tabel respondent_external
        const ext = selectedRespondent.value;
        if (!ext) return [{ id: 'external', name: 'Eksternal' }];

        let roleName = 'Eksternal';
        if (ext.role === 'alumni') roleName = 'Alumni';
        else if (ext.role === 'mitra') roleName = 'Mitra Kerjasama';
        else if (ext.role === 'pengguna_lulusan') roleName = 'Pengguna Lulusan';

        return [{ id: 'external', name: roleName }];
    }
});

// 3. Watcher Penting: Set Default Active Role saat User Berubah
watch(userRoles, (newRoles) => {
    if (newRoles && newRoles.length > 0) {
        // Cek apakah activeRoleId yang sekarang masih valid di list role baru?
        const currentRoleIsValid = newRoles.find(r => r.id === activeRoleId.value);

        // Jika tidak valid (atau null), set ke role pertama
        if (!currentRoleIsValid) {
            activeRoleId.value = newRoles[0].id;
        }
    } else {
        activeRoleId.value = null;
    }
}, { immediate: true, deep: true });

// 4. Filter Pertanyaan (Search)
const filteredQuestions = computed(() => {
    const questions = props.questionnaire.questions || [];
    if (!questionSearch.value) return questions;

    const query = questionSearch.value.toLowerCase();
    return questions.filter(q =>
        q.question_text.toLowerCase().includes(query) ||
        (q.category?.name || '').toLowerCase().includes(query)
    );
});

// 5. Filter Jawaban Berdasarkan Responden DAN Role Aktif
const respondentAnswers = computed(() => {
    if (props.respondentType === 'internal') {
        if (!activeRoleId.value) return [];
        // Filter ganda: User ID & Role ID
        return props.questionnaire.answers.filter(a =>
            a.user_id === Number(props.respondentId) &&
            a.role_id === activeRoleId.value
        );
    } else {
        return props.questionnaire.answers.filter(a =>
            a.respondent_external_id === Number(props.respondentId)
        );
    }
});

// UI Helpers
const respondentName = computed(() => selectedRespondent.value?.name || 'Responden Tidak Ditemukan');

const respondentDetails = computed(() => {
    const data = selectedRespondent.value;
    if (!data) return '-';

    if (props.respondentType === 'internal') {
        let details = `<i class="fa-solid fa-envelope me-1"></i> ${data.email}`;
        if (data.student_detail) details += `<br><i class="fa-solid fa-id-card me-1"></i> NIM: <strong>${data.student_detail.nim}</strong>`;
        if (data.lecturer_detail) details += `<br><i class="fa-solid fa-id-card me-1"></i> NIDN: <strong>${data.lecturer_detail.nidn}</strong>`;
        return details;
    } else {
        const instansi = data.role === 'alumni' ? 'Lulusan UPU' : (data.company_or_institution || '-');
        return `
            <div><i class="fa-solid fa-building me-1"></i> ${instansi}</div>
            <div><i class="fa-solid fa-phone me-1"></i> ${data.contact_number}</div>
        `;
    }
});

const getAnswerForQuestion = (questionId) => {
    return respondentAnswers.value.find(a => a.question_id === questionId);
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
    <div class="card-body">

        <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
            <div>
                <h3 class="card-title mb-1">
                    <i class="fa-solid fa-file-contract me-2 text-primary"></i> Review Jawaban
                </h3>
                <div class="text-muted small">Detail respons per individu</div>
            </div>
            <BaseButton type="button" variant="secondary" outline @click="emit('back')" size="sm">
                <i class="fa-solid fa-arrow-left me-2"></i> Kembali
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
                            <h2 class="card-title mb-1 text-primary">{{ respondentName }}</h2>
                            <div class="text-muted small lh-sm" v-html="respondentDetails"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card border h-100 shadow-sm">
                    <div class="card-body">
                        <div class="text-uppercase text-muted small fw-bold mb-2">
                            Peran Saat Mengisi
                        </div>

                        <div v-if="respondentType === 'internal' && userRoles.length > 1">
                            <label class="form-label mb-1 small text-dark">Pilih Role Jawaban:</label>
                            <select v-model="activeRoleId" class="form-select form-select-sm border-primary text-primary fw-bold shadow-none">
                                <option v-for="role in userRoles" :key="role.id" :value="role.id">
                                    {{ role.name }} (Lihat Jawaban)
                                </option>
                            </select>
                            <div class="form-text text-muted mt-1" style="font-size: 11px;">
                                User ini menjawab kuesioner sebagai <strong>{{ userRoles.length }} peran berbeda</strong>.
                            </div>
                        </div>

                        <div v-else>
                            <span class="badge bg-blue-lt fs-3 p-2 w-100 justify-content-center">
                                <i class="fa-solid fa-user-tag me-2"></i>
                                {{ userRoles[0]?.name || 'Tidak Ada Role' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-3 bg-light p-2 rounded border">
            <div class="fw-bold px-2">
                <i class="fa-solid fa-list-ol me-2"></i> Daftar Pertanyaan
            </div>
            <div class="w-auto">
                 <div class="input-icon">
                    <span class="input-icon-addon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" v-model="questionSearch" class="form-control form-control-sm" placeholder="Cari isi pertanyaan..." style="width: 250px;">
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <div v-for="(question, index) in filteredQuestions" :key="question.id" class="card card-sm border shadow-sm">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="me-3">
                            <span class="badge bg-secondary-lt">{{ index + 1 }}</span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-dark mb-2">{{ question.question_text }}</div>

                            <div class="bg-light p-3 rounded-2 border border-dashed position-relative">
                                <template v-if="getAnswerForQuestion(question.id)">

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

                                    <div class="mt-2 text-end border-top pt-2">
                                        <span class="badge bg-green-lt" style="font-size: 9px;">
                                            <i class="fa-regular fa-clock me-1"></i>
                                            {{ formatDate(getAnswerForQuestion(question.id).created_at) }}
                                        </span>
                                    </div>
                                </template>

                                <template v-else>
                                    <div class="text-danger small d-flex align-items-center">
                                        <i class="fa-solid fa-circle-xmark me-2"></i>
                                        <em>Tidak dijawab pada sesi role ini.</em>
                                    </div>
                                </template>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div v-if="filteredQuestions.length === 0" class="empty py-5">
                <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                <p class="empty-title">Tidak ditemukan</p>
                <p class="empty-subtitle text-muted">Tidak ada pertanyaan yang cocok dengan pencarian Anda.</p>
            </div>
        </div>

    </div>
</template>

<style scoped>
.space-y-3 > * + * { margin-top: 1rem; }
.bg-primary-lt { background-color: #eef6ff !important; color: #182433; }
</style>
