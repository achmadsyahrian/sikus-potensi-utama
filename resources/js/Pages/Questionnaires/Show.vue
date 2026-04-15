<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import QuestionnaireForm from './Partials/QuestionnaireForm.vue';
import QuestionnaireSidebarTabs from './Partials/QuestionnaireSidebarTabs.vue';
import BaseAlert from '@/Components/BaseAlert.vue';

const props = defineProps({
    questionnaire: Object,
    roles: Array,
    academicPeriods: Array,
    faculties: Array,
    programStudies: Array,
});

const hasAnswers = computed(() => props.questionnaire.total_answers > 0);

const form = useForm({
    id: props.questionnaire.id,
    name: props.questionnaire.name,
    description: props.questionnaire.description,
    academic_period_id: props.questionnaire.academic_period_id,
    is_active: props.questionnaire.is_active,
    start_date: props.questionnaire.start_date,
    end_date: props.questionnaire.end_date,
    targets: props.questionnaire.targets.map(t => ({
        target_type: t.target_type,
        target_value: t.target_value
    })),
});

const isEditing = ref(false);

const update = () => {
    form.put(route('questionnaires.update', props.questionnaire.id), {
        onSuccess: () => { isEditing.value = false; }
    });
};
</script>

<template>
    <Head :title="`Detail Kuesioner: ${questionnaire.name}`" />
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

        <BaseAlert
            v-if="!hasAnswers"
            type="warning"
            title="Belum Ada Jawaban"
            message="Kuesioner ini belum memiliki jawaban. Anda masih dapat mengubah struktur pertanyaan, opsi, dan kategori."
            class="mb-4"
        />

        <div class="row g-4">
            <QuestionnaireSidebarTabs :questionnaire="questionnaire" />

            <div class="col-12 col-md-9 col-lg-10">
                <div class="card border-0 shadow-sm">
                    <QuestionnaireForm
                        :form="form"
                        :questionnaire="questionnaire"
                        :roles="roles"
                        :academicPeriods="academicPeriods"
                        :faculties="faculties"
                        :programStudies="programStudies"
                        @submit="update"
                        :is-disabled="!isEditing"
                        :is-editing="isEditing"
                        @edit-toggle="isEditing = !isEditing"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
