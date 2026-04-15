<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import BaseButton from '@/Components/BaseButton.vue';
import QuestionnaireInfoCard from './Partials/QuestionnaireInfoCard.vue';
import QuestionnaireQuestionTable from './Partials/QuestionnaireQuestionTable.vue';
import QuestionnaireQuestionForm from './Partials/QuestionnaireQuestionForm.vue';
import QuestionnaireSidebarTabs from './Partials/QuestionnaireSidebarTabs.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import BaseAlert from '@/Components/BaseAlert.vue';

const props = defineProps({
    questionnaire: Object,
    questionCategories: Array,
});

const isFormOpen      = ref(false);
const editingQuestion = ref(null);
const questionToDelete = ref(null);

const hasAnswers = computed(() => props.questionnaire.total_answers > 0);

const form = useForm({
    id: null,
    question_text: '',
    question_type: 'multiple_choice',
    category_id: null,
    is_required: true,
    order: null,
});

const editingOrderWarning = computed(() => {
    if (!isFormOpen.value || form.order === null) return null;
    const otherQuestions = props.questionnaire.questions.filter(q => q.id !== form.id && q.category_id === form.category_id);
    const conflict = otherQuestions.find(q => q.order === form.order);
    return conflict ? `Urutan ini sudah digunakan. Jika disimpan, urutan pertanyaan '${conflict.question_text}' akan ditukar.` : null;
});

const startAdd = () => {
    editingQuestion.value = null;
    form.reset();
    isFormOpen.value = true;
};

const startEdit = (question) => {
    editingQuestion.value = question;
    form.id            = question.id;
    form.question_text = question.question_text;
    form.question_type = question.question_type;
    form.category_id   = question.category_id;
    form.is_required   = !!question.is_required;
    form.order         = question.order;
    isFormOpen.value   = true;
};

const cancelForm = () => {
    isFormOpen.value      = false;
    editingQuestion.value = null;
    form.reset();
};

const confirmDelete = (question) => {
    questionToDelete.value = question;
};

const performDelete = () => {
    if (!questionToDelete.value) return;
    form.delete(route('questions.destroy', [props.questionnaire.id, questionToDelete.value.id]), {
        preserveScroll: true,
        onSuccess: () => {
            const modalEl = document.getElementById('confirm-delete-modal');
            if (modalEl) bootstrap.Modal.getInstance(modalEl)?.hide();
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
            document.body.style.removeProperty('overflow');
            questionToDelete.value = null;
            form.reset();
        },
    });
};

const saveQuestion = () => {
    const submitRoute  = editingQuestion.value
        ? route('questions.update', [props.questionnaire.id, form.id])
        : route('questions.store', props.questionnaire.id);
    const submitMethod = editingQuestion.value ? 'put' : 'post';
    form[submitMethod](submitRoute, {
        preserveScroll: true,
        onSuccess: () => cancelForm(),
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
            v-if="hasAnswers"
            type="warning"
            title="Kuesioner Telah Dijawab"
            message="Kuesioner ini sudah memiliki jawaban. Pertanyaan tidak dapat diubah, ditambah, atau dihapus untuk menjaga konsistensi data."
            class="mb-4"
        />

        <div class="row g-4">
            <QuestionnaireSidebarTabs :questionnaire="questionnaire" />

            <div class="col-12 col-md-9 col-lg-10">
                <QuestionnaireInfoCard :questionnaire="questionnaire" />

                <!-- Form tambah/edit pertanyaan -->
                <div v-if="isFormOpen" class="card border-0 shadow-sm border-start border-primary border-3 mt-4">
                    <div class="card-header border-0 bg-primary-lt">
                        <h3 class="card-title text-primary">
                            <i class="fa-solid fa-pen-to-square me-2"></i>
                            {{ editingQuestion ? 'Edit Pertanyaan' : 'Tambah Pertanyaan Baru' }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="saveQuestion" id="question-form">
                            <QuestionnaireQuestionForm
                                :form="form"
                                :editingQuestion="editingQuestion"
                                :questionCategories="questionCategories"
                                :editingOrderWarning="editingOrderWarning"
                            />
                        </form>
                    </div>
                    <div class="card-footer border-0 d-flex justify-content-end gap-2">
                        <BaseButton type="button" variant="secondary" outline @click="cancelForm">Batal</BaseButton>
                        <BaseButton type="submit" variant="primary" :disabled="form.processing || hasAnswers" form="question-form">
                            {{ editingQuestion ? 'Simpan Perubahan' : 'Tambah Pertanyaan' }}
                        </BaseButton>
                    </div>
                </div>

                <!-- Tabel pertanyaan -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header border-0 d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="card-title fw-bold">Manajemen Pertanyaan</h3>
                            <p class="card-subtitle text-muted small">Kelola daftar pertanyaan, tipe, dan urutannya.</p>
                        </div>
                        <BaseButton variant="primary" @click="startAdd" v-if="!hasAnswers && !isFormOpen">
                            <i class="fa-solid fa-plus me-2"></i> Tambah Pertanyaan
                        </BaseButton>
                    </div>
                    <div class="card-body">
                        <QuestionnaireQuestionTable
                            :questions="questionnaire.questions"
                            :questionCategories="questionCategories"
                            @edit="startEdit"
                            @delete-confirm="confirmDelete"
                            :has-answers="hasAnswers"
                        />
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            id="confirm-delete-modal"
            title="Hapus Pertanyaan"
            :message="`Apakah Anda yakin ingin menghapus pertanyaan '${questionToDelete?.question_text}'? Aksi ini tidak dapat dibatalkan.`"
            confirm-text="Ya, Hapus"
            @confirm="performDelete"
        />
    </AuthenticatedLayout>
</template>
