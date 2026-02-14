<script setup>
import { computed, onMounted, ref, nextTick, watch } from 'vue';
import { getCriterion, calculateQuestionScore } from '@/Utilities/scoringUtils';

const props = defineProps({
    questionnaire: Object,
    criteria: Array,
    programStudies: Array,
    roles: Array
});

const chartInstance = ref(null);
const isExporting = ref(false);

// State UI & Filters
const activeTab = ref('category'); // 'category' | 'prodi'
const selectedCategoryFilter = ref('all'); // Filter Aspek (utk Mode Banding Prodi)
const selectedRoleFilter = ref('all');     // Filter Role (utk Tab Aspek)
const selectedProdiFilter = ref('all');    // NEW: Filter Prodi Spesifik

// 1. Helper Max Option
const maxOptionValue = computed(() => {
    if (!props.questionnaire.options || props.questionnaire.options.length === 0) return 0;
    return Math.max(...props.questionnaire.options.map(o => o.option_value));
});

// 2. DATA CHART: Hitung Skor Per Kategori (Tab Analisis Aspek)
const categoryStats = computed(() => {
    return props.questionnaire.categories.map(category => {
        const questions = props.questionnaire.questions.filter(q => q.category_id === category.id && q.question_type === 'multiple_choice');
        if (questions.length === 0) return null;

        let totalPercent = 0;
        let count = 0;

        questions.forEach(q => {
            let answers = props.questionnaire.answers.filter(a => a.question_id === q.id);
            // Filter Role
            if (selectedRoleFilter.value !== 'all') {
                if (['alumni', 'mitra', 'pengguna_lulusan'].includes(selectedRoleFilter.value)) {
                     answers = answers.filter(a => a.respondent_external?.role === selectedRoleFilter.value);
                } else if (selectedRoleFilter.value === 'external_all') {
                     answers = answers.filter(a => a.respondent_external_id !== null);
                } else {
                    answers = answers.filter(a => a.role_id == selectedRoleFilter.value);
                }
            }
            const qStats = calculateQuestionScore(answers, props.questionnaire.options);
            if (answers.length > 0) {
                totalPercent += parseFloat(qStats.percentage);
                count++;
            }
        });

        if (count === 0) return null;
        const finalScore = totalPercent / count;

        return {
            name: category.name,
            score: parseFloat(finalScore.toFixed(1)),
            criterion: getCriterion(finalScore, props.criteria)
        };
    }).filter(Boolean);
});

// 3. DATA CHART: Hitung Skor Per Prodi (Tab Analisis Prodi)
const prodiStats = computed(() => {
    if (maxOptionValue.value === 0) return [];
    const mhsRole = props.roles.find(r => r.name.toLowerCase() === 'mahasiswa');
    if (!mhsRole) return [];

    // --- MODE A: DETAIL PRODI (Jika Filter Prodi Dipilih) ---
    // Tampilkan Skor Per KATEGORI untuk Prodi tersebut
    if (selectedProdiFilter.value !== 'all') {
        return props.questionnaire.categories.map(category => {
            const questions = props.questionnaire.questions.filter(q => q.category_id === category.id && q.question_type === 'multiple_choice');
            if (questions.length === 0) return null;

            let totalPercent = 0;
            let count = 0;

            questions.forEach(q => {
                // Ambil jawaban: Role Mahasiswa AND Prodi Code Cocok AND Question ID Cocok
                const answers = props.questionnaire.answers.filter(a =>
                    a.question_id === q.id &&
                    a.role_id === mhsRole.id &&
                    String(a.user?.student_detail?.program_study_code) === String(selectedProdiFilter.value)
                );

                const qStats = calculateQuestionScore(answers, props.questionnaire.options);
                if (answers.length > 0) {
                    totalPercent += parseFloat(qStats.percentage);
                    count++;
                }
            });

            if (count === 0) return null;
            const finalScore = totalPercent / count;

            return {
                name: category.name, // Label Chart = Nama Kategori
                score: parseFloat(finalScore.toFixed(1)),
                criterion: getCriterion(finalScore, props.criteria)
            };
        }).filter(Boolean);
    }

    // --- MODE B: BANDING SEMUA PRODI (Jika Filter Prodi = All) ---
    // Tampilkan Skor Per PRODI (bisa difilter aspek tertentu)
    let validQuestionIds = [];
    if (selectedCategoryFilter.value === 'all') {
        validQuestionIds = props.questionnaire.questions.map(q => q.id);
    } else {
        validQuestionIds = props.questionnaire.questions
            .filter(q => q.category_id == selectedCategoryFilter.value)
            .map(q => q.id);
    }

    const mhsAnswers = props.questionnaire.answers.filter(a =>
        a.role_id === mhsRole.id &&
        a.user?.student_detail?.program_study_code &&
        validQuestionIds.includes(a.question_id)
    );

    const groups = {};
    mhsAnswers.forEach(ans => {
        const code = ans.user.student_detail.program_study_code;
        if (!groups[code]) groups[code] = { sumPercent: 0, count: 0 };
        const val = parseInt(ans.answer_value || 0);
        const pct = (val / maxOptionValue.value) * 100;
        groups[code].sumPercent += pct;
        groups[code].count++;
    });

    return Object.keys(groups).map(code => {
        const prodi = props.programStudies.find(p => String(p.program_study_code) === String(code));
        const prodiName = prodi ? prodi.name : `Prodi: ${code}`;
        const finalScore = groups[code].sumPercent / groups[code].count;

        return {
            name: prodiName, // Label Chart = Nama Prodi
            score: parseFloat(finalScore.toFixed(1)),
            criterion: getCriterion(finalScore, props.criteria)
        };
    });
});

// 4. Data Aktif
const activeStats = computed(() => {
    return activeTab.value === 'prodi' ? prodiStats.value : categoryStats.value;
});

// 5. Global Score (Updated Logic)
const overallStats = computed(() => {
    if (maxOptionValue.value === 0) return { score: 0, criterion: { label: '-', color: '#ccc' } };

    let relevantAnswers = [];

    if (activeTab.value === 'category') {
        // ... (Logic Tab Category Tetap Sama) ...
        relevantAnswers = props.questionnaire.answers.filter(a => {
            const q = props.questionnaire.questions.find(qu => qu.id === a.question_id);
            if (!q || q.question_type !== 'multiple_choice') return false;
            if (selectedRoleFilter.value !== 'all') {
                 if (['alumni', 'mitra', 'pengguna_lulusan'].includes(selectedRoleFilter.value)) {
                    return a.respondent_external?.role === selectedRoleFilter.value;
                } else if (selectedRoleFilter.value === 'external_all') {
                     return a.respondent_external_id !== null;
                } else {
                    return a.role_id == selectedRoleFilter.value;
                }
            }
            return true;
        });

    } else {
        // --- Filter TAB PRODI ---
        const mhsRole = props.roles.find(r => r.name.toLowerCase() === 'mahasiswa');
        if (!mhsRole) return { score: 0, criterion: { label: '-', color: '#ccc' } };

        // Logic Filter Pertanyaan
        let validQuestionIds = [];
        // Jika Prodi Spesifik dipilih, kita ambil SEMUA kategori (karena chart menampilkan kategori)
        // Jika Semua Prodi dipilih, kita cek filter kategori
        if (selectedProdiFilter.value !== 'all' || selectedCategoryFilter.value === 'all') {
             validQuestionIds = props.questionnaire.questions.map(q => q.id);
        } else {
            validQuestionIds = props.questionnaire.questions
                .filter(q => q.category_id == selectedCategoryFilter.value)
                .map(q => q.id);
        }

        relevantAnswers = props.questionnaire.answers.filter(a => {
            const isMhs = a.role_id === mhsRole.id;
            const hasProdi = a.user?.student_detail?.program_study_code;
            const isValidQ = validQuestionIds.includes(a.question_id);

            // Cek Filter Prodi Spesifik
            let isProdiMatch = true;
            if (selectedProdiFilter.value !== 'all') {
                isProdiMatch = String(a.user?.student_detail?.program_study_code) === String(selectedProdiFilter.value);
            }

            return isMhs && hasProdi && isValidQ && isProdiMatch;
        });
    }

    if (relevantAnswers.length === 0) {
        return { score: 0, criterion: { label: '-', color: '#ccc' } };
    }

    const sumValues = relevantAnswers.reduce((sum, a) => sum + parseInt(a.answer_value || 0), 0);
    const finalScore = (sumValues / (relevantAnswers.length * maxOptionValue.value)) * 100;

    return {
        score: finalScore.toFixed(1),
        criterion: getCriterion(finalScore, props.criteria)
    };
});

const renderChart = async () => {
    await nextTick();
    const chartEl = document.getElementById('analysis-chart');
    if (!chartEl) return;
    if (chartInstance.value) chartInstance.value.destroy();

    if (activeStats.value.length === 0) return;

    const palette = ['#206bc4', '#2fb344', '#f76707', '#ae3ec9', '#d63939', '#4299e1', '#17a2b8', '#ffc107', '#1e293b'];

    const options = {
        series: activeStats.value.map(c => c.score),
        labels: activeStats.value.map(c => c.name),
        chart: {
            type: 'polarArea',
            height: 380,
            fontFamily: 'inherit',
            animations: { enabled: true },
            toolbar: { show: false }
        },
        colors: activeStats.value.map((_, i) => palette[i % palette.length]),
        fill: { opacity: 0.9 },
        stroke: { width: 1, colors: ['#fff'] },
        yaxis: { show: false },
        legend: { position: 'bottom', fontSize: '12px' },
        plotOptions: { polarArea: { rings: { strokeWidth: 0 }, spokes: { strokeWidth: 0 } } },
        tooltip: {
            theme: 'light',
            y: { formatter: (val) => val + "%" }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val, opts) {
                const index = opts.seriesIndex;
                const stat = activeStats.value[index];
                return `${stat.criterion.label} (${stat.score}%)`;
            },
            style: { colors: ['#333'], fontWeight: 'bold', fontSize: '10px' },
            background: { enabled: true, foreColor: '#fff', borderRadius: 2, opacity: 0.8 }
        }
    };

    chartInstance.value = new ApexCharts(chartEl, options);
    chartInstance.value.render();
};

const downloadChart = async () => {
    if (!chartInstance.value) return;
    isExporting.value = true;
    try {
        await chartInstance.value.updateOptions({ chart: { animations: { enabled: false } } });
        const { imgURI } = await chartInstance.value.dataURI({ scale: 2 });
        const link = document.createElement('a');
        link.href = imgURI;

        let filename = `Analisis-${activeTab.value}`;
        if (activeTab.value === 'category') {
             // ... logic nama file role ...
             let roleName = 'Semua';
             if (selectedRoleFilter.value !== 'all') {
                if (selectedRoleFilter.value === 'external_all') roleName = 'Semua-Eksternal';
                else if (['alumni', 'mitra', 'pengguna_lulusan'].includes(selectedRoleFilter.value)) roleName = selectedRoleFilter.value;
                else roleName = props.roles.find(r => r.id == selectedRoleFilter.value)?.name || 'Internal';
             }
            filename += `-${roleName}`;
        } else {
             // Logic nama file Prodi
             if (selectedProdiFilter.value !== 'all') {
                 const pName = props.programStudies.find(p => String(p.program_study_code) === String(selectedProdiFilter.value))?.name || 'Prodi';
                 filename += `-Detail_${pName}`;
             } else {
                 const catName = props.questionnaire.categories.find(c => c.id == selectedCategoryFilter.value)?.name || 'Semua-Aspek';
                 filename += `-Banding_${catName}`;
             }
        }

        link.download = `${filename}.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        await chartInstance.value.updateOptions({ chart: { animations: { enabled: true } } });
    } catch (e) { console.error(e); }
    finally { isExporting.value = false; }
};

const getRoleLabel = () => {
    if (selectedRoleFilter.value === 'all') return 'Semua Responden';
    if (selectedRoleFilter.value === 'external_all') return 'Semua Pihak Luar';
    if (selectedRoleFilter.value === 'alumni') return 'Alumni';
    if (selectedRoleFilter.value === 'mitra') return 'Mitra Kerjasama';
    if (selectedRoleFilter.value === 'pengguna_lulusan') return 'Pengguna Lulusan';
    return props.roles.find(r => r.id == selectedRoleFilter.value)?.name || 'Internal';
};

// Helper Label Prodi
const getProdiLabel = () => {
    if (selectedProdiFilter.value !== 'all') {
        const p = props.programStudies.find(p => String(p.program_study_code) === String(selectedProdiFilter.value));
        return `Detail Prodi: ${p ? p.name : ''}`;
    }
    const cat = props.questionnaire.categories.find(c => c.id == selectedCategoryFilter.value);
    return `Perbandingan Prodi (Aspek: ${cat ? cat.name : 'Semua'})`;
}

onMounted(() => renderChart());
// Tambahkan selectedProdiFilter ke watcher
watch([activeStats, activeTab, selectedCategoryFilter, selectedRoleFilter, selectedProdiFilter], () => renderChart());
</script>

<template>
    <div class="card mb-3 border-primary-lt">
        <div class="card-status-top bg-primary"></div>
        <div class="card-body">
            <div class="row">

                <div class="col-md-4 border-end-md d-flex flex-column align-items-center justify-content-center py-4">
                    <div class="text-muted fw-bold text-uppercase small tracking-wide">Indeks Kepuasan Global</div>
                    <div class="display-1 fw-bold my-3 text-dark">{{ overallStats.score }}%</div>

                    <div class="d-inline-flex align-items-center px-3 py-1 rounded-pill text-white shadow-sm mb-3"
                         :style="{ backgroundColor: overallStats.criterion.color || '#6c757d' }">
                        <i class="fa-solid fa-medal me-2"></i>
                        <span class="fw-bold">{{ overallStats.criterion.label }}</span>
                    </div>

                    <div class="text-muted small text-center px-4">
                        <div v-if="activeTab === 'category'">
                            <i class="fa-solid fa-users me-1"></i>
                            Basis Data: <br>
                            <strong>{{ getRoleLabel() }}</strong>
                        </div>
                        <div v-else>
                            <i class="fa-solid fa-graduation-cap me-1"></i>
                            Basis Data: <br>
                            <strong>{{ getProdiLabel() }}</strong>
                        </div>
                    </div>
                </div>

                <div class="col-md-8 ps-md-0">
                    <div class="border-bottom bg-light-lt px-3 pt-3">
                        <ul class="nav nav-tabs card-header-tabs" style="margin-bottom: -1px;">
                            <li class="nav-item">
                                <a
                                    href="#"
                                    class="nav-link"
                                    :class="{ 'active fw-bold': activeTab === 'category' }"
                                    @click.prevent="activeTab = 'category'"
                                >
                                    <i class="fa-solid fa-layer-group me-2 text-muted"></i>
                                    Analisis Aspek
                                </a>
                            </li>
                            <li class="nav-item">
                                <a
                                    href="#"
                                    class="nav-link"
                                    :class="{ 'active fw-bold': activeTab === 'prodi' }"
                                    @click.prevent="activeTab = 'prodi'"
                                >
                                    <i class="fa-solid fa-graduation-cap me-2 text-muted"></i>
                                    Analisis Prodi
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="px-3 py-3 bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">

                        <div v-if="activeTab === 'category'" class="d-flex align-items-center">
                            <label class="form-label mb-0 me-2 text-muted small">Filter Role:</label>
                            <select v-model="selectedRoleFilter" class="form-select form-select-sm" style="width: 200px;">
                                <option value="all">Semua Responden</option>
                                <optgroup label="Internal Kampus">
                                    <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                                </optgroup>
                                <optgroup label="Pihak Luar (Eksternal)">
                                    <option value="external_all">Semua Pihak Luar</option>
                                    <option value="alumni">Alumni</option>
                                    <option value="mitra">Mitra Kerjasama</option>
                                    <option value="pengguna_lulusan">Pengguna Lulusan</option>
                                </optgroup>
                            </select>
                        </div>

                        <div v-if="activeTab === 'prodi'" class="d-flex align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center">
                                <label class="form-label mb-0 me-2 text-muted small">Prodi:</label>
                                <select v-model="selectedProdiFilter" class="form-select form-select-sm" style="width: 180px;">
                                    <option value="all">Semua (Bandingkan)</option>
                                    <option v-for="p in programStudies" :key="p.id" :value="p.program_study_code">
                                        {{ p.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="d-flex align-items-center" v-if="selectedProdiFilter === 'all'">
                                <label class="form-label mb-0 me-2 text-muted small">Aspek:</label>
                                <select v-model="selectedCategoryFilter" class="form-select form-select-sm" style="width: 150px;">
                                    <option value="all">Semua Aspek</option>
                                    <option v-for="cat in questionnaire.categories" :key="cat.id" :value="cat.id">
                                        {{ cat.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <button
                            class="btn btn-sm btn-ghost-success ms-auto"
                            @click="downloadChart"
                            title="Download Chart"
                            :disabled="isExporting"
                        >
                            <i v-if="isExporting" class="fa-solid fa-spinner fa-spin me-1"></i>
                            <i v-else class="fa-solid fa-download me-1"></i>
                            PNG
                        </button>
                    </div>

                    <div class="p-3">
                        <div v-if="activeStats.length > 0">
                            <div id="analysis-chart" style="min-height: 380px;"></div>
                        </div>
                        <div v-else class="text-center py-5 text-muted bg-light rounded border border-dashed m-3">
                            <i class="fa-solid fa-chart-simple fa-2x mb-2 d-block text-secondary"></i>
                            <div class="fw-bold text-dark">Tidak ada data</div>
                            <small class="text-muted">
                                {{ activeTab === 'prodi' ? 'Belum ada data untuk Prodi/Filter ini.' : 'Belum ada jawaban untuk filter ini.' }}
                            </small>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media (min-width: 768px) {
    .border-end-md { border-right: 1px dashed #e2e8f0; }
}
.bg-light-lt { background-color: #f8fafc !important; }
.nav-tabs .nav-link { border: none; border-bottom: 2px solid transparent; color: #64748b; }
.nav-tabs .nav-link:hover { color: #1e293b; }
.nav-tabs .nav-link.active { border-color: #206bc4; color: #206bc4; background: transparent; }
</style>
