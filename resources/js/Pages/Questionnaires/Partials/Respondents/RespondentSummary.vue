<script setup>
import { computed, onMounted, watch, ref, nextTick } from 'vue';

const props = defineProps({
    chartStats: { type: Array, default: () => [] },
    prodiStats: { type: Array, default: () => [] }
});

const chartInstance = ref(null);
const isExporting = ref(false);
const expandedProdi = ref(false);
const palette = ['#206bc4', '#2fb344', '#f76707', '#ae3ec9', '#d63939', '#4299e1', '#17a2b8', '#ffc107', '#1e293b'];

const prodiChartInstance = ref(null);
const isExportingProdi = ref(false);

const downloadProdiChart = async () => {
    if (!prodiChartInstance.value) return;
    isExportingProdi.value = true;
    try {
        await prodiChartInstance.value.updateOptions({ chart: { animations: { enabled: false } } });
        const { imgURI } = await prodiChartInstance.value.dataURI({ width: 1200, scale: 2 });
        const link = document.createElement('a');
        link.href = imgURI;
        link.download = 'Distribusi-Per-Prodi.png';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        await prodiChartInstance.value.updateOptions({ chart: { animations: { enabled: true } } });
    } catch (e) {
        console.error(e);
    } finally {
        isExportingProdi.value = false;
    }
};

const renderProdiChart = async () => {
    await nextTick();
    const el = document.getElementById('prodi-distribution-chart');
    if (!el || props.prodiStats.length === 0) return;

    if (prodiChartInstance.value) {
        prodiChartInstance.value.destroy();
        prodiChartInstance.value = null;
    }

    const chartHeight = Math.max(250, props.prodiStats.length * 45);
    const isMobile = window.innerWidth < 768;

    prodiChartInstance.value = new ApexCharts(el, {
        chart: {
            type: 'bar',
            height: chartHeight,
            toolbar: { show: false },
            animations: { enabled: true }
        },
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 6,
                barHeight: '60%',
                distributed: true,
                dataLabels: { position: 'right' }
            }
        },
        colors: props.prodiStats.map((_, i) => palette[i % palette.length]),
        series: [{
            name: 'Mahasiswa',
            data: props.prodiStats.map(p => p.total)
        }],
        xaxis: {
            categories: props.prodiStats.map(p => isMobile ? p.prodi_name : `[${p.degree_level}] ${p.prodi_name}`),
            labels: { style: { fontSize: isMobile ? '10px' : '12px' } }
        },
        yaxis: {
            labels: {
                style: { fontSize: isMobile ? '10px' : '12px' },
                maxWidth: isMobile ? 100 : 220
            }
        },
        dataLabels: {
            enabled: !isMobile,
            formatter: (val) => val + ' org',
            style: { fontSize: '12px', fontWeight: '600', colors: ['#1e293b'] },
            offsetX: 8,
            dropShadow: { enabled: false }
        },
        legend: { show: false },
        grid: {
            xaxis: { lines: { show: true } },
            yaxis: { lines: { show: false } },
            padding: { right: isMobile ? 10 : 60, left: isMobile ? 0 : 10 }
        },
        tooltip: {
            theme: 'light',
            y: { formatter: (val) => val + ' Orang' }
        }
    });

    prodiChartInstance.value.render();
};

watch(expandedProdi, (val) => {
    if (val) renderProdiChart();
});

watch(() => props.prodiStats, () => {
    if (expandedProdi.value) renderProdiChart();
}, { deep: true });

const roleDistribution = computed(() => {
    let total = 0;
    const labels = [];
    const series = [];

    props.chartStats.forEach(stat => {
        let labelName = stat.name;
        if(labelName === 'alumni') labelName = 'Alumni';
        if(labelName === 'mitra') labelName = 'Mitra Kerjasama';
        if(labelName === 'pengguna_lulusan') labelName = 'Pengguna Lulusan';
        labels.push(labelName);
        series.push(Number(stat.total));
        total += Number(stat.total);
    });

    const percentages = series.map(v => total > 0 ? ((v / total) * 100).toFixed(1) : 0);
    return { labels, series, percentages, total };
});

const totalMahasiswa = computed(() => {
    const found = props.chartStats.find(s => s.name.toLowerCase() === 'mahasiswa');
    return found ? Number(found.total) : 0;
});

const downloadChart = async () => {
    if (!chartInstance.value) return;
    isExporting.value = true;
    try {
        await chartInstance.value.updateOptions({ chart: { animations: { enabled: false } } });
        const { imgURI } = await chartInstance.value.dataURI({ width: 1200, scale: 2 });
        const downloadLink = document.createElement('a');
        downloadLink.href = imgURI;
        downloadLink.download = `Distribusi-Responden.png`;
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
        await chartInstance.value.updateOptions({ chart: { animations: { enabled: true } } });
    } catch (error) {
        console.error(error);
    } finally {
        isExporting.value = false;
    }
};

const renderChart = async () => {
    await nextTick();
    const el = document.getElementById('respondent-distribution-chart');
    if (!el || roleDistribution.value.series.length === 0) return;

    if (chartInstance.value) {
        chartInstance.value.destroy();
        chartInstance.value = null;
    }

    chartInstance.value = new ApexCharts(el, {
        chart: { type: 'donut', height: 350, toolbar: { show: false }, animations: { enabled: true } },
        states: { hover: { filter: { type: 'darken', value: 0.1 } } },
        series: roleDistribution.value.series,
        labels: roleDistribution.value.labels,
        colors: roleDistribution.value.labels.map((_, i) => palette[i % palette.length]),
        stroke: { width: 2, colors: ['#ffffff'] },
        grid: { padding: { top: 20, bottom: 20, left: 10, right: 10 } },
        dataLabels: {
            enabled: true,
            formatter: (val) => `${val.toFixed(1)}%`,
            style: { fontSize: '12px', fontWeight: '600', colors: ['#1e293b'] },
            dropShadow: { enabled: false }
        },
        plotOptions: {
            pie: {
                customScale: 0.9,
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total Responden',
                            color: '#64748b',
                            fontSize: '14px',
                            formatter: () => roleDistribution.value.total
                        }
                    }
                }
            }
        },
        legend: { position: 'bottom', fontSize: '13px', markers: { radius: 12 }, itemMargin: { horizontal: 10, vertical: 5 } },
        tooltip: { theme: 'light', y: { formatter: (val) => val + ' Orang' } }
    });
    chartInstance.value.render();
};

onMounted(() => renderChart());
watch(() => props.chartStats, () => renderChart(), { deep: true });
</script>

<template>
    <div class="card card-md mb-4 shadow-sm border-0">
        <div class="card-status-top bg-primary"></div>
        <div class="card-header border-0">
            <div>
                <h3 class="card-title text-primary">Distribusi Responden</h3>
                <p class="card-subtitle text-muted small">Ringkasan partisipasi berdasarkan peran responden (Internal & Eksternal).</p>
            </div>
            <div class="card-actions">
                <button
                    class="btn btn-sm btn-ghost-success border-0 shadow-none"
                    @click="downloadChart"
                    :disabled="isExporting || roleDistribution.total === 0"
                    title="Download Chart"
                >
                    <i v-if="isExporting" class="fa-solid fa-spinner fa-spin me-1"></i>
                    <i v-else class="fa-solid fa-download me-1"></i>
                    PNG
                </button>
            </div>
        </div>

        <div class="card-body py-4 bg-light-lt">
            <div v-if="roleDistribution.total === 0" class="d-flex flex-column align-items-center justify-content-center" style="min-height: 350px;">
                <div class="text-muted mb-3">
                    <i class="fa-solid fa-chart-pie fa-2x"></i>
                </div>
                <p class="empty-title mb-1">Belum ada data</p>
                <p class="empty-subtitle text-muted small mb-0">Kuesioner ini belum memiliki responden yang berpartisipasi.</p>
            </div>
            <div v-else id="respondent-distribution-chart"></div>
        </div>

        <div class="card-footer bg-light-lt" v-if="roleDistribution.total > 0">
            <div class="row row-cards justify-content-center">
                <div v-for="(label, i) in roleDistribution.labels" :key="i" class="col-sm-6 col-lg-3">
                    <div class="card card-sm shadow-sm border-0">
                        <div class="card-status-start" :style="{ backgroundColor: palette[i % palette.length] }"></div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="badge badge-outline text-dark fw-bold">{{ roleDistribution.percentages[i] }}%</span>
                                </div>
                                <div class="col">
                                    <div class="fw-bold text-truncate" style="max-width: 130px;" :title="label">{{ label }}</div>
                                    <div class="text-muted small">{{ roleDistribution.series[i] }} Orang</div>
                                </div>
                                <div class="col-auto" v-if="label === 'Mahasiswa' && prodiStats.length > 0">
                                    <button
                                        class="btn btn-sm btn-ghost-primary border-0 shadow-none p-1"
                                        @click="expandedProdi = !expandedProdi"
                                        :title="expandedProdi ? 'Sembunyikan detail prodi' : 'Lihat detail per prodi'"
                                    >
                                        <i :class="expandedProdi ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down'"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Breakdown Per Prodi -->
            <div v-if="expandedProdi && prodiStats.length > 0" class="mt-3 px-1">
                <div class="card shadow-sm border-0">
                    <div class="card-header border-0 py-2 bg-blue-lt d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-building-columns text-primary me-2"></i>
                            <span class="fw-bold text-primary small">Detail Mahasiswa per Program Studi</span>
                            <span class="badge bg-primary ms-2">{{ prodiStats.length }} Prodi</span>
                        </div>
                        <button
                            class="btn btn-sm btn-ghost-success border-0 shadow-none"
                            @click="downloadProdiChart"
                            :disabled="isExportingProdi"
                            title="Download Chart Prodi"
                        >
                            <i v-if="isExportingProdi" class="fa-solid fa-spinner fa-spin me-1"></i>
                            <i v-else class="fa-solid fa-download me-1"></i>
                            PNG
                        </button>
                    </div>
                    <div class="card-body py-3">
                        <div id="prodi-distribution-chart"></div>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center">
                <div class="d-inline-flex align-items-center px-3 py-1 bg-blue-lt text-blue rounded-pill fw-bold small shadow-sm">
                    <i class="fa-solid fa-users me-2"></i>
                    Total Seluruh Responden: {{ roleDistribution.total }}
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.card-status-top { height: 3px; }
.bg-light-lt { background-color: #f8fafc !important; }
</style>
