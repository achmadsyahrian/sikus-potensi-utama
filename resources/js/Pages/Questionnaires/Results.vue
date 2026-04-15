<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import QuestionnaireSidebarTabs from './Partials/QuestionnaireSidebarTabs.vue';
import QuestionnaireInfoCard    from './Partials/QuestionnaireInfoCard.vue';
import ResultKpiCards           from './Partials/Results/ResultKpiCards.vue';
import ResultIkmTable           from './Partials/Results/ResultIkmTable.vue';
import ResultScoreAnalysis      from './Partials/Results/ResultScoreAnalysis.vue';
import ResultSummary            from './Partials/Results/ResultSummary.vue';
import ResultPerProdi           from './Partials/Results/ResultPerProdi.vue';
import ResultQuestionChart      from './Partials/Results/ResultQuestionChart.vue';
import ResultEssayList          from './Partials/Results/ResultEssayList.vue';
import BaseOffcanvas            from '@/Components/BaseOffcanvas.vue';

const props = defineProps({
    questionnaire:        Object,
    roles:                Array,
    satisfactionCriteria: Array,
    programStudies:       Array,
    questionStats:        Object,
    summaryStats:         Array,
    essayPreviews:        Object,
    essayCounts:          Object,
    categoryScores:       Array,
    bestCategory:         Object,
    worstCategory:        Object,
    totalRespondents:     Number,
    totalInternal:        Number,
    totalExternal:        Number,
    respondentBreakdown:  Array,
    externalBreakdown:    Array,
});

const activeSubTab         = ref('ringkasan');
const activeCategoryFilter = ref('all');
const sidebarSearch        = ref('');
const sidebarData          = ref({ title: '', answers: [] });
const displayLimit         = ref(50);

const hasData = computed(() => props.questionnaire.total_answers > 0);

const subTabs = [
    { key: 'ringkasan',      label: 'Ringkasan',     icon: 'fa-chart-pie' },
    { key: 'per-prodi',      label: 'Per Prodi',     icon: 'fa-building-columns' },
    { key: 'per-pertanyaan', label: 'Per Pertanyaan', icon: 'fa-list-check' },
];

const filteredQuestions = computed(() => {
    if (activeCategoryFilter.value === 'all') return props.questionnaire.questions;
    return props.questionnaire.questions.filter(q => q.category_id == activeCategoryFilter.value);
});

const questionCountByCategory = computed(() => {
    const map = {};
    props.questionnaire.questions.forEach(q => {
        if (q.category_id) map[q.category_id] = (map[q.category_id] || 0) + 1;
    });
    return map;
});

const getRespondentName     = (ans) => ans.user?.name || ans.respondent_external?.name || 'Responden';
const getRespondentIdentity = (ans) => {
    if (!ans.user) return '-';
    if (ans.user.student_detail)  return `NIM: ${ans.user.student_detail.nim}`;
    if (ans.user.lecturer_detail) return `NIDN: ${ans.user.lecturer_detail.nidn}`;
    return '-';
};

const filteredSidebarAnswers = computed(() => {
    const query = sidebarSearch.value.toLowerCase();
    const all   = sidebarData.value.answers;
    if (!query) return all.slice(0, displayLimit.value);
    return all.filter(a => {
        const name    = (a.user?.name || '').toLowerCase();
        const nim     = (a.user?.student_detail?.nim || '').toLowerCase();
        const nidn    = (a.user?.lecturer_detail?.nidn || '').toLowerCase();
        const content = (a.answer_value || '').toLowerCase();
        return name.includes(query) || nim.includes(query) || nidn.includes(query) || content.includes(query);
    }).slice(0, displayLimit.value);
});

const openFullAnswers = (data) => {
    sidebarSearch.value = '';
    displayLimit.value  = 50;
    sidebarData.value   = { title: data.question.question_text, answers: data.answers };
    new bootstrap.Offcanvas(document.getElementById('offcanvasAnswers')).show();
};
</script>

<template>
    <Head :title="`Hasil Analisis: ${questionnaire.name}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Manajemen Kuesioner</div>
                    <h2 class="page-title d-flex align-items-center gap-2">
                        <span class="text-truncate" style="max-width: 500px;">{{ questionnaire.name }}</span>
                        <span v-if="questionnaire.is_active" class="badge bg-green-lt fs-6">Aktif</span>
                        <span v-else class="badge bg-secondary-lt fs-6">Draft</span>
                    </h2>
                </div>
                <div class="col-auto ms-auto">
                    <Link :href="route('questionnaires.index')" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-2"></i> Kembali
                    </Link>
                </div>
            </div>
        </template>

        <div class="row g-4">
            <QuestionnaireSidebarTabs :questionnaire="questionnaire" />

            <div class="col-12 col-md-9 col-lg-10">
                <QuestionnaireInfoCard :questionnaire="questionnaire" />

                <!-- Empty state -->
                <div v-if="!hasData" class="card border-0 shadow-sm mt-4">
                    <div class="card-body text-center py-6">
                        <div class="text-muted mb-3"><i class="fa-solid fa-chart-pie fa-3x"></i></div>
                        <h3 class="fw-bold">Belum Ada Data Analisis</h3>
                        <p class="text-muted">Analisis akan tersedia setelah responden mulai mengisi.</p>
                    </div>
                </div>

                <template v-else>

                    <!-- KPI + Komposisi -->
                    <div class="mt-3">
                        <ResultKpiCards
                            :totalRespondents="totalRespondents"
                            :totalInternal="totalInternal"
                            :totalExternal="totalExternal"
                            :totalQuestions="questionnaire.questions.length"
                            :totalCategories="questionnaire.categories.length"
                            :bestCategory="bestCategory"
                            :worstCategory="worstCategory"
                            :respondentBreakdown="respondentBreakdown"
                            :externalBreakdown="externalBreakdown"
                        />
                    </div>

                    <!-- Indeks Kepuasan Global -->
                    <div class="mt-4">
                        <div class="section-header mb-3">
                            <span class="step-badge">1</span>
                            <div>
                                <h4 class="fw-bold mb-0">Indeks Kepuasan Global</h4>
                                <p class="text-muted small mb-0">Skor keseluruhan berdasarkan semua jawaban.</p>
                            </div>
                        </div>
                        <ResultScoreAnalysis
                            :questionnaire="questionnaire"
                            :criteria="satisfactionCriteria"
                            :programStudies="programStudies || []"
                            :roles="roles"
                        />
                    </div>

                    <!-- Sub-tab Detail -->
                    <div class="mt-4">
                        <div class="section-header mb-3">
                            <span class="step-badge">2</span>
                            <div>
                                <h4 class="fw-bold mb-0">Detail Analisis</h4>
                                <p class="text-muted small mb-0">Eksplorasi data dari berbagai sudut pandang.</p>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-header border-0 pb-0">
                                <ul class="nav nav-tabs card-header-tabs">
                                    <li v-for="tab in subTabs" :key="tab.key" class="nav-item">
                                        <a
                                            href="#"
                                            class="nav-link px-4"
                                            :class="{ 'active fw-bold': activeSubTab === tab.key }"
                                            @click.prevent="activeSubTab = tab.key"
                                        >
                                            <i :class="`fa-solid ${tab.icon} me-2`"></i>{{ tab.label }}
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <!-- Ringkasan -->
                            <div v-show="activeSubTab === 'ringkasan'" class="card-body">
                                <ResultIkmTable
                                    :categoryScores="categoryScores"
                                    :satisfactionCriteria="satisfactionCriteria"
                                />
                                <div class="mt-4">
                                    <h5 class="fw-bold mb-3">
                                        <i class="fa-solid fa-chart-bar me-2 text-primary"></i>
                                        Distribusi & Dominansi Jawaban
                                    </h5>
                                    <ResultSummary
                                        :questionnaire="questionnaire"
                                        :roles="roles"
                                        :summaryStats="summaryStats"
                                    />
                                </div>
                            </div>

                            <!-- Per Prodi -->
                            <div v-show="activeSubTab === 'per-prodi'" class="card-body p-0">
                                <ResultPerProdi
                                    :questionnaire="questionnaire"
                                    :criteria="satisfactionCriteria"
                                    :programStudies="programStudies || []"
                                    :roles="roles"
                                />
                            </div>

                            <!-- Per Pertanyaan -->
                            <div v-show="activeSubTab === 'per-pertanyaan'" class="card-body">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-4">
                                    <span class="text-muted small fw-semibold">
                                        <i class="fa-solid fa-filter me-1"></i> Filter Aspek:
                                    </span>
                                    <button
                                        class="btn btn-sm"
                                        :class="activeCategoryFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary'"
                                        @click="activeCategoryFilter = 'all'"
                                    >
                                        Semua
                                        <span class="badge ms-1" :class="activeCategoryFilter === 'all' ? 'bg-white text-primary' : 'bg-secondary'">
                                            {{ questionnaire.questions.length }}
                                        </span>
                                    </button>
                                    <button
                                        v-for="cat in questionnaire.categories"
                                        :key="cat.id"
                                        class="btn btn-sm text-nowrap"
                                        :class="activeCategoryFilter === cat.id ? 'btn-primary' : 'btn-outline-secondary'"
                                        @click="activeCategoryFilter = cat.id"
                                    >
                                        {{ cat.name }}
                                        <span class="badge ms-1" :class="activeCategoryFilter === cat.id ? 'bg-white text-primary' : 'bg-secondary'">
                                            {{ questionCountByCategory[cat.id] ?? 0 }}
                                        </span>
                                    </button>
                                </div>

                                <div v-for="(q, i) in filteredQuestions" :key="q.id">
                                    <ResultQuestionChart
                                        v-if="q.question_type === 'multiple_choice'"
                                        :question="q" :index="i"
                                        :options="questionnaire.options"
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

                </template>
            </div>
        </div>

        <!-- Offcanvas Essay -->
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
                        <div class="text-muted mb-2" style="font-size:11px;">
                            <i class="fa-solid fa-id-card me-1"></i> {{ getRespondentIdentity(ans) }}
                            <span class="mx-1">|</span>
                            <i class="fa-solid fa-user-tag me-1"></i> {{ ans.role?.name || 'Eksternal' }}
                        </div>
                        <div class="text-dark small p-2 bg-light rounded border">"{{ ans.answer_value }}"</div>
                    </div>
                    <div v-if="filteredSidebarAnswers.length === 0" class="p-5 text-center text-muted">
                        Data tidak ditemukan.
                    </div>
                    <div v-if="displayLimit < sidebarData.answers.length && !sidebarSearch" class="p-3">
                        <button @click="displayLimit += 100" class="btn btn-white w-100 border shadow-sm">
                            Muat Lebih Banyak...
                        </button>
                    </div>
                </div>
            </template>
        </BaseOffcanvas>
    </AuthenticatedLayout>
</template>

<style scoped>
.step-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background-color: #206bc4;
    color: white;
    font-size: 13px;
    font-weight: 700;
    flex-shrink: 0;
}
.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
}
.nav-tabs .nav-link { border: none; border-bottom: 2px solid transparent; color: #64748b; }
.nav-tabs .nav-link:hover { color: #1e293b; }
.nav-tabs .nav-link.active { border-color: #206bc4; color: #206bc4; background: transparent; }
</style>
