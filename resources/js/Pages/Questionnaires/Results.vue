<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import QuestionnaireInfoCard from './Partials/QuestionnaireInfoCard.vue';
import ResultSummary from './Partials/Results/ResultSummary.vue';
import ResultQuestionChart from './Partials/Results/ResultQuestionChart.vue';
import ResultScoreAnalysis from './Partials/Results/ResultScoreAnalysis.vue';
import ResultEssayList from './Partials/Results/ResultEssayList.vue';
import BaseOffcanvas from '@/Components/BaseOffcanvas.vue';

const props = defineProps({
    questionnaire: Object,
    roles: Array,
    satisfactionCriteria: Array,
    programStudies: Array,
    questionStats: Object,
    summaryStats: Array,
    essayPreviews: Object,
    essayCounts: Object
});

const activeCategoryFilter = ref('all');
const sidebarSearch = ref('');
const sidebarData = ref({ title: '', answers: [] });
const displayLimit = ref(50);

const getRespondentName = (ans) => ans.user?.name || ans.respondent_external?.name || 'Responden';

const getRespondentIdentity = (ans) => {
    if (!ans.user) return '-';
    if (ans.user.student_detail) return `NIM: ${ans.user.student_detail.nim}`;
    if (ans.user.lecturer_detail) return `NIDN: ${ans.user.lecturer_detail.nidn}`;
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

const filteredSidebarAnswers = computed(() => {
    const query = sidebarSearch.value.toLowerCase();
    const all = sidebarData.value.answers;

    if (!query) return all.slice(0, displayLimit.value);

    return all.filter(a => {
        const name = (a.user?.name || '').toLowerCase();
        const nim = (a.user?.student_detail?.nim || '').toLowerCase();
        const nidn = (a.user?.lecturer_detail?.nidn || '').toLowerCase();
        const content = (a.answer_value || '').toLowerCase();

        return name.includes(query) || nim.includes(query) || nidn.includes(query) || content.includes(query);
    }).slice(0, displayLimit.value);
});
</script>

<template>
    <Head :title="`Hasil Analisis: ${questionnaire.name}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Manajemen Kuesioner</div>
                    <h2 class="page-title d-flex align-items-center">
                        <span class="text-truncate" style="max-width: 500px;">Detail: {{ questionnaire.name }}</span>
                        <span v-if="questionnaire.is_active" class="badge bg-green-lt ms-2 fs-6 align-middle">Aktif</span>
                        <span v-else class="badge bg-secondary-lt ms-2 fs-6 align-middle">Draft</span>
                    </h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <Link :href="route('questionnaires.index')" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-2"></i> Kembali
                    </Link>
                </div>
            </div>
        </template>

        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs flex-nowrap overflow-auto custom-scrollbar">
                    <li class="nav-item"><Link :href="route('questionnaires.show', questionnaire.id)" class="nav-link text-nowrap"><i class="fa-solid fa-circle-info me-2" style="opacity: 0.6"></i> Info Dasar</Link></li>
                    <li class="nav-item"><Link :href="route('questionnaires.categories', questionnaire.id)" class="nav-link text-nowrap"><i class="fa-solid fa-layer-group me-2" style="opacity: 0.6"></i> Kategori</Link></li>
                    <li class="nav-item"><Link :href="route('questionnaires.options', questionnaire.id)" class="nav-link text-nowrap"><i class="fa-solid fa-list-check me-2" style="opacity: 0.6"></i> Opsi Jawaban</Link></li>
                    <li class="nav-item"><Link :href="route('questionnaires.questions', questionnaire.id)" class="nav-link text-nowrap"><i class="fa-solid fa-clipboard-question me-2" style="opacity: 0.6"></i> Pertanyaan</Link></li>
                    <li class="nav-item"><Link :href="route('questionnaires.results', questionnaire.id)" class="nav-link active fw-bold text-nowrap"><i class="fa-solid fa-chart-pie me-2"></i> Hasil Analisis</Link></li>
                    <li class="nav-item"><Link :href="route('questionnaires.respondents', questionnaire.id)" class="nav-link text-nowrap"><i class="fa-solid fa-users me-2" style="opacity: 0.6"></i> Responden</Link></li>
                </ul>
            </div>

            <div class="card-body">
                <QuestionnaireInfoCard :questionnaire="questionnaire" />

                <div class="pt-2 pb-4">
                    <ResultScoreAnalysis
                        v-if="questionnaire.total_answers > 0"
                        :questionnaire="questionnaire"
                        :criteria="satisfactionCriteria"
                        :programStudies="programStudies || []"
                        :roles="roles"
                    />

                    <ResultSummary :questionnaire="questionnaire" :roles="roles" :criteria="satisfactionCriteria" :summaryStats="summaryStats" />

                    <div class="card shadow-sm my-4 border-0">
                        <div class="card-body p-0">
                            <div class="d-flex align-items-center p-3 border-bottom bg-light-lt">
                                <i class="fa-solid fa-filter text-primary me-2"></i>
                                <span class="fw-bold text-dark">Filter Berdasarkan Kategori</span>
                            </div>
                            <div class="p-3">
                                <div class="nav nav-pills gap-2 flex-nowrap overflow-auto pb-2 custom-scrollbar">
                                    <a href="javascript:void(0)"
                                    class="nav-link px-3 py-2 border shadow-none transition-all category-pill"
                                    :class="{ 'active': activeCategoryFilter === 'all' }"
                                    @click="activeCategoryFilter = 'all'">
                                        <i class="fa-solid fa-layer-group me-1"></i> Semua Kategori
                                    </a>

                                    <a v-for="cat in questionnaire.categories" :key="cat.id"
                                    href="javascript:void(0)"
                                    class="nav-link px-3 py-2 border shadow-none transition-all text-nowrap category-pill"
                                    :class="{ 'active': activeCategoryFilter === cat.id }"
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
                            :statsData="questionStats[q.id] || []"
                            :criteria="satisfactionCriteria"
                        />
                        <ResultEssayList
                            v-else
                            :question="q" :index="i"
                            :previewAnswers="essayPreviews[q.id] || []"
                            :totalAnswers="essayCounts[q.id] || 0"
                            @open-detail="openFullAnswers"
                        />
                    </div>
                </div>
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
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { height: 3px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
.nav-tabs { flex-wrap: nowrap; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; padding-bottom: 2px; }
.nav-link.active { border-bottom-color: #ffffff; color: #206bc4 !important; }
.nav-link { color: #64748b; transition: all 0.2s; font-size: 0.9rem; }
.nav-link:hover { color: #1e293b; }
.transition-all { transition: all 0.2s ease-in-out; }
</style>
