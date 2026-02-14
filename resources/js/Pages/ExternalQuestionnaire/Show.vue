<script setup>
import { ref, computed } from 'vue';
import { useForm, Head, usePage } from '@inertiajs/vue3';
import BaseButton from '@/Components/BaseButton.vue';
import BaseTooltip from '@/Components/BaseTooltip.vue';
import BaseInput from '@/Components/BaseInput.vue';
import BaseAlert from '@/Components/BaseAlert.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue'; // Import Logo

const props = defineProps({
    questionnaire: Object,
    allQuestions: Object,
});

const page = usePage();
const appName = computed(() => page.props.app_name || 'Sistem Kuesioner');

const isDataSubmitted = ref(false);
const isSubmittedSuccessfully = ref(false);
const showRespondentDataError = ref(false);

const form = useForm({
    role: '', // Field Role Baru
    name: '',
    company_or_institution: '',
    contact_number: '',
    answers: Object.values(props.allQuestions).map(question => ({
        question_id: question.id,
        answer_value: '',
    })),
});

// --- LOGIKA KATEGORI & NAVIGASI ---
const currentCategoryIndex = ref(0);

const groupedSections = computed(() => {
    const sections = [];
    const allQuestionsArray = Object.values(props.allQuestions).sort((a, b) => a.order - b.order);

    // 1. Pertanyaan Tanpa Kategori
    const questionsWithoutCategory = allQuestionsArray.filter(q => q.category_id === null);
    if (questionsWithoutCategory.length > 0) {
        sections.push({
            id: 'no-category',
            name: 'Pertanyaan Umum',
            questions: questionsWithoutCategory,
        });
    }

    // 2. Pertanyaan Dengan Kategori
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

const currentSection = computed(() => groupedSections.value[currentCategoryIndex.value]);
const totalSections = computed(() => groupedSections.value.length);

// --- VALIDASI ---
const isSectionValid = computed(() => {
    if (!currentSection.value) return true;
    const requiredQuestions = currentSection.value.questions.filter(q => q.is_required);
    return requiredQuestions.every(q => {
        const answer = form.answers.find(a => a.question_id === q.id);
        // Cek apakah ada isinya (string tidak kosong atau angka 0)
        return (answer?.answer_value !== null && answer?.answer_value !== '');
    });
});

const nextSection = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
    currentCategoryIndex.value++;
};

const prevSection = () => {
    if (currentCategoryIndex.value > 0) {
        currentCategoryIndex.value--;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

// --- SUBMISSION LOGIC ---
const submitRespondentData = () => {
    // Validasi Role
    if (!form.role) {
        showRespondentDataError.value = true;
        return;
    }

    // Validasi Perusahaan (Wajib jika BUKAN Alumni)
    if (form.role !== 'alumni' && !form.company_or_institution) {
        showRespondentDataError.value = true;
        return;
    }

    // Validasi Umum
    if (!form.name || !form.contact_number) {
        showRespondentDataError.value = true;
        return;
    }

    showRespondentDataError.value = false;
    isDataSubmitted.value = true; // Pindah ke step berikutnya
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const submitAnswer = () => {
    form.post(route('answers.store.external', props.questionnaire.id), {
        preserveScroll: true,
        onSuccess: () => {
            isSubmittedSuccessfully.value = true;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        onError: () => {
            alert('Terjadi kesalahan saat mengirim jawaban. Silakan coba lagi.');
        }
    });
};
</script>

<template>
    <Head :title="questionnaire.name" />

    <div class="page page-center bg-light min-vh-100 py-5">
        <div class="container container-tight py-4">

            <div class="text-center mb-4">
                <a href="#" class="navbar-brand navbar-brand-autodark">
                    <ApplicationLogo class="mx-auto mb-2" style="height: 60px; width: auto;" />
                </a>
                <h2 class="h2 text-primary mb-1">{{ appName }}</h2>
                <div class="text-muted">Portal Kuesioner Eksternal</div>
            </div>

            <div class="card mb-3" v-if="!isSubmittedSuccessfully">
                <div class="card-body">
                    <div class="steps steps-counter steps-blue">
                        <span class="step-item" :class="{ 'active': !isDataSubmitted, 'completed': isDataSubmitted }">
                            Identitas
                        </span>
                        <span class="step-item" :class="{ 'active': isDataSubmitted, 'completed': false }">
                            Isi Kuesioner
                        </span>
                    </div>
                </div>
            </div>

            <div class="card card-stacked shadow-sm border-0">

                <div class="card-header bg-primary-lt d-flex flex-column align-items-start border-bottom-0 pt-4 pb-3">
                    <div class="badge bg-white text-primary mb-2 border shadow-sm">{{ questionnaire.academic_period.name }}</div>
                    <h1 class="card-title h2 mb-2">{{ questionnaire.name }}</h1>
                    <p class="text-muted mb-0">{{ questionnaire.description }}</p>
                </div>

                <div v-if="isSubmittedSuccessfully" class="card-body text-center py-5">
                    <div class="mb-4">
                        <div class="avatar avatar-xl bg-green-lt rounded-circle shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check" width="48" height="48" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M5 12l5 5l10 -10"></path>
                            </svg>
                        </div>
                    </div>
                    <h2 class="mb-2 text-success">Terima Kasih!</h2>
                    <p class="text-muted fs-3 mb-4">Jawaban Anda telah berhasil kami simpan.</p>
                    <div class="text-muted small">Anda dapat menutup halaman ini sekarang.</div>
                </div>

                <div v-else-if="!isDataSubmitted" class="card-body">
                    <form @submit.prevent="submitRespondentData">
                        <BaseAlert
                            v-if="showRespondentDataError"
                            title="Mohon Perhatian"
                            message="Silakan lengkapi Peran, Nama, dan Kontak Anda. Jika bukan Alumni, Instansi wajib diisi."
                            type="danger"
                            class="mb-4"
                        />

                        <div class="mb-4">
                            <h3 class="card-title text-primary"><i class="fa-solid fa-user-tag me-2"></i>Data Responden</h3>
                            <div class="text-muted small">Informasi ini akan kami gunakan untuk validasi data survei.</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label required">Peran Anda</label>
                                <select class="form-select form-select-lg" v-model="form.role" :class="{'is-invalid': showRespondentDataError && !form.role}">
                                    <option value="" disabled selected>-- Pilih Peran --</option>
                                    <option value="alumni">Alumni</option>
                                    <option value="pengguna_lulusan">Pengguna Lulusan (Atasan/HRD)</option>
                                    <option value="mitra">Mitra Kerjasama</option>
                                </select>
                                <div class="form-text text-muted mt-1" v-if="form.role === 'alumni'">
                                    Sebagai lulusan yang mengisi survei pelacakan (Tracer Study).
                                </div>
                                <div class="form-text text-muted mt-1" v-if="form.role === 'pengguna_lulusan'">
                                    Sebagai pihak yang mempekerjakan lulusan kami.
                                </div>
                            </div>

                            <div class="col-12">
                                <BaseInput label="Nama Lengkap" placeholder="Masukkan nama lengkap Anda" v-model="form.name" required />
                            </div>

                            <div class="col-12" v-if="form.role !== 'alumni'">
                                <div class="mb-3">
                                    <label class="form-label" :class="{'required': form.role !== 'alumni'}">Nama Instansi / Perusahaan</label>
                                    <input type="text" v-model="form.company_or_institution" class="form-control" placeholder="Contoh: PT. Teknologi Maju" />
                                </div>
                            </div>

                            <div class="col-12">
                                <BaseInput label="Nomor Kontak (WA/Telp)" placeholder="08xxxxxxxxxx" v-model="form.contact_number" required />
                            </div>
                        </div>

                        <div class="mt-5 d-flex justify-content-end">
                            <BaseButton type="submit" variant="primary" class="w-100 w-md-auto btn-lg">
                                Lanjut Mengisi <i class="fa-solid fa-arrow-right ms-2"></i>
                            </BaseButton>
                        </div>
                    </form>
                </div>

                <div v-else class="card-body p-0">
                    <form @submit.prevent="submitAnswer">

                        <div class="p-4 border-bottom bg-light d-flex justify-content-between align-items-center sticky-top" style="z-index: 10;">
                            <div>
                                <h3 class="card-title mb-0 text-primary">{{ currentSection.name }}</h3>
                                <div class="text-muted small">Halaman {{ currentCategoryIndex + 1 }} dari {{ totalSections }}</div>
                            </div>
                            <div class="d-none d-md-block">
                                <div class="progress" style="width: 100px; height: 6px;">
                                    <div class="progress-bar bg-primary" :style="{ width: ((currentCategoryIndex + 1) / totalSections) * 100 + '%' }"></div>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 p-md-4">
                            <div v-for="(question, qIndex) in currentSection.questions" :key="question.id" class="mb-4 card card-body bg-white border shadow-none">
                                <div class="mb-3">
                                    <span class="badge bg-primary-lt me-2">{{ qIndex + 1 }}</span>
                                    <span class="fw-bold text-dark">{{ question.question_text }}</span>
                                    <span v-if="question.is_required" class="text-danger ms-1" title="Wajib diisi">*</span>
                                </div>

                                <div v-if="question.question_type === 'multiple_choice'" class="ps-md-4">
                                    <div class="row g-2">
                                        <div v-for="option in questionnaire.options" :key="option.id" class="col-6 col-md-auto">
                                            <label class="form-selectgroup-item w-100">
                                                <input
                                                    type="radio"
                                                    :name="`q_${question.id}`"
                                                    :value="option.option_value"
                                                    class="form-selectgroup-input"
                                                    v-model="form.answers.find(a => a.question_id === question.id).answer_value"
                                                >
                                                <span class="form-selectgroup-label d-flex flex-column align-items-center p-2 h-100 justify-content-center text-center">
                                                    <span class="fw-bold">{{ option.option_text }}</span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="question.question_type === 'text'" class="ps-md-4">
                                    <textarea
                                        class="form-control"
                                        rows="3"
                                        placeholder="Tuliskan jawaban Anda di sini..."
                                        v-model="form.answers.find(a => a.question_id === question.id).answer_value"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-transparent border-top p-4">
                            <div class="d-flex justify-content-between">
                                <BaseButton
                                    type="button"
                                    variant="secondary"
                                    outline
                                    @click="prevSection"
                                    :disabled="currentCategoryIndex === 0"
                                    class="px-4"
                                >
                                    <i class="fa-solid fa-arrow-left me-2"></i> Kembali
                                </BaseButton>

                                <div v-if="currentCategoryIndex < totalSections - 1">
                                    <BaseTooltip :title="!isSectionValid ? 'Harap lengkapi semua jawaban bertanda *' : ''">
                                        <span class="d-inline-block">
                                            <BaseButton
                                                type="button"
                                                @click="nextSection"
                                                variant="primary"
                                                :disabled="!isSectionValid"
                                                class="px-4"
                                            >
                                                Selanjutnya <i class="fa-solid fa-arrow-right ms-2"></i>
                                            </BaseButton>
                                        </span>
                                    </BaseTooltip>
                                </div>

                                <div v-else>
                                    <BaseTooltip :title="!isSectionValid ? 'Harap lengkapi semua jawaban bertanda *' : ''">
                                        <span class="d-inline-block">
                                            <BaseButton
                                                type="submit"
                                                variant="success"
                                                :disabled="form.processing || !isSectionValid"
                                                class="px-4"
                                            >
                                                <i class="fa-solid fa-paper-plane me-2"></i> Kirim Jawaban
                                            </BaseButton>
                                        </span>
                                    </BaseTooltip>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

            </div>

            <div class="text-center text-muted mt-4 small">
                &copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.
            </div>

        </div>
    </div>
</template>

<style scoped>
.page-center {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}
.container-tight {
    max-width: 800px;
}
.form-selectgroup-label {
    border: 1px solid #e2e8f0;
    transition: all 0.2s;
}
.form-selectgroup-input:checked + .form-selectgroup-label {
    border-color: #206bc4;
    background-color: #eef6ff;
    color: #206bc4;
    font-weight: bold;
}
</style>
