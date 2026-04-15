<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import QuestionnaireOptionTable from './Partials/QuestionnaireOptionTable.vue';
import QuestionnaireInfoCard from './Partials/QuestionnaireInfoCard.vue';
import QuestionnaireSidebarTabs from './Partials/QuestionnaireSidebarTabs.vue';

const props = defineProps({
    questionnaire: Object,
    questionOptions: Array,
});
</script>

<template>
    <Head :title="`Opsi Jawaban Kuesioner: ${questionnaire.name}`" />
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
                <div class="col-auto ms-auto d-print-none">
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

                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header border-0">
                        <div>
                            <h3 class="card-title fw-bold">Manajemen Opsi Jawaban</h3>
                            <p class="card-subtitle text-muted small">Kelola opsi jawaban yang dapat digunakan kembali untuk pertanyaan kuesioner.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <QuestionnaireOptionTable :questionnaire="questionnaire" :questionOptions="questionOptions" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
