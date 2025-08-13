<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import QuestionnaireForm from './Partials/QuestionnaireForm.vue';
import QuestionnaireMenu from './Partials/QuestionnaireMenu.vue';
import QuestionnaireCategory from './Partials/QuestionnaireCategory.vue';
import QuestionnaireOption from './Partials/QuestionnaireOption.vue';
import QuestionnaireQuestion from './Partials/QuestionnaireQuestion.vue';
import QuestionnaireResults from './Partials/QuestionnaireResults.vue';
import QuestionnaireRespondents from './Partials/QuestionnaireRespondents.vue';
import RespondentAnswers from './Partials/RespondentAnswers.vue'; // <-- Tambahkan
import BaseAlert from '@/Components/BaseAlert.vue';

const props = defineProps({
    questionnaire: Object,
    roles: Array,
    academicPeriods: Array,
    faculties: Array,
    programStudies: Array,
    questionCategories: Array,
});

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

const activeMenu = ref('basic');
const selectedUserId = ref(null); // <-- Tambahkan state baru
const isEditing = ref(false);

const update = () => {
    form.put(route('questionnaires.update', props.questionnaire.id), {
        onSuccess: () => {
            isEditing.value = false;
        }
    });
};

const showRespondentAnswers = (userId) => {
    selectedUserId.value = userId;
    activeMenu.value = 'respondent-answers';
};

const backToRespondents = () => {
    activeMenu.value = 'respondents';
};
</script>

<template>

    <Head :title="`Detail Kuesioner: ${questionnaire.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        Manajemen Kuesioner
                    </div>
                    <h2 class="page-title">
                        Detail Kuesioner
                    </h2>
                </div>
            </div>
        </template>

        <BaseAlert type="warning" title="Penting: Periksa Kembali!"
            message="Setelah kuesioner diaktifkan dan mulai diisi, semua data (pertanyaan, opsi, dan kategori) tidak dapat diubah lagi. Pastikan semua data sudah benar sebelum mengaktifkan kuesioner ini."
            class="mb-4" />

        <div class="card">
            <div class="row g-0">
                <div class="col-3 d-none d-md-block border-end">
                    <div class="card-body">
                        <QuestionnaireMenu v-model="activeMenu" />
                    </div>
                </div>
                <div class="col d-flex flex-column">
                    <div v-if="activeMenu === 'basic'">
                        <QuestionnaireForm :form="form" :roles="roles" :academicPeriods="academicPeriods"
                            :faculties="faculties" :programStudies="programStudies" @submit="update"
                            :is-disabled="!isEditing" :is-editing="isEditing" @edit-toggle="isEditing = !isEditing" />
                    </div>
                    <div v-else-if="activeMenu === 'categories'">
                        <QuestionnaireCategory :questionnaire="questionnaire"
                            :questionCategories="questionnaire.categories" />
                    </div>
                    <div v-else-if="activeMenu === 'options'">
                        <QuestionnaireOption :questionnaire="questionnaire" :questionOptions="questionnaire.options" />
                    </div>
                    <div v-else-if="activeMenu === 'questions'">
                        <QuestionnaireQuestion :questionnaire="questionnaire"
                            :questionCategories="questionCategories" />
                    </div>
                    <div v-else-if="activeMenu === 'results'">
                        <QuestionnaireResults :questionnaire="questionnaire" />
                    </div>
                    <div v-else-if="activeMenu === 'respondents'">
                        <QuestionnaireRespondents :questionnaire="questionnaire" @show-answers="showRespondentAnswers" />
                    </div>
                    <div v-else-if="activeMenu === 'respondent-answers'">
                        <RespondentAnswers :questionnaire="questionnaire" :userId="selectedUserId" @back="backToRespondents" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
