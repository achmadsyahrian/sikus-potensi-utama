<script setup>
import { ref, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import BaseButton from '@/Components/BaseButton.vue';
import BaseTooltip from '@/Components/BaseTooltip.vue';
import BaseInput from '@/Components/BaseInput.vue';
import BaseAlert from '@/Components/BaseAlert.vue';

const props = defineProps({
    questionnaire: Object,
    allQuestions: Object,
});

const isDataSubmitted = ref(false);
const isSubmittedSuccessfully = ref(false); // Ref baru untuk melacak status sukses pengiriman
const showRespondentDataError = ref(false); // Ref baru untuk menampilkan error

const form = useForm({
    name: '',
    company_or_institution: '',
    contact_number: '',
    answers: Object.values(props.allQuestions).map(question => ({
        question_id: question.id,
        answer_value: '',
    })),
});

const currentCategoryIndex = ref(0);
const groupedSections = computed(() => {
    const sections = [];
    const allQuestionsArray = Object.values(props.allQuestions).sort((a, b) => a.order - b.order);

    const questionsWithoutCategory = allQuestionsArray.filter(q => q.category_id === null);
    if (questionsWithoutCategory && questionsWithoutCategory.length > 0) {
        sections.push({
            id: 'no-category',
            name: 'Tanpa Kategori',
            questions: questionsWithoutCategory,
        });
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

    const categorizedSections = Array.from(categoriesMap.values()).sort((a, b) => a.order - b.order);
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
    form.post(route('answers.store.external', props.questionnaire.id), {
        onSuccess: () => {
            isSubmittedSuccessfully.value = true;
        },
        onError: () => {
            // Logika untuk menampilkan pesan error jika terjadi kesalahan
        }
    });
};

const submitRespondentData = () => {
    if (!form.name || !form.company_or_institution || !form.contact_number) {
        showRespondentDataError.value = true;
        return;
    }
    showRespondentDataError.value = false;
    isDataSubmitted.value = true;
};
</script>

<template>
    <Head :title="`Isi Kuesioner: ${questionnaire.name}`" />

    <div class="page-body">
        <div class="container-xl">
            <div class="card card-stacked">
                <div class="card-status-top bg-primary"></div>
                <div class="card-header">
                    <div class="d-flex align-items-center w-100">
                        <div class="d-flex flex-column me-auto">
                            <h3 class="card-title mb-0">{{ questionnaire.name }}</h3>
                            <div class="text-muted small">Masa Akademik: {{ questionnaire.academic_period.name }}</div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <p class="text-muted">{{ questionnaire.description }}</p>
                </div>
                
                <template v-if="isSubmittedSuccessfully">
                    <div class="card-body text-center d-flex flex-column justify-content-center align-items-center">
                        <div class="mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-check text-success">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M5 12l5 5l10 -10"></path>
                            </svg>
                        </div>
                        <h3 class="card-title text-success">Kuesioner Berhasil Dikirim!</h3>
                        <p class="text-muted">Terima kasih atas partisipasi Anda. Jawaban Anda sudah berhasil kami terima.</p>
                    </div>
                </template>

                <template v-else-if="!isDataSubmitted">
                    <form @submit.prevent="submitRespondentData">
                        <div class="card-body border-top">
                            <BaseAlert v-if="showRespondentDataError" title="Gagal!" message="Mohon lengkapi semua data identitas responden." type="danger" class="mb-3" />
                            <h5 class="card-title text-primary">Informasi Responden</h5>
                            <p class="card-subtitle mb-4">Mohon lengkapi data diri Anda sebelum melanjutkan ke kuesioner.</p>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <BaseInput label="Nama Lengkap" type="text" v-model="form.name" required />
                                </div>
                                <div class="col-md-12">
                                    <BaseInput label="Asal Perusahaan/PT" type="text" v-model="form.company_or_institution" required />
                                </div>
                                <div class="col-md-12">
                                    <BaseInput label="Nomor Kontak" type="text" v-model="form.contact_number" required />
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end">
                            <BaseButton type="submit" label="Lanjut ke Kuesioner" variant="primary" />
                        </div>
                    </form>
                </template>

                <template v-else>
                    <form @submit.prevent="submitAnswer">
                        <div class="card-header d-flex align-items-center justify-content-between border-top">
                            <div class="text-start">
                                <h3 v-if="currentSection" class="card-title mb-0">{{ currentSection.name }}</h3>
                                <div class="text-muted small">Bagian {{ currentCategoryIndex + 1 }} dari {{ totalSections }}</div>
                            </div>
                            <div class="avatar avatar-md bg-azure-lt">
                                <span class="avatar-letter fs-3">{{ currentCategoryIndex + 1 }}</span>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-vcenter table-borderless">
                                    <thead class="bg-gray-lt">
                                        <tr>
                                            <th class="fw-bold text-dark fs-4 text-capitalize w-1">No.</th>
                                            <th class="fw-bold text-dark fs-4 text-capitalize">Daftar Pertanyaan</th>
                                            <th v-for="option in questionnaire.options" :key="option.id" class="text-center text-dark fw-bold fs-4 text-capitalize">{{ option.option_text }}</th>
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
                                                        <input v-model="form.answers.find(a => a.question_id === question.id).answer_value" class="form-check-input" type="radio" :name="`question_${question.id}`" :value="option.option_value" />
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
                                                    <textarea v-model="form.answers.find(a => a.question_id === question.id).answer_value" class="form-control" rows="3"></textarea>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <div class="d-flex justify-content-end">
                                <BaseButton type="button" variant="secondary" outline @click="prevSection" v-if="currentCategoryIndex > 0">
                                    Kembali
                                </BaseButton>

                                <div class="ms-2" v-if="currentCategoryIndex < totalSections - 1">
                                    <BaseTooltip title="Harap isi jawaban wajib" v-if="!isSectionValid">
                                        <BaseButton type="button" @click="nextSection" :disabled="!isSectionValid" label="Selanjutnya" />
                                    </BaseTooltip>
                                    <BaseButton type="button" @click="nextSection" v-else label="Selanjutnya" />
                                </div>

                                <div class="ms-2" v-if="currentCategoryIndex === totalSections - 1">
                                    <BaseTooltip title="Harap isi jawaban wajib" v-if="!isSectionValid">
                                        <BaseButton type="submit" :disabled="form.processing || !isSectionValid" label="Kirimkan" />
                                    </BaseTooltip>
                                    <BaseButton type="submit" :disabled="form.processing || !isSectionValid" v-else label="Kirimkan" />
                                </div>
                            </div>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </div>
</template>
