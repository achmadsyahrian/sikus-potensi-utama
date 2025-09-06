<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue'; // Tambahkan 'computed'
import QuestionnaireForm from './Partials/QuestionnaireForm.vue';
import QuestionnaireMenu from './Partials/QuestionnaireMenu.vue';
import QuestionnaireCategory from './Partials/QuestionnaireCategory.vue';
import QuestionnaireOption from './Partials/QuestionnaireOption.vue';
import QuestionnaireQuestion from './Partials/QuestionnaireQuestion.vue';
import QuestionnaireResults from './Partials/QuestionnaireResults.vue';
import QuestionnaireRespondents from './Partials/QuestionnaireRespondents.vue';
import RespondentAnswers from './Partials/RespondentAnswers.vue';
import BaseAlert from '@/Components/BaseAlert.vue';

const props = defineProps({
    questionnaire: Object,
    roles: Array,
    academicPeriods: Array,
    faculties: Array,
    programStudies: Array,
    questionCategories: Array,
    respondents: Object,
});

const page = usePage();

const activeMenu = ref('basic');
const selectedRespondentId = ref(null);
const selectedRespondentType = ref(null);

watch(() => page.url, (newUrl) => {
    const url = new URL(newUrl, window.location.origin);
    activeMenu.value = url.searchParams.get('tab') || 'basic';
    selectedRespondentId.value = url.searchParams.get('userId') || null;
    selectedRespondentType.value = url.searchParams.get('userType') || null;
}, { immediate: true });

// Computed property untuk mengecek apakah kuesioner sudah ada jawabannya
const hasAnswers = computed(() => {
    return props.questionnaire.total_answers > 0;
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

const isEditing = ref(false);

const update = () => {
    form.put(route('questionnaires.update', props.questionnaire.id), {
        onSuccess: () => {
            isEditing.value = false;
        }
    });
};

const showRespondentAnswers = ({ type, id }) => {
    selectedRespondentId.value = id;
    selectedRespondentType.value = type;
    activeMenu.value = 'respondent-answers';
};

const backToRespondents = () => {
    selectedRespondentId.value = null;
    selectedRespondentType.value = null;
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

        <BaseAlert v-if="!hasAnswers" type="warning" title="Penting: Periksa Kembali!"
            message="Setelah kuesioner diaktifkan dan mulai diisi, semua data (pertanyaan, opsi, dan kategori) tidak dapat diubah lagi. Pastikan semua data sudah benar sebelum mengaktifkan kuesioner ini."
            class="mb-4" />
        <div class="card">
            <div class="row g-0">
                <div class="col-3 d-none d-md-block border-end">
                    <div class="card-body">
                        <QuestionnaireMenu :modelValue="activeMenu" @update:modelValue="activeMenu = $event" />
                    </div>
                </div>
                <div class="col d-flex flex-column">
                    <div v-if="activeMenu === 'basic'">
                        <QuestionnaireForm :form="form" :questionnaire="questionnaire" :roles="roles" :academicPeriods="academicPeriods"
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
                        <QuestionnaireRespondents :questionnaire="questionnaire" @show-answers="showRespondentAnswers"
                            :respondents="respondents"/>
                    </div>
                    <div v-else-if="activeMenu === 'respondent-answers'">
                        <RespondentAnswers :questionnaire="questionnaire" :respondentId="selectedRespondentId" :respondentType="selectedRespondentType"
                            @back="backToRespondents" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
