<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BaseButton from '@/Components/BaseButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import QuestionnaireInfoCard from './QuestionnaireInfoCard.vue';
import QuestionnaireQuestionTable from './QuestionnaireQuestionTable.vue';
import BaseModal from '@/Components/BaseModal.vue';
import QuestionnaireQuestionForm from './QuestionnaireQuestionForm.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import BaseAlert from '@/Components/BaseAlert.vue';

const props = defineProps({
    questionnaire: Object,
    questionCategories: {
        type: Array,
        default: () => [],
    },
});

const editingQuestion = ref(null);
const questionToDelete = ref(null);

// Computed property untuk mengecek apakah kuesioner sudah ada jawabannya
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
    if (!editingQuestion.value || form.order === null) {
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

const openAddModal = () => {
    editingQuestion.value = null;
    form.reset();
};

const openEditModal = (question) => {
    editingQuestion.value = question;
    form.id = question.id;
    form.question_text = question.question_text;
    form.question_type = question.question_type;
    form.category_id = question.category_id;
    form.is_required = !!question.is_required;
    form.order = question.order;
};

const confirmDelete = (question) => {
    questionToDelete.value = question;
};

const performDelete = () => {
    if (questionToDelete.value) {
        form.delete(route('questions.destroy', [props.questionnaire.id, questionToDelete.value.id]), {
            preserveScroll: true,
            onSuccess: () => {
                const modalElement = document.getElementById('confirm-delete-modal');
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
                questionToDelete.value = null;
                form.reset();
            },
        });
    }
};

const saveQuestion = () => {
    if (editingQuestion.value) {
        form.put(route('questions.update', [props.questionnaire.id, form.id]), {
            preserveScroll: true,
            onSuccess: () => {
                const modalElement = document.getElementById('question-form-modal');
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
                form.reset();
            },
        });
    } else {
        form.post(route('questions.store', props.questionnaire.id), {
            preserveScroll: true,
            onSuccess: () => {
                const modalElement = document.getElementById('question-form-modal');
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
                form.reset();
            },
        });
    }
};
</script>

<template>
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

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Daftar Pertanyaan</h3>
                <BaseButton variant="primary" data-bs-toggle="modal" data-bs-target="#question-form-modal"
                @click="openAddModal" v-if="!hasAnswers">
                    <i class="fa-solid fa-plus me-2"></i> Tambah Pertanyaan
                </BaseButton>
            </div>
        </div>
        <div class="card-body p-0">
            <QuestionnaireQuestionTable :questions="questionnaire.questions" :questionCategories="questionCategories"
                @edit="openEditModal" @delete-confirm="confirmDelete" :has-answers="hasAnswers" />
        </div>

        <BaseModal id="question-form-modal" :title="editingQuestion ? 'Edit Pertanyaan' : 'Tambah Pertanyaan Baru'"
            size="lg">
            <template #body>
                <form @submit.prevent="saveQuestion" id="question-form">
                    <QuestionnaireQuestionForm :form="form" :editingQuestion="editingQuestion"
                        :questionCategories="questionCategories" :editingOrderWarning="editingOrderWarning" />
                </form>
            </template>
            <template #footer>
                <BaseButton type="button" variant="secondary" data-bs-dismiss="modal">Batal</BaseButton>
                <BaseButton type="submit" variant="primary" :disabled="form.processing || hasAnswers"
                    form="question-form">
                    {{ editingQuestion ? 'Simpan Perubahan' : 'Tambah Pertanyaan' }}
                </BaseButton>
            </template>
        </BaseModal>

        <ConfirmModal id="confirm-delete-modal" title="Hapus Pertanyaan"
            :message="`Apakah Anda yakin ingin menghapus pertanyaan '${questionToDelete?.question_text}'? Aksi ini tidak dapat dibatalkan.`"
            confirm-text="Ya, Hapus" @confirm="performDelete" />
    </div>
</template>