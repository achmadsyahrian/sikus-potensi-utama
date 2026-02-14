<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import QuestionnaireInfoCard from './QuestionnaireInfoCard.vue';
import ResultSummary from './Results/ResultSummary.vue';
import ResultQuestionChart from './Results/ResultQuestionChart.vue';
import ResultScoreAnalysis from './Results/ResultScoreAnalysis.vue';
import ResultEssayList from './Results/ResultEssayList.vue';
import BaseOffcanvas from '@/Components/BaseOffcanvas.vue';

const props = defineProps({
    questionnaire: Object,
    roles: Array,
    satisfactionCriteria: Array,
    programStudies: Array
});

const activeCategoryFilter = ref('all');
const sidebarSearch = ref('');
const sidebarData = ref({ title: '', answers: [] });
const displayLimit = ref(50);

// Helper untuk mendapatkan Nama
const getRespondentName = (ans) => ans.user?.name || ans.respondent_external?.name || 'Responden';

// Helper untuk mendapatkan NIM/NIDN berdasarkan detail user
const getRespondentIdentity = (ans) => {
    if (!ans.user) return '-';

    // Cek Mahasiswa (NIM)
    if (ans.user.student_detail) {
        return `NIM: ${ans.user.student_detail.nim}`;
    }

    // Cek Dosen/Pegawai (NIDN)
    if (ans.user.lecturer_detail) {
        return `NIDN: ${ans.user.lecturer_detail.nidn}`;
    }

    return '-';
};

const openFullAnswers = (data) => {
    sidebarSearch.value = '';
    displayLimit.value = 50;
    sidebarData.value = { title: data.question.question_text, answers: data.answers };
    new bootstrap.Offcanvas(document.getElementById('offcanvasAnswers')).show();
};

const filteredQuestions = computed(() => {
    if (activeCategoryFilter.value === 'all') return props.questionnaire.questions;
    return props.questionnaire.questions.filter(q => q.category_id == activeCategoryFilter.value);
});

// Logic Pencarian yang diperbarui (Name, NIM, NIDN, atau Answer)
const filteredSidebarAnswers = computed(() => {
    const query = sidebarSearch.value.toLowerCase();
    const all = sidebarData.value.answers;

    if (!query) return all.slice(0, displayLimit.value);

    return all.filter(a => {
        const name = (a.user?.name || '').toLowerCase();
        const nim = (a.user?.student_detail?.nim || '').toLowerCase();
        const nidn = (a.user?.lecturer_detail?.nidn || '').toLowerCase();
        const content = (a.answer_value || '').toLowerCase();

        return name.includes(query) ||
               nim.includes(query) ||
               nidn.includes(query) ||
               content.includes(query);
    }).slice(0, displayLimit.value);
});
</script>

<template>
    <div class="card-body">
        <QuestionnaireInfoCard :questionnaire="questionnaire" />

        <div class="p-3">
            <ResultScoreAnalysis
                v-if="questionnaire.answers.length > 0"
                :questionnaire="questionnaire"
                :criteria="satisfactionCriteria"
                :programStudies="programStudies || []"
                :roles="roles"
            />

            <ResultSummary :questionnaire="questionnaire" :roles="roles" :criteria="satisfactionCriteria" />

            <div class="card shadow-sm my-4">
                <div class="card-body p-0">
                    <div class="d-flex align-items-center p-3 border-bottom bg-light-lt">
                        <i class="fa-solid fa-filter text-primary me-2"></i>
                        <span class="fw-bold text-dark">Filter Berdasarkan Kategori</span>
                    </div>
                    <div class="p-3">
                        <div class="nav nav-pills gap-2 flex-nowrap overflow-auto pb-2 custom-scrollbar">
                            <a href="javascript:void(0)"
                            class="nav-link px-3 py-2 border shadow-none transition-all"
                            :class="activeCategoryFilter === 'all' ? 'active bg-primary text-white border-primary' : 'bg-white text-muted border-primary-lt'"
                            @click="activeCategoryFilter = 'all'">
                                <i class="fa-solid fa-layer-group me-1"></i> Semua Kategori
                            </a>

                            <a v-for="cat in questionnaire.categories" :key="cat.id"
                            href="javascript:void(0)"
                            class="nav-link px-3 py-2 border shadow-none transition-all text-nowrap"
                            :class="activeCategoryFilter === cat.id ? 'active bg-primary text-white border-primary' : 'bg-white text-muted border-primary-lt'"
                            @click="activeCategoryFilter = cat.id">
                            {{ cat.name }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div v-for="(q, i) in filteredQuestions" :key="q.id">
                <ResultQuestionChart
                    v-if="q.question_type === 'multiple_choice'"
                    :question="q" :index="i" :options="questionnaire.options"
                    :answers="questionnaire.answers.filter(a => a.question_id === q.id)"
                    :criteria="satisfactionCriteria"
                />
                <ResultEssayList
                    v-else
                    :question="q" :index="i"
                    :answers="questionnaire.answers.filter(a => a.question_id === q.id)"
                    @open-detail="openFullAnswers"
                />
            </div>
        </div>

        <BaseOffcanvas id="offcanvasAnswers" :title="sidebarData.title" width="600px">
            <template #header>
                <div class="p-3 bg-light border-bottom">
                    <input type="text" v-model="sidebarSearch" class="form-control border-primary" placeholder="Cari nama, NIM, NIDN, atau jawaban...">
                </div>
            </template>
            <template #body>
                <div class="list-group list-group-flush">
                    <div v-for="(ans, idx) in filteredSidebarAnswers" :key="idx" class="list-group-item py-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="badge bg-blue-lt fw-bold">{{ getRespondentName(ans) }}</span>
                            <span class="text-muted small">{{ new Date(ans.created_at).toLocaleDateString('id-ID') }}</span>
                        </div>
                        <div class="text-muted mb-2" style="font-size: 11px;">
                            <i class="fa-solid fa-id-card me-1"></i> {{ getRespondentIdentity(ans) }}
                            <span class="mx-1">|</span>
                            <i class="fa-solid fa-user-tag me-1"></i> Role: {{ ans.role?.name || '-' }}
                        </div>
                        <div class="text-dark small p-2 bg-light rounded border border-dashed border-primary-lt">
                            "{{ ans.answer_value }}"
                        </div>
                    </div>
                    <div v-if="filteredSidebarAnswers.length === 0" class="p-5 text-center text-muted">
                        Data tidak ditemukan.
                    </div>
                    <div v-if="displayLimit < sidebarData.answers.length && !sidebarSearch" class="p-3">
                        <button @click="displayLimit += 100" class="btn btn-white w-100 border shadow-sm">Muat Lebih Banyak...</button>
                    </div>
                </div>
            </template>
        </BaseOffcanvas>
    </div>
</template>

<style scoped>
/* Styling tambahan agar UI lebih smooth */
.nav-link {
    border-radius: 8px !important;
    font-size: 0.85rem;
    font-weight: 500;
}

.nav-link.active {
    box-shadow: 0 4px 6px -1px rgba(32, 107, 196, 0.2) !important;
}

.transition-all {
    transition: all 0.2s ease-in-out;
}

/* Merapikan scrollbar horizontal agar tidak jelek di Windows */
.custom-scrollbar::-webkit-scrollbar {
    height: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>
