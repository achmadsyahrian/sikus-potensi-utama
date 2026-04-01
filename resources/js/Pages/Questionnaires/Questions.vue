<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import BaseButton from '@/Components/BaseButton.vue';
import QuestionnaireInfoCard from './Partials/QuestionnaireInfoCard.vue';
import QuestionnaireQuestionTable from './Partials/QuestionnaireQuestionTable.vue';
import QuestionnaireQuestionForm from './Partials/QuestionnaireQuestionForm.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import BaseAlert from '@/Components/BaseAlert.vue';

const props = defineProps({
    questionnaire: Object,
    questionCategories: Array,
});

const isFormOpen = ref(false);
const editingQuestion = ref(null);
const questionToDelete = ref(null);

const hasAnswers = computed(() => {
    return props.questionnaire.total_answers > 0;
});

const form = useForm({
    id: null,
    question_text: '',
    question_type: 'multiple_choice',
    category_id: null,
    is_required: true,
    order: null,
});

const editingOrderWarning = computed(() => {
    if (!isFormOpen.value || form.order === null) {
        return null;
    }

    const otherQuestionsInSameCategory = props.questionnaire.questions.filter(q =>
        q.id !== form.id && q.category_id === form.category_id
    );

    const conflictingQuestion = otherQuestionsInSameCategory.find(q =>
        q.order === form.order
    );

    if (conflictingQuestion) {
        return `Urutan ini sudah digunakan. Jika disimpan, urutan pertanyaan '${conflictingQuestion.question_text}' akan ditukar.`;
    }

    return null;
});

const startAdd = () => {
    editingQuestion.value = null;
    form.reset();
    isFormOpen.value = true;
};

const startEdit = (question) => {
    editingQuestion.value = question;
    form.id = question.id;
    form.question_text = question.question_text;
    form.question_type = question.question_type;
    form.category_id = question.category_id;
    form.is_required = !!question.is_required;
    form.order = question.order;
    isFormOpen.value = true;
};

const cancelForm = () => {
    isFormOpen.value = false;
    editingQuestion.value = null;
    form.reset();
};

const confirmDelete = (question) => {
    questionToDelete.value = question;
};

const performDelete = () => {
    if (questionToDelete.value) {
        form.delete(route('questions.destroy', [props.questionnaire.id, questionToDelete.value.id]), {
            preserveScroll: true,
            onSuccess: () => {
                const modalEl = document.getElementById('confirm-delete-modal');
                if (modalEl) {
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                }
                document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.body.style.removeProperty('overflow');

                questionToDelete.value = null;
                form.reset();
            },
        });
    }
};

const saveQuestion = () => {
    const submitRoute = editingQuestion.value
        ? route('questions.update', [props.questionnaire.id, form.id])
        : route('questions.store', props.questionnaire.id);

    const submitMethod = editingQuestion.value ? 'put' : 'post';

    form[submitMethod](submitRoute, {
        preserveScroll: true,
        onSuccess: () => {
            cancelForm();
        },
    });
};
</script>

<template>
    <Head :title="`Pertanyaan Kuesioner: ${questionnaire.name}`" />
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
                        <Link :href="route('questionnaires.categories', questionnaire.id)" class="nav-link text-nowrap">
                            <i class="fa-solid fa-layer-group me-2" style="opacity: 0.6"></i> Kategori
                        </Link>
                    </li>
                    <li class="nav-item">
                        <Link :href="route('questionnaires.options', questionnaire.id)" class="nav-link text-nowrap">
                            <i class="fa-solid fa-list-check me-2" style="opacity: 0.6"></i> Opsi Jawaban
                        </Link>
                    </li>
                    <li class="nav-item">
                        <Link :href="route('questionnaires.questions', questionnaire.id)" class="nav-link active fw-bold text-nowrap">
                            <i class="fa-solid fa-clipboard-question me-2"></i> Pertanyaan
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
                        <h3 class="fw-bold mb-1">Manajemen Pertanyaan</h3>
                        <h5 class="op-7 mb-2 text-muted">Kelola daftar pertanyaan, tipe, dan urutannya.</h5>
                    </div>
                </div>

                <BaseAlert v-if="hasAnswers" type="warning" title="Kuesioner Telah Dijawab"
                    message="Kuesioner ini sudah memiliki jawaban. Pertanyaan tidak dapat diubah, ditambah, atau dihapus untuk menjaga konsistensi data."
                    class="mb-4" />

                <div v-if="isFormOpen" class="card border-primary mb-4 shadow-sm">
                    <div class="card-header bg-primary-lt">
                        <h3 class="card-title text-primary">
                            <i class="fa-solid fa-pen-to-square me-2"></i>
                            {{ editingQuestion ? 'Edit Pertanyaan' : 'Tambah Pertanyaan Baru' }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="saveQuestion" id="question-form">
                            <QuestionnaireQuestionForm :form="form" :editingQuestion="editingQuestion"
                                :questionCategories="questionCategories" :editingOrderWarning="editingOrderWarning" />
                        </form>
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-2">
                        <BaseButton type="button" variant="secondary" outline @click="cancelForm">Batal</BaseButton>
                        <BaseButton type="submit" variant="primary" :disabled="form.processing || hasAnswers" form="question-form">
                            {{ editingQuestion ? 'Simpan Perubahan' : 'Tambah Pertanyaan' }}
                        </BaseButton>
                    </div>
                </div>

                <div v-if="!isFormOpen">
                    <div class="d-flex justify-content-end mb-3">
                        <BaseButton variant="primary" @click="startAdd" v-if="!hasAnswers">
                            <i class="fa-solid fa-plus me-2"></i> Tambah Pertanyaan
                        </BaseButton>
                    </div>

                    <QuestionnaireQuestionTable :questions="questionnaire.questions" :questionCategories="questionCategories"
                        @edit="startEdit" @delete-confirm="confirmDelete" :has-answers="hasAnswers" />
                </div>

                <ConfirmModal id="confirm-delete-modal" title="Hapus Pertanyaan"
                    :message="`Apakah Anda yakin ingin menghapus pertanyaan '${questionToDelete?.question_text}'? Aksi ini tidak dapat dibatalkan.`"
                    confirm-text="Ya, Hapus" @confirm="performDelete" />
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
