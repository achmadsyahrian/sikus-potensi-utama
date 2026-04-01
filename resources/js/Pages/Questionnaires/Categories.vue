<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import QuestionnaireCategoryTable from './Partials/QuestionnaireCategoryTable.vue';
import QuestionnaireInfoCard from './Partials/QuestionnaireInfoCard.vue';

const props = defineProps({
    questionnaire: Object,
    questionCategories: Array,
});
</script>

<template>
    <Head :title="`Kategori Kuesioner: ${questionnaire.name}`" />
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
                    <li class="nav-item">
                        <Link :href="route('questionnaires.show', questionnaire.id)" class="nav-link text-nowrap">
                            <i class="fa-solid fa-circle-info me-2" style="opacity: 0.6"></i> Info Dasar
                        </Link>
                    </li>
                    <li class="nav-item">
                        <Link :href="route('questionnaires.categories', questionnaire.id)" class="nav-link active fw-bold text-nowrap">
                            <i class="fa-solid fa-layer-group me-2"></i> Kategori
                        </Link>
                    </li>
                    <li class="nav-item">
                        <Link :href="route('questionnaires.options', questionnaire.id)" class="nav-link text-nowrap">
                            <i class="fa-solid fa-list-check me-2" style="opacity: 0.6"></i> Opsi Jawaban
                        </Link>
                    </li>
                    <li class="nav-item">
                        <Link :href="route('questionnaires.questions', questionnaire.id)" class="nav-link text-nowrap">
                            <i class="fa-solid fa-clipboard-question me-2" style="opacity: 0.6"></i> Pertanyaan
                        </Link>
                    </li>
                    <li class="nav-item">
                        <Link :href="route('questionnaires.results', questionnaire.id)" class="nav-link text-nowrap">
                            <i class="fa-solid fa-chart-pie me-2" style="opacity: 0.6"></i> Hasil Analisis
                        </Link>
                    </li>
                    <li class="nav-item">
                        <Link :href="route('questionnaires.respondents', questionnaire.id)" class="nav-link text-nowrap">
                            <i class="fa-solid fa-users me-2" style="opacity: 0.6"></i> Responden
                        </Link>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <QuestionnaireInfoCard :questionnaire="questionnaire" />

                <div class="d-flex align-items-center justify-content-between pt-2 pb-4">
                    <div>
                        <h3 class="fw-bold mb-1">Manajemen Kategori</h3>
                        <h5 class="op-7 mb-2 text-muted">Kelola urutan dan nama kategori kuesioner.</h5>
                    </div>
                </div>

                <QuestionnaireCategoryTable :questionnaire="questionnaire" :questionCategories="questionCategories" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    height: 3px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
.nav-tabs {
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 2px;
}
.nav-link.active {
    border-bottom-color: #ffffff;
    color: #206bc4 !important;
}
.nav-link {
    color: #64748b;
    transition: all 0.2s;
    font-size: 0.9rem;
}
.nav-link:hover {
    color: #1e293b;
}
</style>
