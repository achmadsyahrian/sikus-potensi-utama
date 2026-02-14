<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, usePage, router } from '@inertiajs/vue3'; // Import router
import { ref, watch, computed } from 'vue';
import QuestionnaireForm from './Partials/QuestionnaireForm.vue';
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
    satisfactionCriteria: Array,
});

const page = usePage();
const activeMenu = ref('basic');
const selectedRespondentId = ref(null);
const selectedRespondentType = ref(null);

// Definisi Menu Items agar template lebih bersih
const menuItems = [
    { id: 'basic', label: 'Info Dasar', icon: 'fa-solid fa-circle-info' },
    { id: 'categories', label: 'Kategori', icon: 'fa-solid fa-layer-group' },
    { id: 'options', label: 'Opsi Jawaban', icon: 'fa-solid fa-list-check' },
    { id: 'questions', label: 'Pertanyaan', icon: 'fa-solid fa-clipboard-question' },
    { id: 'results', label: 'Hasil Analisis', icon: 'fa-solid fa-chart-pie' },
    { id: 'respondents', label: 'Responden', icon: 'fa-solid fa-users' },
];

watch(() => page.url, (newUrl) => {
    const url = new URL(newUrl, window.location.origin);
    activeMenu.value = url.searchParams.get('tab') || 'basic';

    // Logic tambahan untuk handle sub-view responden
    selectedRespondentId.value = url.searchParams.get('userId') || null;
    selectedRespondentType.value = url.searchParams.get('userType') || null;

    // Jika ada ID responden, otomatis anggap kita sedang di view detail jawaban
    if (selectedRespondentId.value && activeMenu.value === 'respondent-answers') {
        // activeMenu sudah benar
    } else if (selectedRespondentId.value) {
         // Fallback jika URL manual
         activeMenu.value = 'respondent-answers';
    }
}, { immediate: true });

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

const showRespondentAnswers = ({ type, id }) => {
    // Gunakan router visit agar URL berubah & state terjaga
    router.get(route('questionnaires.show', props.questionnaire.id), {
        tab: 'respondent-answers',
        userType: type,
        userId: id
    }, { preserveState: true, preserveScroll: true });
};

const backToRespondents = () => {
    router.get(route('questionnaires.show', props.questionnaire.id), {
        tab: 'respondents'
    }, { preserveState: true, preserveScroll: true });
};

const changeTab = (tabId) => {
    // Reset sub-selection saat pindah tab utama
    if (tabId !== 'respondent-answers') {
        selectedRespondentId.value = null;
    }
    router.get(route('questionnaires.show', props.questionnaire.id), { tab: tabId }, {
        preserveState: true,
        preserveScroll: true, // Agar tidak lompat ke atas saat ganti tab
        replace: true // Agar history browser tidak penuh sampah navigasi tab
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

        <BaseAlert v-if="!hasAnswers" type="warning" title="Mode Edit Terbuka"
            message="Kuesioner ini belum memiliki jawaban. Anda masih dapat mengubah struktur pertanyaan, opsi, dan kategori."
            class="mb-4" />

        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs flex-nowrap overflow-auto custom-scrollbar">
                    <li v-for="item in menuItems" :key="item.id" class="nav-item">
                        <a href="#"
                           class="nav-link text-nowrap"
                           :class="{ 'active fw-bold': activeMenu === item.id }"
                           @click.prevent="changeTab(item.id)"
                        >
                            <i :class="`${item.icon} me-2`" :style="{ opacity: activeMenu === item.id ? '1' : '0.6' }"></i>
                            {{ item.label }}
                        </a>
                    </li>

                    <li v-if="activeMenu === 'respondent-answers'" class="nav-item ms-2 border-start ps-2">
                        <a href="#" class="nav-link active fw-bold text-nowrap">
                            <i class="fa-solid fa-file-lines me-2"></i> Detail Jawaban
                        </a>
                    </li>
                </ul>
            </div>

            <div class="tab-content">
                <div v-if="activeMenu === 'basic'">
                    <QuestionnaireForm
                        :form="form" :questionnaire="questionnaire" :roles="roles" :academicPeriods="academicPeriods"
                        :faculties="faculties" :programStudies="programStudies" @submit="update"
                        :is-disabled="!isEditing" :is-editing="isEditing" @edit-toggle="isEditing = !isEditing"
                    />
                </div>

                <div v-else-if="activeMenu === 'categories'">
                    <QuestionnaireCategory :questionnaire="questionnaire" :questionCategories="questionnaire.categories" />
                </div>

                <div v-else-if="activeMenu === 'options'">
                    <QuestionnaireOption :questionnaire="questionnaire" :questionOptions="questionnaire.options" />
                </div>

                <div v-else-if="activeMenu === 'questions'">
                    <QuestionnaireQuestion :questionnaire="questionnaire" :questionCategories="questionCategories" />
                </div>

                <div v-else-if="activeMenu === 'results'">
                    <QuestionnaireResults
                        :questionnaire="questionnaire" :roles="roles"
                        :satisfactionCriteria="satisfactionCriteria" :programStudies="programStudies"
                    />
                </div>

                <div v-else-if="activeMenu === 'respondents'">
                    <QuestionnaireRespondents
                        :questionnaire="questionnaire" @show-answers="showRespondentAnswers"
                        :respondents="respondents" :programStudies="programStudies"
                    />
                </div>

                <div v-else-if="activeMenu === 'respondent-answers'">
                    <RespondentAnswers
                        :questionnaire="questionnaire" :respondentId="selectedRespondentId" :respondentType="selectedRespondentType"
                        @back="backToRespondents"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Styling untuk Tab Scrollable di Mobile */
.custom-scrollbar::-webkit-scrollbar {
    height: 3px; /* Scrollbar tipis */
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

/* Agar nav-tabs di mobile bisa di-swipe */
.nav-tabs {
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch; /* Smooth scroll di iOS */
    padding-bottom: 2px; /* Ruang untuk scrollbar */
}

/* Style active tab biar lebih pop */
.nav-link.active {
    border-bottom-color: #ffffff; /* Menyatu dengan card body */
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
