<script setup>
import { computed, onMounted, watch, ref, nextTick } from 'vue';

const props = defineProps({
    chartStats: {
        type: Array,
        default: () => []
    }
});

const chartInstance = ref(null);
const isExporting = ref(false);
const palette = ['#206bc4', '#2fb344', '#f76707', '#ae3ec9', '#d63939', '#4299e1', '#17a2b8', '#ffc107', '#1e293b'];

const roleDistribution = computed(() => {
    let total = 0;
    const labels = [];
    const series = [];

    props.chartStats.forEach(stat => {
        let labelName = stat.name;
        // Percantik label eksternal
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

const downloadChart = async () => {
    if (!chartInstance.value) return;

    isExporting.value = true;
    try {
        await chartInstance.value.updateOptions({ chart: { animations: { enabled: false } } });

        const { imgURI } = await chartInstance.value.dataURI({
            width: 1200,
            scale: 2
        });

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
        chart: {
            type: 'donut',
            height: 350,
            toolbar: { show: false },
            animations: { enabled: true }
        },
        states: {
            hover: { filter: { type: 'darken', value: 0.1 } }
        },
        series: roleDistribution.value.series,
        labels: roleDistribution.value.labels,
        colors: roleDistribution.value.labels.map((_, i) => palette[i % palette.length]),
        stroke: { width: 2, colors: ['#ffffff'] },
        grid: {
            padding: { top: 20, bottom: 20, left: 10, right: 10 }
        },
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
        legend: {
            position: 'bottom',
            fontSize: '13px',
            markers: { radius: 12 },
            itemMargin: { horizontal: 10, vertical: 5 }
        },
        tooltip: {
            theme: 'light',
            y: {
                formatter: function (val) {
                    return val + " Orang";
                }
            }
        }
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
            <div id="respondent-distribution-chart"></div>

            <div v-if="roleDistribution.total === 0" class="text-center py-5">
                <div class="empty-icon text-muted">
                    <i class="fa-solid fa-chart-pie fa-2x"></i>
                </div>
                <p class="empty-title mt-3">Belum ada data</p>
                <p class="empty-subtitle text-muted small">Kuesioner ini belum memiliki responden yang berpartisipasi.</p>
            </div>
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
                                    <div class="fw-bold text-truncate" style="max-width: 130px;" :title="label">
                                        {{ label }}
                                    </div>
                                    <div class="text-muted small">
                                        {{ roleDistribution.series[i] }} Orang
                                    </div>
                                </div>
                            </div>
                        </div>
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
