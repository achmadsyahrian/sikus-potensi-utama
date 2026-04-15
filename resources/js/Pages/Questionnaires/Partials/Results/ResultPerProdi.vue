<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { getCriterion } from '@/Utilities/scoringUtils';
import axios from 'axios';

const props = defineProps({
    questionnaire: Object,
    criteria:      Array,
    programStudies: Array,
    roles:         Array,
});

const activeStakeholder  = ref('mahasiswa');
const selectedProdi      = ref('all');
const selectedCategory   = ref('all');
const selectedExternalRole = ref('all');
const isLoading          = ref(false);
const chartInstance      = ref(null);
const isExporting        = ref(false);

const prodiStats  = ref([]);
const globalScore = ref(0);

const stakeholderTabs = [
    { key: 'mahasiswa', label: 'Mahasiswa',  icon: 'fa-graduation-cap' },
    { key: 'dosen',     label: 'Dosen',      icon: 'fa-chalkboard-user' },
    { key: 'eksternal', label: 'Eksternal',  icon: 'fa-handshake' },
];

const chartTitle = computed(() => {
    if (selectedProdi.value !== 'all') {
        const p = props.programStudies.find(p => p.program_study_code === selectedProdi.value);
        return `Detail Aspek — ${p ? `${p.name} (${p.degree_level})` : selectedProdi.value}`;
    }
    const cat = props.questionnaire.categories.find(c => c.id == selectedCategory.value);
    return `Perbandingan Prodi${cat ? ` — Aspek: ${cat.name}` : ' — Semua Aspek'}`;
});

const fetchData = async () => {
    isLoading.value = true;
    prodiStats.value  = [];
    globalScore.value = 0;
    try {
        const { data } = await axios.get(route('questionnaires.analysis', props.questionnaire.id), {
            params: {
                tab:         'prodi',
                stakeholder: activeStakeholder.value,
                prodi:       selectedProdi.value,
                category:    selectedCategory.value,
                role:        activeStakeholder.value === 'eksternal' ? selectedExternalRole.value : 'all',
            }
        });
        prodiStats.value  = data.prodiStats;
        globalScore.value = data.globalScore;
    } catch (e) {
        console.error(e);
    } finally {
        isLoading.value = false;
        await nextTick();
        renderChart();
    }
};

const renderChart = () => {
    const el = document.getElementById('prodi-analysis-chart');
    if (!el) return;
    if (chartInstance.value) { chartInstance.value.destroy(); chartInstance.value = null; }
    if (!prodiStats.value.length) return;

    const palette  = ['#206bc4', '#2fb344', '#f76707', '#ae3ec9', '#d63939', '#4299e1', '#17a2b8', '#ffc107'];
    const height   = Math.max(300, prodiStats.value.length * 55 + 80);

    chartInstance.value = new ApexCharts(el, {
        chart: { type: 'bar', height, toolbar: { show: false }, fontFamily: 'inherit' },
        plotOptions: { bar: { horizontal: true, borderRadius: 4, distributed: true, dataLabels: { position: 'bottom' } } },
        colors: prodiStats.value.map((_, i) => palette[i % palette.length]),
        series: [{ name: 'Indeks Kepuasan', data: prodiStats.value.map(p => p.score) }],
        xaxis: {
            categories: prodiStats.value.map(p => p.degree_level ? `[${p.degree_level}] ${p.name}` : p.name),
            max: 100,
            labels: { formatter: v => v + '%' }
        },
        yaxis: { labels: { maxWidth: 250, style: { fontSize: '12px', fontWeight: '600' } } },
        dataLabels: {
            enabled: true,
            textAnchor: 'start',
            style: { colors: ['#fff'], fontWeight: 'bold', fontSize: '12px' },
            formatter: (val) => {
                const c = getCriterion(val, props.criteria);
                return `${val}% — ${c?.label ?? 'N/A'}`;
            },
            offsetX: 10,
            dropShadow: { enabled: true, top: 1, left: 1, blur: 1, opacity: 0.4 }
        },
        tooltip: { theme: 'light', y: { formatter: (val) => `${val}% (${getCriterion(val, props.criteria)?.label ?? 'N/A'})` } },
        legend: { show: false },
    });
    chartInstance.value.render();
};

const downloadChart = async () => {
    if (!chartInstance.value) return;
    isExporting.value = true;
    try {
        const { imgURI } = await chartInstance.value.dataURI({ scale: 2 });
        const link = document.createElement('a');
        link.href     = imgURI;
        link.download = `PerProdi-${activeStakeholder.value}.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (e) { console.error(e); }
    finally { isExporting.value = false; }
};

watch([activeStakeholder, selectedProdi, selectedCategory, selectedExternalRole], () => {
    if (activeStakeholder.value !== 'eksternal') selectedExternalRole.value = 'all';
    fetchData();
});

fetchData();
</script>

<template>
    <div class="card border-0 shadow-sm">
        <div class="card-status-top bg-primary"></div>

        <!-- Stakeholder Tabs -->
        <div class="card-header border-0 pb-0">
            <ul class="nav nav-tabs card-header-tabs">
                <li v-for="tab in stakeholderTabs" :key="tab.key" class="nav-item">
                    <a
                        href="#"
                        class="nav-link px-4"
                        :class="{ 'active fw-bold': activeStakeholder === tab.key }"
                        @click.prevent="activeStakeholder = tab.key; selectedProdi = 'all'; selectedCategory = 'all';"
                    >
                        <i :class="`fa-solid ${tab.icon} me-2`"></i>{{ tab.label }}
                    </a>
                </li>
            </ul>
        </div>

        <!-- Filter Bar -->
        <div class="card-body border-bottom py-3 bg-light-lt">
            <div class="row g-2 align-items-end">

                <!-- Filter Prodi -->
                <div class="col-12 col-md-4">
                    <label class="form-label text-muted small mb-1">Program Studi</label>
                    <select v-model="selectedProdi" class="form-select form-select-sm">
                        <option value="all">Semua (Bandingkan antar prodi)</option>
                        <optgroup
                            v-for="(group, level) in Object.groupBy ? Object.groupBy(programStudies, p => p.degree_level) : {}"
                            :key="level" :label="level"
                        >
                            <option v-for="p in group" :key="p.program_study_code" :value="p.program_study_code">
                                {{ p.name }}
                            </option>
                        </optgroup>
                        <template v-if="!Object.groupBy">
                            <option v-for="p in programStudies" :key="p.program_study_code" :value="p.program_study_code">
                                [{{ p.degree_level }}] {{ p.name }}
                            </option>
                        </template>
                    </select>
                </div>

                <!-- Filter Aspek -->
                <div class="col-12 col-md-4" v-if="selectedProdi === 'all'">
                    <label class="form-label text-muted small mb-1">Aspek / Kategori</label>
                    <select v-model="selectedCategory" class="form-select form-select-sm">
                        <option value="all">Semua Aspek</option>
                        <option v-for="cat in questionnaire.categories" :key="cat.id" :value="cat.id">
                            {{ cat.name }}
                        </option>
                    </select>
                </div>

                <!-- Filter Role Eksternal -->
                <div class="col-12 col-md-4" v-if="activeStakeholder === 'eksternal'">
                    <label class="form-label text-muted small mb-1">Tipe Eksternal</label>
                    <select v-model="selectedExternalRole" class="form-select form-select-sm">
                        <option value="all">Semua (Alumni + Mitra + Pengguna Lulusan)</option>
                        <option value="alumni">Alumni</option>
                        <option value="mitra">Mitra Kerjasama</option>
                        <option value="pengguna_lulusan">Pengguna Lulusan</option>
                    </select>
                </div>

                <!-- Download -->
                <div class="col-auto ms-auto">
                    <button
                        class="btn btn-sm btn-ghost-success"
                        @click="downloadChart"
                        :disabled="isExporting || !prodiStats.length"
                    >
                        <i v-if="isExporting" class="fa-solid fa-spinner fa-spin me-1"></i>
                        <i v-else class="fa-solid fa-download me-1"></i> PNG
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Global Score Badge -->
            <div v-if="globalScore > 0" class="d-flex align-items-center gap-3 mb-4 p-3 bg-light-lt rounded-3">
                <div>
                    <div class="text-muted small fw-semibold">Indeks Kepuasan</div>
                    <div class="h2 fw-bold mb-0">{{ globalScore }}%</div>
                </div>
                <div
                    class="badge px-3 py-2 text-white fs-6"
                    :style="{ backgroundColor: getCriterion(globalScore, criteria)?.color ?? '#6c757d' }"
                >
                    {{ getCriterion(globalScore, criteria)?.label ?? 'N/A' }}
                </div>
                <div class="text-muted small ms-2">{{ chartTitle }}</div>
            </div>

            <!-- Loading -->
            <div v-if="isLoading" class="text-center py-5 text-muted">
                <i class="fa-solid fa-spinner fa-spin fa-2x mb-3 d-block"></i>
                Memuat data...
            </div>

            <!-- Chart -->
            <div v-else-if="prodiStats.length > 0">
                <div id="prodi-analysis-chart"></div>
            </div>

            <!-- Empty -->
            <div v-else class="text-center py-5 text-muted border rounded-3 bg-light-lt">
                <i class="fa-solid fa-chart-simple fa-2x mb-3 d-block text-secondary"></i>
                <div class="fw-bold text-dark">Tidak ada data</div>
                <small>Belum ada jawaban untuk filter yang dipilih.</small>
            </div>
        </div>
    </div>
</template>

<style scoped>
.bg-light-lt { background-color: #f8fafc !important; }
.nav-tabs .nav-link { border: none; border-bottom: 2px solid transparent; color: #64748b; }
.nav-tabs .nav-link:hover { color: #1e293b; }
.nav-tabs .nav-link.active { border-color: #206bc4; color: #206bc4; background: transparent; }
</style>
