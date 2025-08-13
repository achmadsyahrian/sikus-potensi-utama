<script setup>
import { ref, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseTooltip from '@/Components/BaseTooltip.vue';
import { defineProps } from 'vue';

const props = defineProps({
    questionnaire: Object,
});

const form = useForm({
    answers: props.questionnaire.categories
        .flatMap(category => category.questions)
        .map(question => ({
            question_id: question.id,
            answer_value: '',
        })),
});

const currentCategoryIndex = ref(0);

const sortedCategories = computed(() => {
    return props.questionnaire.categories.slice().sort((a, b) => a.order - b.order);
});

const currentCategory = computed(() => {
    return sortedCategories.value[currentCategoryIndex.value];
});

const currentQuestions = computed(() => {
    return currentCategory.value.questions.slice().sort((a, b) => a.order - b.order);
});

const totalCategories = computed(() => {
    return sortedCategories.value.length;
});

// Computed property untuk validasi apakah semua pertanyaan wajib di kategori saat ini sudah diisi
const isCategoryValid = computed(() => {
    const requiredQuestions = currentQuestions.value.filter(q => q.is_required);

    // Memastikan setiap pertanyaan wajib memiliki jawaban yang tidak kosong
    const allRequiredAnswered = requiredQuestions.every(q => {
        const answer = form.answers.find(a => a.question_id === q.id);
        return answer && answer.answer_value !== '';
    });

    return allRequiredAnswered;
});

const nextCategory = () => {
    currentCategoryIndex.value++;
};

const prevCategory = () => {
    if (currentCategoryIndex.value > 0) {
        currentCategoryIndex.value--;
    }
};

const submitAnswer = () => {
    form.post(route('answers.store', props.questionnaire.id), {
        onSuccess: () => {},
    });
};
</script>

<template>

    <Head :title="`Isi Kuesioner: ${questionnaire.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Kuesioner
                    </h2>
                    <div class="page-pretitle">
                        Isian Kuesioner
                    </div>
                </div>
            </div>
        </template>

        <div class="container-xl">
            <div class="card">
                <div class="card-status-top bg-primary"></div>
                <div class="card-header">
                    <div class="d-flex align-items-center w-100">
                        <div class="d-flex flex-column me-auto">
                            <h3 class="card-title mb-0">{{ questionnaire.name }}</h3>
                            <div class="text-muted small">Masa Akademik: {{ questionnaire.academic_period.name }}</div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="text-end me-3">
                                <h3 class="card-title mb-0">{{ currentCategory.name }}</h3>
                                <div class="text-muted small">Bagian {{ currentCategoryIndex + 1 }} dari {{
                                    totalCategories }}
                                </div>
                            </div>
                            <div class="avatar avatar-md bg-azure-lt">
                                <span class="avatar-letter fs-3">{{ currentCategoryIndex + 1 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">{{ questionnaire.description }}</p>
                </div>
            </div>

            <form @submit.prevent="submitAnswer">
                <div class="card my-3">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-vcenter table-borderless">
                                <thead class="bg-gray-lt">
                                    <tr>
                                        <th class="fw-bold text-dark fs-4 text-capitalize w-1">No.</th>
                                        <th class="fw-bold text-dark fs-4 text-capitalize">Daftar Pertanyaan</th>
                                        <th v-for="option in questionnaire.options" :key="option.id"
                                            class="text-center text-dark fw-bold fs-4 text-capitalize">{{
                                                option.option_text }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="(question, index) in currentQuestions" :key="question.id">
                                        <tr v-if="question.question_type === 'multiple_choice'">
                                            <td>{{ index + 1 }}.</td>
                                            <td>
                                                {{ question.question_text }}
                                                <span v-if="question.is_required" class="text-danger">*</span>
                                            </td>
                                            <td v-for="option in questionnaire.options" :key="option.id"
                                                class="text-center">
                                                <div class="form-check m-0 d-inline-flex mb-2">
                                                    <input
                                                        v-model="form.answers.find(a => a.question_id === question.id).answer_value"
                                                        class="form-check-input" type="radio"
                                                        :name="`question_${question.id}`"
                                                        :value="option.option_value" />
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="question.question_type === 'text'">
                                            <td>{{ index + 1 }}.</td>
                                            <td>
                                                {{ question.question_text }}
                                                <span v-if="question.is_required" class="text-danger">*</span>
                                            </td>
                                            <td :colspan="questionnaire.options.length" class="text-center">
                                                <textarea
                                                    v-model="form.answers.find(a => a.question_id === question.id).answer_value"
                                                    class="form-control" rows="3"></textarea>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end">
                            <!-- Tombol Kembali -->
                            <BaseButton type="button" variant="secondary" outline @click="prevCategory"
                                v-if="currentCategoryIndex > 0">
                                Kembali
                            </BaseButton>

                            <!-- Tombol Selanjutnya -->
                            <div class="ms-2" v-if="currentCategoryIndex < totalCategories - 1">
                                <BaseTooltip title="Harap isi jawaban wajib" v-if="!isCategoryValid">
                                    <BaseButton type="button" @click="nextCategory" :disabled="!isCategoryValid"
                                        label="Selanjutnya" />
                                </BaseTooltip>
                                <BaseButton type="button" @click="nextCategory" v-else label="Selanjutnya" />
                            </div>

                            <!-- Tombol Kirimkan -->
                            <div class="ms-2" v-if="currentCategoryIndex === totalCategories - 1">
                                <BaseTooltip title="Harap isi jawaban wajib" v-if="!isCategoryValid">
                                    <BaseButton type="submit" @click="submitAnswer"
                                        :disabled="form.processing || !isCategoryValid" label="Kirimkan" />
                                </BaseTooltip>
                                <BaseButton type="submit" @click="submitAnswer"
                                    :disabled="form.processing || !isCategoryValid" v-else label="Kirimkan" />
                            </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </AuthenticatedLayout>
</template>
