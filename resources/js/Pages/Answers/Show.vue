<script setup>
import { ref, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseTooltip from '@/Components/BaseTooltip.vue';

const props = defineProps({
    questionnaire: Object,
    allQuestions: Object,
    availableProdi: { type: Array, default: () => [] },
    isDosen: { type: Boolean, default: false },
});

// Step: 'select-prodi' | 'fill'
const step = ref(props.isDosen && props.availableProdi.length > 0 ? 'select-prodi' : 'fill');
const selectedProdi = ref(null);

const unfilledProdi = computed(() => props.availableProdi.filter(p => !p.is_filled));

const selectProdi = (prodi) => {
    selectedProdi.value = prodi;
    step.value = 'fill';
};

const form = useForm({
    answers: Object.values(props.allQuestions).map(question => ({
        question_id: question.id,
        answer_value: '',
    })),
    lecturer_program_study_code: null,
});

const currentCategoryIndex = ref(0);

const groupedSections = computed(() => {
    const sections = [];
    const allQuestionsArray = Object.values(props.allQuestions).sort((a, b) => a.order - b.order);
    const questionsWithoutCategory = allQuestionsArray.filter(q => q.category_id === null);

    if (questionsWithoutCategory.length > 0) {
        sections.push({ id: 'no-category', name: 'Tanpa Kategori', questions: questionsWithoutCategory });
    }

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

    sections.push(...Array.from(categoriesMap.values()).sort((a, b) => a.order - b.order));
    return sections;
});

const currentSection  = computed(() => groupedSections.value[currentCategoryIndex.value]);
const totalSections   = computed(() => groupedSections.value.length);

const isSectionValid = computed(() => {
    if (!currentSection.value) return true;
    return currentSection.value.questions
        .filter(q => q.is_required)
        .every(q => {
            const answer = form.answers.find(a => a.question_id === q.id);
            return !!answer?.answer_value || answer?.answer_value === 0;
        });
});

const nextSection = () => currentCategoryIndex.value++;
const prevSection = () => { if (currentCategoryIndex.value > 0) currentCategoryIndex.value--; };

const submitAnswer = () => {
    if (selectedProdi.value) {
        form.lecturer_program_study_code = selectedProdi.value.id_program_studi;
    }
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
                    <h2 class="page-title">Kuesioner</h2>
                    <div class="page-pretitle">Isian Kuesioner</div>
                </div>
            </div>
        </template>

        <div class="container-xl">

            <!-- Step 1: Pilih Prodi (khusus Dosen) -->
            <template v-if="step === 'select-prodi'">
                <div class="card">
                    <div class="card-status-top bg-teal"></div>
                    <div class="card-header">
                        <div>
                            <h3 class="card-title text-teal">
                                <i class="fa-solid fa-chalkboard-user me-2"></i>
                                Pilih Program Studi
                            </h3>
                            <p class="card-subtitle text-muted small">
                                Anda akan mengisi kuesioner <strong>{{ questionnaire.name }}</strong> sebagai dosen untuk program studi berikut.
                                Pilih salah satu yang belum diisi.
                            </p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div v-for="prodi in availableProdi" :key="prodi.id_program_studi" class="col-12 col-sm-6 col-lg-4">
                                <div
                                    class="card card-sm border-2 h-100"
                                    :class="prodi.is_filled ? 'border-success opacity-75' : 'border-teal cursor-pointer card-hover'"
                                    @click="!prodi.is_filled && selectProdi(prodi)"
                                >
                                    <div class="card-body d-flex align-items-center gap-3">
                                        <div>
                                            <span
                                                class="avatar avatar-md rounded"
                                                :class="prodi.is_filled ? 'bg-success-lt' : 'bg-teal-lt'"
                                            >
                                                <i
                                                    class="fa-solid"
                                                    :class="prodi.is_filled ? 'fa-circle-check text-success' : 'fa-building-columns text-teal'"
                                                ></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">{{ prodi.program_studi }}</div>
                                            <div class="text-muted small">
                                                <span v-if="prodi.degree_level" class="badge bg-azure-lt text-azure me-1">{{ prodi.degree_level }}</span>
                                                {{ prodi.id_program_studi }}
                                            </div>
                                        </div>
                                        <div>
                                            <span v-if="prodi.is_filled" class="badge bg-success-lt text-success">Sudah Diisi</span>
                                            <i v-else class="fa-solid fa-chevron-right text-teal"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Step 2: Isi Kuesioner -->
            <template v-if="step === 'fill'">
                <!-- Info prodi yang dipilih (khusus dosen) -->
                <div v-if="isDosen && selectedProdi" class="alert alert-info mb-3 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-info"></i>
                    Anda mengisi sebagai dosen Program Studi
                    <strong>{{ selectedProdi.program_studi }}</strong>
                    <span v-if="selectedProdi.degree_level" class="badge bg-azure-lt text-azure ms-1">{{ selectedProdi.degree_level }}</span>
                    <button class="btn btn-sm btn-ghost-secondary ms-auto" @click="step = 'select-prodi'">
                        <i class="fa-solid fa-arrow-left me-1"></i> Ganti Prodi
                    </button>
                </div>

                <template v-if="totalSections > 0">
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
                                        <h3 v-if="currentSection" class="card-title mb-0">{{ currentSection.name }}</h3>
                                        <div class="text-muted small">Bagian {{ currentCategoryIndex + 1 }} dari {{ totalSections }}</div>
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
                                                <th v-for="option in questionnaire.options" :key="option.id" class="text-center text-dark fw-bold fs-4 text-capitalize">
                                                    {{ option.option_text }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-for="(question, index) in currentSection.questions" :key="question.id">
                                                <tr v-if="question.question_type === 'multiple_choice'">
                                                    <td>{{ index + 1 }}.</td>
                                                    <td>
                                                        {{ question.question_text }}
                                                        <span v-if="question.is_required" class="text-danger">*</span>
                                                    </td>
                                                    <td v-for="option in questionnaire.options" :key="option.id" class="text-center">
                                                        <div class="form-check m-0 d-inline-flex mb-2">
                                                            <input
                                                                v-model="form.answers.find(a => a.question_id === question.id).answer_value"
                                                                class="form-check-input"
                                                                type="radio"
                                                                :name="`question_${question.id}`"
                                                                :value="option.option_value"
                                                            />
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
                                                            class="form-control"
                                                            rows="3"
                                                        ></textarea>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-end gap-2">
                                    <BaseButton type="button" variant="secondary" outline @click="prevSection" v-if="currentCategoryIndex > 0">
                                        Kembali
                                    </BaseButton>
                                    <div v-if="currentCategoryIndex < totalSections - 1">
                                        <BaseTooltip title="Harap isi jawaban wajib" v-if="!isSectionValid">
                                            <BaseButton type="button" @click="nextSection" :disabled="!isSectionValid" label="Selanjutnya" />
                                        </BaseTooltip>
                                        <BaseButton type="button" @click="nextSection" v-else label="Selanjutnya" />
                                    </div>
                                    <div v-if="currentCategoryIndex === totalSections - 1">
                                        <BaseTooltip title="Harap isi jawaban wajib" v-if="!isSectionValid">
                                            <BaseButton type="submit" :disabled="form.processing || !isSectionValid" label="Kirimkan" />
                                        </BaseTooltip>
                                        <BaseButton type="submit" :disabled="form.processing || !isSectionValid" v-else label="Kirimkan" />
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
            </template>
        </div>
    </AuthenticatedLayout>
</template>
