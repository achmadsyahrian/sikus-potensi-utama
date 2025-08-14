<script setup>
import { ref, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseTooltip from '@/Components/BaseTooltip.vue';
import BaseInput from '@/Components/BaseInput.vue';

const props = defineProps({
    questionnaire: Object,
    roles: Array,
    allQuestions: Object, // Prop allQuestions sekarang adalah Object (Collection)
});

// Perbaikan: Kita tidak lagi butuh prop categories dari questionnaire
// Karena sudah ada di allQuestions.
const form = useForm({
    answers: Object.values(props.allQuestions).map(question => ({
        question_id: question.id,
        answer_value: '',
    })),
});

const currentCategoryIndex = ref(0);

const groupedSections = computed(() => {
    const sections = [];
    const allQuestionsArray = Object.values(props.allQuestions).sort((a, b) => a.order - b.order);

    // Filter pertanyaan tanpa kategori
    const questionsWithoutCategory = allQuestionsArray.filter(q => q.category_id === null);

    if (questionsWithoutCategory && questionsWithoutCategory.length > 0) {
        sections.push({
            id: 'no-category',
            name: 'Tanpa Kategori',
            questions: questionsWithoutCategory,
        });
    }

    // Kelompokkan pertanyaan berdasarkan kategori
    const categoriesMap = new Map();
    allQuestionsArray.forEach(q => {
        if (q.category_id !== null) {
            if (!categoriesMap.has(q.category_id)) {
                categoriesMap.set(q.category_id, {
                    id: q.category.id,
                    name: q.category.name,
                    order: q.category.order,
                    questions: [],
                });
            }
            categoriesMap.get(q.category_id).questions.push(q);
        }
    });

    const categorizedSections = Array.from(categoriesMap.values()).sort((a, b) => a.order - b.order);

    // Gabungkan pertanyaan tanpa kategori dan berkategori
    sections.push(...categorizedSections);

    return sections;
});

const currentSection = computed(() => {
    return groupedSections.value[currentCategoryIndex.value];
});

const totalSections = computed(() => {
    return groupedSections.value.length;
});

const isSectionValid = computed(() => {
    if (!currentSection.value) return true;

    const requiredQuestions = currentSection.value.questions.filter(q => q.is_required);

    const allRequiredAnswered = requiredQuestions.every(q => {
        const answer = form.answers.find(a => a.question_id === q.id);

        // Perbaikan di sini: Cek apakah answer_value ada dan tidak kosong
        // Menggunakan double bang (!!) untuk memeriksa keberadaan nilai (truthiness)
        // Ini bekerja untuk string, angka, dan nilai lainnya yang dianggap tidak kosong.
        // answer.answer_value juga bisa 0, jadi kita harus memastikan itu tidak termasuk
        // string kosong.

        // Versi yang lebih sederhana:
        // return answer && answer.answer_value !== null && answer.answer_value !== '';

        // Versi yang lebih fleksibel dan aman:
        return !!answer?.answer_value || answer?.answer_value === 0;

    });

    return allRequiredAnswered;
});

const nextSection = () => {
    currentCategoryIndex.value++;
};

const prevSection = () => {
    if (currentCategoryIndex.value > 0) {
        currentCategoryIndex.value--;
    }
};

const submitAnswer = () => {
    form.post(route('answers.store', props.questionnaire.id), {
        onSuccess: () => { },
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
            <template v-if="totalSections > 0">
                <div class="card">
                    <div class="card-status-top bg-primary"></div>
                    <div class="card-header">
                        <div class="d-flex align-items-center w-100">
                            <div class="d-flex flex-column me-auto">
                                <h3 class="card-title mb-0">{{ questionnaire.name }}</h3>
                                <div class="text-muted small">Masa Akademik: {{ questionnaire.academic_period.name }}
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="text-end me-3">
                                    <h3 v-if="currentSection" class="card-title mb-0">{{ currentSection.name }}</h3>
                                    <div class="text-muted small">Bagian {{ currentCategoryIndex + 1 }} dari {{
                                        totalSections }}</div>
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
                                        <template v-for="(question, index) in currentSection.questions"
                                            :key="question.id">
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
                                <BaseButton type="button" variant="secondary" outline @click="prevSection"
                                    v-if="currentCategoryIndex > 0">
                                    Kembali
                                </BaseButton>

                                <div class="ms-2" v-if="currentCategoryIndex < totalSections - 1">
                                    <BaseTooltip title="Harap isi jawaban wajib" v-if="!isSectionValid">
                                        <BaseButton type="button" @click="nextSection" :disabled="!isSectionValid"
                                            label="Selanjutnya" />
                                    </BaseTooltip>
                                    <BaseButton type="button" @click="nextSection" v-else label="Selanjutnya" />
                                </div>

                                <div class="ms-2" v-if="currentCategoryIndex === totalSections - 1">
                                    <BaseTooltip title="Harap isi jawaban wajib" v-if="!isSectionValid">
                                        <BaseButton type="submit" :disabled="form.processing || !isSectionValid"
                                            label="Kirimkan" />
                                    </BaseTooltip>
                                    <BaseButton type="submit" :disabled="form.processing || !isSectionValid" v-else
                                        label="Kirimkan" />
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </template>
            <template v-else>
                <div class="alert alert-info">
                    <div class="alert-title">Tidak ada pertanyaan</div>
                    <div class="text-muted">Kuesioner ini belum memiliki pertanyaan untuk diisi.</div>
                </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>