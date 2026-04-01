<script setup>
import { computed, onMounted, ref, nextTick, watch } from 'vue';
import { getCriterion } from '@/Utilities/scoringUtils';
import axios from 'axios';

const props = defineProps({
    questionnaire: Object,
    criteria: Array,
    programStudies: Array,
    roles: Array
});

const chartInstance  = ref(null);
const isExporting    = ref(false);
const isLoading      = ref(false);

const activeTab              = ref('category');
const selectedCategoryFilter = ref('all');
const selectedRoleFilter     = ref('all');
const selectedProdiFilter    = ref('all');

const categoryStats = ref([]);
const prodiStats    = ref([]);
const globalScore   = ref(0);

const activeStats = computed(() => {
    return activeTab.value === 'prodi' ? prodiStats.value : categoryStats.value;
});

const overallStats = computed(() => {
    return {
        score:     globalScore.value,
        criterion: getCriterion(globalScore.value, props.criteria)
    };
});

// ── Fetch dari API ─────────────────────────────────────────────────────────
const fetchAnalysis = async () => {
    isLoading.value = true;
    try {
        const { data } = await axios.get(route('questionnaires.analysis', props.questionnaire.id), {
            params: {
                tab:      activeTab.value,
                role:     selectedRoleFilter.value,
                prodi:    selectedProdiFilter.value,
                category: selectedCategoryFilter.value,
            }
        });
        categoryStats.value = data.categoryStats;
        prodiStats.value    = data.prodiStats;
        globalScore.value   = data.globalScore;
    } catch (e) {
        console.error('fetchAnalysis error:', e);
    } finally {
        isLoading.value = false;
    }
};

// ── Render Chart ───────────────────────────────────────────────────────────
const renderChart = async () => {
    await nextTick();
    const chartEl = document.getElementById('analysis-chart');
    if (!chartEl) return;
    if (chartInstance.value) chartInstance.value.destroy();
    if (activeStats.value.length === 0) return;

    const palette = ['#206bc4', '#2fb344', '#f76707', '#ae3ec9', '#d63939', '#4299e1', '#17a2b8', '#ffc107', '#1e293b'];

    const dynamicHeight = Math.max(400, (activeStats.value.length * 60) + 100);

    chartInstance.value = new ApexCharts(chartEl, {
        series: [{
            name: 'Indeks Kepuasan',
            data: activeStats.value.map(c => c.score)
        }],
        chart: {
            type: 'bar',
            height: dynamicHeight,
            width: '100%',
            fontFamily: 'inherit',
            toolbar: { show: false },
            parentHeightOffset: 0,
        },
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 4,
                distributed: true,
                dataLabels: {
                    position: 'bottom'
                }
            }
        },
        colors: activeStats.value.map((_, i) => palette[i % palette.length]),

        dataLabels: {
            enabled: true,
            textAnchor: 'start',
            style: {
                colors: ['#fff'],
                fontWeight: 'bold',
                fontSize: '12px'
            },
            formatter: function (val) {
                const criterion = getCriterion(val, props.criteria);
                const label = criterion ? criterion.label : 'N/A';
                return `${val}% — ${label}`;
            },
            offsetX: 10,
            dropShadow: { enabled: true, top: 1, left: 1, blur: 1, color: '#000', opacity: 0.4 }
        },

        xaxis: {
            categories: activeStats.value.map(c => c.name),
            max: 100,
            labels: {
                formatter: function (val) { return val + "%" }
            }
        },
        yaxis: {
            labels: {
                show: true,
                maxWidth: 250,
                style: {
                    fontSize: '12px',
                    fontWeight: '600',
                    colors: '#1e293b'
                },
                formatter: function (val) {
                    if (val && val.length > 35) {
                        const match = val.match(/.{1,35}(\s|$)/g);
                        return match ? match.map(s => s.trim()) : val;
                    }
                    return val;
                }
            }
        },
        tooltip: {
            theme: 'light',
            y: {
                formatter: function (val) {
                    const criterion = getCriterion(val, props.criteria);
                    const label = criterion ? criterion.label : 'N/A';
                    return `${val}% (${label})`;
                }
            }
        },
        legend: { show: false },

        responsive: [
            {
                breakpoint: 768,
                options: {
                    yaxis: {
                        labels: {
                            maxWidth: 120,
                            style: { fontSize: '10px' },
                            formatter: function (val) {
                                if (val && val.length > 15) {
                                    const match = val.match(/.{1,15}(\s|$)/g);
                                    return match ? match.map(s => s.trim()) : val;
                                }
                                return val;
                            }
                        }
                    },
                    dataLabels: {
                        style: { fontSize: '10px' }
                    }
                }
            }
        ]
    });

    chartInstance.value.render();
};

// ── Download Chart ─────────────────────────────────────────────────────────
const downloadChart = async () => {
    if (!chartInstance.value) return;
    isExporting.value = true;
    try {
        await chartInstance.value.updateOptions({ chart: { animations: { enabled: false } } });
        const { imgURI } = await chartInstance.value.dataURI({ scale: 2 });

        let filename = `Analisis-${activeTab.value}`;
        if (activeTab.value === 'category') {
            let roleName = 'Semua';
            if (selectedRoleFilter.value !== 'all') {
                if (selectedRoleFilter.value === 'external_all') roleName = 'Semua-Eksternal';
                else if (['alumni', 'mitra', 'pengguna_lulusan'].includes(selectedRoleFilter.value)) roleName = selectedRoleFilter.value;
                else roleName = props.roles.find(r => r.id == selectedRoleFilter.value)?.name || 'Internal';
            }
            filename += `-${roleName}`;
        } else {
            if (selectedProdiFilter.value !== 'all') {
                const p = props.programStudies.find(p => String(p.program_study_code) === String(selectedProdiFilter.value));
                filename += `-Detail_${p ? p.name : 'Prodi'}`;
            } else {
                const cat = props.questionnaire.categories.find(c => c.id == selectedCategoryFilter.value);
                filename += `-Banding_${cat ? cat.name : 'Semua-Aspek'}`;
            }
        }

        const link = document.createElement('a');
        link.href     = imgURI;
        link.download = `${filename}.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        await chartInstance.value.updateOptions({ chart: { animations: { enabled: true } } });
    } catch (e) {
        console.error(e);
    } finally {
        isExporting.value = false;
    }
};

// ── Label Helpers ──────────────────────────────────────────────────────────
const getRoleLabel = () => {
    if (selectedRoleFilter.value === 'all')           return 'Semua Responden';
    if (selectedRoleFilter.value === 'external_all')  return 'Semua Pihak Luar';
    if (selectedRoleFilter.value === 'alumni')        return 'Alumni';
    if (selectedRoleFilter.value === 'mitra')         return 'Mitra Kerjasama';
    if (selectedRoleFilter.value === 'pengguna_lulusan') return 'Pengguna Lulusan';
    return props.roles.find(r => r.id == selectedRoleFilter.value)?.name || 'Internal';
};

const getProdiLabel = () => {
    if (selectedProdiFilter.value !== 'all') {
        const p = props.programStudies.find(p => String(p.program_study_code) === String(selectedProdiFilter.value));
        return `Detail Prodi: ${p ? `${p.degree_level} - ${p.name}` : ''}`;
    }
    const cat = props.questionnaire.categories.find(c => c.id == selectedCategoryFilter.value);
    return `Perbandingan Prodi (Aspek: ${cat ? cat.name : 'Semua'})`;
};

// ── Watchers ───────────────────────────────────────────────────────────────
// Fetch ulang saat filter berubah, lalu render chart
watch([activeTab, selectedRoleFilter, selectedProdiFilter, selectedCategoryFilter], async () => {
    await fetchAnalysis();
    await renderChart();
});

onMounted(async () => {
    await fetchAnalysis();
    await renderChart();
});
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
                                        {{ p.degree_level }} - {{ p.name }}
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
                            <div id="analysis-chart" class="w-100"></div>
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
