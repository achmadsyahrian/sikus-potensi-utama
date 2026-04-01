<script setup>
import { defineProps, computed } from 'vue';
import BaseButton from '@/Components/BaseButton.vue';

const props = defineProps({
    questions: {
        type: Array,
        default: () => [],
    },
    questionCategories: {
        type: Array,
        default: () => [],
    },
    hasAnswers: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['edit', 'delete-confirm']);

const groupedQuestions = computed(() => {
    const groups = {};

    groups[null] = {
        name: 'Tanpa Kategori',
        questions: []
    };

    props.questionCategories.forEach(category => {
        groups[category.id] = {
            name: category.name,
            questions: []
        };
    });

    props.questions.forEach(question => {
        const categoryId = question.category_id || null;
        if (groups[categoryId]) {
            groups[categoryId].questions.push(question);
        }
    });

    for (const categoryId in groups) {
        groups[categoryId].questions.sort((a, b) => a.order - b.order);
    }

    return Object.fromEntries(
        Object.entries(groups).filter(([_, group]) => group.name !== 'Tanpa Kategori' || group.questions.length > 0)
    );
});

const handleEdit = (question) => {
    emit('edit', question);
};

const handleDelete = (question) => {
    emit('delete-confirm', question);
};
</script>

<template>
    <div class="row g-3">
        <div v-for="(group, categoryId) in groupedQuestions" :key="categoryId" class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light-lt py-2">
                    <h4 class="card-title text-primary mb-0"><i class="fa-solid fa-layer-group me-2"></i>{{ group.name }}</h4>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter text-nowrap datatable">
                            <thead>
                                <tr>
                                    <th class="w-1 text-center">No</th>
                                    <th>Pertanyaan</th>
                                    <th>Tipe</th>
                                    <th class="text-center">Wajib</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="group.questions.length > 0" v-for="question in group.questions" :key="question.id">
                                    <td class="text-center text-muted">{{ question.order }}</td>
                                    <td class="text-wrap" style="min-width: 300px;">
                                        <span class="d-block text-truncate" style="max-width: 500px;" :title="question.question_text">
                                            {{ question.question_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-azure-lt text-capitalize">{{ question.formatted_question_type || 'Pilihan Ganda' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <i v-if="question.is_required" class="fa-solid fa-check text-success"></i>
                                        <i v-else class="fa-solid fa-minus text-secondary"></i>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-end">
                                            <BaseButton variant="info" outline class="btn-icon" size="sm" @click="handleEdit(question)" :disabled="props.hasAnswers">
                                                <i class="fa-solid fa-pencil-alt"></i>
                                            </BaseButton>
                                            <BaseButton variant="danger" outline class="btn-icon" size="sm" @click="handleDelete(question)" data-bs-toggle="modal" data-bs-target="#confirm-delete-modal" :disabled="props.hasAnswers">
                                                <i class="fa-solid fa-trash"></i>
                                            </BaseButton>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-else>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="fa-solid fa-inbox fs-2 mb-2 d-block"></i>
                                        Belum ada pertanyaan di kategori ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
