<script setup>
import { ref, computed } from 'vue';
import { useForm, Head, usePage } from '@inertiajs/vue3';
import BaseButton from '@/Components/BaseButton.vue';
import BaseTooltip from '@/Components/BaseTooltip.vue';
import BaseInput from '@/Components/BaseInput.vue';
import BaseAlert from '@/Components/BaseAlert.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    questionnaire: Object,
    allQuestions: Object,
    programStudies: Array,
});

const page    = usePage();
const appName = computed(() => page.props.app_name || 'Sistem Kuesioner');

const isDataSubmitted         = ref(false);
const isSubmittedSuccessfully = ref(false);
const showRespondentDataError = ref(false);

const form = useForm({
    role:                   '',
    program_study_code:     '',
    name:                   '',
    company_or_institution: '',
    contact_number:         '',
    answers: Object.values(props.allQuestions).map(q => ({
        question_id:  q.id,
        answer_value: '',
    })),
});

// Group prodi by degree_level untuk optgroup
const groupedProgramStudies = computed(() => {
    const groups = {};
    props.programStudies.forEach(p => {
        const level = p.degree_level || 'Lainnya';
        if (!groups[level]) groups[level] = [];
        groups[level].push(p);
    });
    return groups;
});

const roleLabels = {
    alumni:            'Alumni',
    pengguna_lulusan:  'Pengguna Lulusan (Atasan/HRD)',
    mitra:             'Mitra Kerjasama',
};

// Navigasi section
const currentCategoryIndex = ref(0);

const groupedSections = computed(() => {
    const sections = [];
    const allQuestionsArray = Object.values(props.allQuestions).sort((a, b) => a.order - b.order);

    const withoutCategory = allQuestionsArray.filter(q => q.category_id === null);
    if (withoutCategory.length > 0) {
        sections.push({ id: 'no-category', name: 'Pertanyaan Umum', questions: withoutCategory });
    }

    const categoriesMap = new Map();
    allQuestionsArray.forEach(q => {
        if (q.category_id !== null) {
            if (!categoriesMap.has(q.category_id)) {
                categoriesMap.set(q.category_id, {
                    id: q.category.id, name: q.category.name, order: q.category.order, questions: [],
                });
            }
            categoriesMap.get(q.category_id).questions.push(q);
        }
    });

    sections.push(...Array.from(categoriesMap.values()).sort((a, b) => a.order - b.order));
    return sections;
});

const currentSection = computed(() => groupedSections.value[currentCategoryIndex.value]);
const totalSections  = computed(() => groupedSections.value.length);
const progressPct    = computed(() => Math.round(((currentCategoryIndex.value + 1) / totalSections.value) * 100));

const isSectionValid = computed(() => {
    if (!currentSection.value) return true;
    return currentSection.value.questions
        .filter(q => q.is_required)
        .every(q => {
            const answer = form.answers.find(a => a.question_id === q.id);
            return answer?.answer_value !== null && answer?.answer_value !== '';
        });
});

const nextSection = () => { window.scrollTo({ top: 0, behavior: 'smooth' }); currentCategoryIndex.value++; };
const prevSection = () => { if (currentCategoryIndex.value > 0) { currentCategoryIndex.value--; window.scrollTo({ top: 0, behavior: 'smooth' }); } };

const submitRespondentData = () => {
    const isInvalid = !form.role
        || !form.program_study_code
        || !form.name
        || !form.contact_number
        || (form.role !== 'alumni' && !form.company_or_institution);

    if (isInvalid) {
        showRespondentDataError.value = true;
        return;
    }

    showRespondentDataError.value = false;
    isDataSubmitted.value         = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const submitAnswer = () => {
    form.post(route('answers.store.external', props.questionnaire.id), {
        preserveScroll: true,
        onSuccess: () => { isSubmittedSuccessfully.value = true; window.scrollTo({ top: 0, behavior: 'smooth' }); },
        onError:   () => { alert('Terjadi kesalahan saat mengirim jawaban. Silakan coba lagi.'); }
    });
};
</script>

<template>
    <Head :title="questionnaire.name" />

    <div class="page page-center bg-light min-vh-100 py-5">
        <div class="container container-tight py-4">

            <!-- Logo & Header -->
            <div class="text-center mb-4">
                <a href="#" class="navbar-brand navbar-brand-autodark">
                    <ApplicationLogo class="mx-auto mb-2" style="height: 60px; width: auto;" />
                </a>
                <h2 class="h2 text-primary mb-1">{{ appName }}</h2>
                <div class="text-muted">Portal Kuesioner Eksternal</div>
            </div>

            <!-- Step indicator -->
            <div class="card mb-3 border-0 shadow-sm" v-if="!isSubmittedSuccessfully">
                <div class="card-body py-3">
                    <div class="steps steps-counter steps-blue">
                        <span class="step-item" :class="{ 'active': !isDataSubmitted, 'completed': isDataSubmitted }">
                            Identitas
                        </span>
                        <span class="step-item" :class="{ 'active': isDataSubmitted }">
                            Isi Kuesioner
                        </span>
                    </div>
                </div>
            </div>

            <!-- Card Utama -->
            <div class="card card-stacked shadow-sm border-0">

                <!-- Header Kuesioner -->
                <div class="card-header bg-primary-lt d-flex flex-column align-items-start border-0 pt-4 pb-3">
                    <div class="badge bg-white text-primary mb-2 border shadow-sm">
                        <i class="fa-solid fa-calendar me-1"></i>
                        {{ questionnaire.academic_period?.name }}
                    </div>
                    <h1 class="card-title h2 mb-2">{{ questionnaire.name }}</h1>
                    <p class="text-muted mb-0">{{ questionnaire.description }}</p>
                </div>

                <!-- Success State -->
                <div v-if="isSubmittedSuccessfully" class="card-body text-center py-6">
                    <div class="mb-4">
                        <div class="avatar avatar-xl bg-green-lt rounded-circle shadow-sm mx-auto">
                            <i class="fa-solid fa-circle-check fa-2x text-success"></i>
                        </div>
                    </div>
                    <h2 class="mb-2 text-success">Terima Kasih!</h2>
                    <p class="text-muted fs-3 mb-4">Jawaban Anda telah berhasil kami simpan dan akan digunakan untuk peningkatan mutu universitas.</p>
                    <div class="text-muted small">Anda dapat menutup halaman ini sekarang.</div>
                </div>

                <!-- Step 1: Form Identitas -->
                <div v-else-if="!isDataSubmitted" class="card-body">
                    <form @submit.prevent="submitRespondentData">

                        <BaseAlert
                            v-if="showRespondentDataError"
                            title="Mohon Perhatian"
                            message="Silakan lengkapi semua field yang wajib diisi (bertanda *)."
                            type="danger"
                            class="mb-4"
                        />

                        <div class="mb-4">
                            <h3 class="card-title text-primary">
                                <i class="fa-solid fa-user-tag me-2"></i>Data Responden
                            </h3>
                            <p class="text-muted small">Informasi ini akan digunakan untuk validasi dan analisis data survei.</p>
                        </div>

                        <div class="row g-3">
                            <!-- Peran -->
                            <div class="col-12">
                                <label class="form-label required">Peran Anda</label>
                                <select
                                    class="form-select form-select-lg"
                                    v-model="form.role"
                                    :class="{ 'is-invalid': showRespondentDataError && !form.role }"
                                >
                                    <option value="" disabled>-- Pilih Peran --</option>
                                    <option value="alumni">Alumni</option>
                                    <option value="pengguna_lulusan">Pengguna Lulusan (Atasan/HRD)</option>
                                    <option value="mitra">Mitra Kerjasama</option>
                                </select>
                                <div class="form-text text-muted mt-1">
                                    <span v-if="form.role === 'alumni'">Sebagai lulusan yang mengisi survei pelacakan (Tracer Study).</span>
                                    <span v-else-if="form.role === 'pengguna_lulusan'">Sebagai pihak yang mempekerjakan lulusan kami.</span>
                                    <span v-else-if="form.role === 'mitra'">Sebagai mitra kerjasama institusi.</span>
                                </div>
                            </div>

                            <!-- Program Studi -->
                            <div class="col-12" v-if="form.role">
                                <label class="form-label required">
                                    <span v-if="form.role === 'alumni'">Program Studi Asal</span>
                                    <span v-else>Program Studi yang Terkait</span>
                                </label>
                                <select
                                    class="form-select form-select-lg"
                                    v-model="form.program_study_code"
                                    :class="{ 'is-invalid': showRespondentDataError && !form.program_study_code }"
                                >
                                    <option value="" disabled>-- Pilih Program Studi --</option>
                                    <optgroup
                                        v-for="(prodis, level) in groupedProgramStudies"
                                        :key="level"
                                        :label="level"
                                    >
                                        <option
                                            v-for="prodi in prodis"
                                            :key="prodi.program_study_code"
                                            :value="prodi.program_study_code"
                                        >
                                            {{ prodi.name }}
                                        </option>
                                    </optgroup>
                                </select>
                                <div class="form-text text-muted small mt-1">
                                    <span v-if="form.role === 'alumni'">Pilih program studi tempat Anda menempuh pendidikan.</span>
                                    <span v-else>Pilih program studi yang paling relevan dengan hubungan Anda dengan institusi kami.</span>
                                </div>
                            </div>

                            <!-- Nama -->
                            <div class="col-12">
                                <BaseInput
                                    label="Nama Lengkap"
                                    placeholder="Masukkan nama lengkap Anda"
                                    v-model="form.name"
                                    required
                                    :error="showRespondentDataError && !form.name ? 'Nama wajib diisi' : ''"
                                />
                            </div>

                            <!-- Instansi (bukan alumni) -->
                            <div class="col-12" v-if="form.role && form.role !== 'alumni'">
                                <label class="form-label required">Nama Instansi / Perusahaan</label>
                                <input
                                    type="text"
                                    v-model="form.company_or_institution"
                                    class="form-control"
                                    :class="{ 'is-invalid': showRespondentDataError && !form.company_or_institution }"
                                    placeholder="Contoh: PT. Teknologi Maju"
                                />
                            </div>

                            <!-- Kontak -->
                            <div class="col-12">
                                <BaseInput
                                    label="Nomor Kontak (WA/Telp)"
                                    placeholder="08xxxxxxxxxx"
                                    v-model="form.contact_number"
                                    required
                                    :error="showRespondentDataError && !form.contact_number ? 'Nomor kontak wajib diisi' : ''"
                                />
                            </div>
                        </div>

                        <!-- Preview identitas sebelum lanjut -->
                        <div v-if="form.role && form.program_study_code && form.name && form.contact_number" class="mt-4 p-3 bg-blue-lt rounded-3 border border-blue-lt">
                            <div class="fw-semibold text-primary mb-2">
                                <i class="fa-solid fa-circle-check me-1"></i> Ringkasan Identitas Anda
                            </div>
                            <div class="row g-2 small text-muted">
                                <div class="col-6">
                                    <span class="fw-semibold">Peran:</span> {{ roleLabels[form.role] }}
                                </div>
                                <div class="col-6">
                                    <span class="fw-semibold">Nama:</span> {{ form.name }}
                                </div>
                                <div class="col-12">
                                    <span class="fw-semibold">Program Studi:</span>
                                    {{ programStudies.find(p => p.program_study_code === form.program_study_code)?.name ?? '-' }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-end">
                            <BaseButton type="submit" variant="primary" class="w-100 w-md-auto btn-lg">
                                Lanjut Mengisi <i class="fa-solid fa-arrow-right ms-2"></i>
                            </BaseButton>
                        </div>
                    </form>
                </div>

                <!-- Step 2: Isi Kuesioner -->
                <div v-else class="card-body p-0">
                    <form @submit.prevent="submitAnswer">

                        <!-- Sticky header progress -->
                        <div class="p-3 p-md-4 border-bottom bg-white sticky-top shadow-sm" style="z-index: 10;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <h3 class="card-title mb-0 text-primary fw-bold">{{ currentSection.name }}</h3>
                                    <div class="text-muted small">
                                        Bagian {{ currentCategoryIndex + 1 }} dari {{ totalSections }}
                                    </div>
                                </div>
                                <span class="badge bg-primary-lt text-primary fw-bold px-3 py-2">
                                    {{ progressPct }}%
                                </span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" :style="{ width: progressPct + '%' }"></div>
                            </div>
                        </div>

                        <!-- Info responden ringkas -->
                        <div class="px-4 py-2 bg-blue-lt border-bottom d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-primary">{{ roleLabels[form.role] }}</span>
                            <span class="text-muted small">
                                {{ programStudies.find(p => p.program_study_code === form.program_study_code)?.name ?? '' }}
                            </span>
                            <span class="text-muted small">· {{ form.name }}</span>
                        </div>

                        <!-- Pertanyaan -->
                        <div class="p-3 p-md-4">
                            <div
                                v-for="(question, qIndex) in currentSection.questions"
                                :key="question.id"
                                class="mb-4 card border-0 shadow-sm"
                            >
                                <div class="card-body">
                                    <div class="mb-3 d-flex align-items-start gap-2">
                                        <span class="badge bg-primary-lt text-primary fw-bold flex-shrink-0">{{ qIndex + 1 }}</span>
                                        <div>
                                            <span class="fw-bold text-dark">{{ question.question_text }}</span>
                                            <span v-if="question.is_required" class="text-danger ms-1">*</span>
                                        </div>
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
                        </div>

                        <!-- Footer navigasi -->
                        <div class="card-footer bg-transparent border-top p-4">
                            <div class="d-flex justify-content-between">
                                <BaseButton type="button" variant="secondary" outline @click="prevSection" :disabled="currentCategoryIndex === 0" class="px-4">
                                    <i class="fa-solid fa-arrow-left me-2"></i> Kembali
                                </BaseButton>

                                <div v-if="currentCategoryIndex < totalSections - 1">
                                    <BaseTooltip :title="!isSectionValid ? 'Harap lengkapi semua jawaban bertanda *' : ''">
                                        <span class="d-inline-block">
                                            <BaseButton type="button" @click="nextSection" variant="primary" :disabled="!isSectionValid" class="px-4">
                                                Selanjutnya <i class="fa-solid fa-arrow-right ms-2"></i>
                                            </BaseButton>
                                        </span>
                                    </BaseTooltip>
                                </div>

                                <div v-else>
                                    <BaseTooltip :title="!isSectionValid ? 'Harap lengkapi semua jawaban bertanda *' : ''">
                                        <span class="d-inline-block">
                                            <BaseButton type="submit" variant="success" :disabled="form.processing || !isSectionValid" class="px-4">
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
.page-center { display: flex; flex-direction: column; justify-content: center; align-items: center; }
.container-tight { max-width: 800px; }
.form-selectgroup-label { border: 1px solid #e2e8f0; transition: all 0.2s; }
.form-selectgroup-input:checked + .form-selectgroup-label { border-color: #206bc4; background-color: #eef6ff; color: #206bc4; font-weight: bold; }
</style>
