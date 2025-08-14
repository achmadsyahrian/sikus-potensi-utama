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
    // Tambahkan prop baru untuk mengecek status jawaban
    hasAnswers: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['edit', 'delete-confirm']);

// Mengelompokkan pertanyaan berdasarkan category_id
const groupedQuestions = computed(() => {
    const groups = {};

    // Inisialisasi grup untuk pertanyaan tanpa kategori
    groups[null] = {
        name: 'Tanpa Kategori',
        questions: []
    };

    // Inisialisasi grup untuk setiap kategori yang ada
    props.questionCategories.forEach(category => {
        groups[category.id] = {
            name: category.name,
            questions: []
        };
    });

    // Masukkan setiap pertanyaan ke grup yang sesuai
    props.questions.forEach(question => {
        const categoryId = question.category_id || null;
        if (groups[categoryId]) {
            groups[categoryId].questions.push(question);
        }
    });

    // Urutkan pertanyaan di dalam setiap grup berdasarkan kolom 'order'
    for (const categoryId in groups) {
        groups[categoryId].questions.sort((a, b) => a.order - b.order);
    }

    return groups;
});

const handleEdit = (question) => {
    emit('edit', question);
};

const handleDelete = (question) => {
    emit('delete-confirm', question);
};
</script>

<template>
    <div>
        <div v-for="(group, categoryId) in groupedQuestions" :key="categoryId" class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">{{ group.name }}</h3>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th class="w-1">Urutan</th>
                                <th class="w-auto">Pertanyaan</th>
                                <th class="w-auto">Tipe</th>
                                <th class="w-auto">Wajib</th>
                                <th class="w-1">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="group.questions.length > 0" v-for="(question, index) in group.questions" :key="question.id">
                                <td><div class="ms-3">{{ question.order }}</div></td>
                                <td>{{ question.question_text }}</td>
                                <td><span class="text-capitalize">{{ question.formatted_question_type }}</span></td>
                                <td>
                                    <span :class="question.is_required ? 'badge bg-success-lt' : 'badge bg-secondary-lt'">
                                        {{ question.is_required ? 'Ya' : 'Tidak' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <BaseButton variant="primary" outline class="btn-icon" size="sm" @click="handleEdit(question)" data-bs-toggle="modal" data-bs-target="#question-form-modal" :disabled="props.hasAnswers">
                                            <i class="fa-solid fa-pencil-alt"></i>
                                        </BaseButton>
                                        <BaseButton variant="danger" outline class="btn-icon" size="sm" @click="handleDelete(question)" data-bs-toggle="modal" data-bs-target="#confirm-delete-modal" :disabled="props.hasAnswers">
                                            <i class="fa-solid fa-trash"></i>
                                        </BaseButton>
                                    </div>
                                </td>
                            </tr>
                            <tr v-else>
                                <td colspan="5" class="text-center text-muted">Belum ada pertanyaan di kategori ini.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>