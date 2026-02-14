<script setup>
import { computed, onMounted, watch, ref, nextTick } from 'vue';

const props = defineProps({
    respondents: Object
});

const chartInstance = ref(null);
const isExporting = ref(false);

const palette = ['#206bc4', '#2fb344', '#f76707', '#ae3ec9', '#d63939', '#4299e1', '#17a2b8', '#ffc107', '#1e293b'];

const roleDistribution = computed(() => {
    const counts = {};
    let totalParticipation = 0; // Total partisipasi (bisa > jumlah orang)

    if (props.respondents && props.respondents.data) {
        props.respondents.data.forEach(item => {
            if (item.type === 'external' && item.respondent_external) {
                // --- KASUS EKSTERNAL ---
                let roleLabel = item.respondent_external.role;
                switch (roleLabel) {
                    case 'alumni': roleLabel = 'Alumni'; break;
                    case 'mitra': roleLabel = 'Mitra Kerjasama'; break;
                    case 'pengguna_lulusan': roleLabel = 'Pengguna Lulusan'; break;
                    default: roleLabel = 'Eksternal Umum';
                }
                counts[roleLabel] = (counts[roleLabel] || 0) + 1;
                totalParticipation++;
            } else {
                // --- KASUS INTERNAL ---
                // Kita harus menghitung SETIAP role yang dimiliki item ini dalam konteks responden
                // Asumsi: item.roles berisi daftar role yang relevan (misal user login sbg Dosen & Pegawai)

                if (item.roles && item.roles.length > 0) {
                    item.roles.forEach(role => {
                        counts[role.name] = (counts[role.name] || 0) + 1;
                        totalParticipation++;
                    });
                } else {
                    // Fallback jika tidak ada role terdeteksi
                    counts['Tanpa Role'] = (counts['Tanpa Role'] || 0) + 1;
                    totalParticipation++;
                }
            }
        });
    }

    // Hitung persentase berdasarkan Total Partisipasi, bukan Total Orang
    const labels = Object.keys(counts);
    const series = Object.values(counts);
    const percentages = series.map(v => totalParticipation > 0 ? ((v / totalParticipation) * 100).toFixed(1) : 0);

    return {
        labels,
        series,
        percentages,
        total: totalParticipation, // Ini jumlah partisipasi role
        uniqueRespondents: props.respondents.data ? props.respondents.data.length : 0 // Ini jumlah orang asli
    };
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

    if (chartInstance.value) chartInstance.value.destroy();

    const chartColors = roleDistribution.value.labels.map((_, i) => palette[i % palette.length]);

    chartInstance.value = new ApexCharts(el, {
        chart: {
            type: 'donut',
            height: 350,
            fontFamily: 'inherit',
            toolbar: { show: false },
            sparkline: { enabled: false },
            animations: { enabled: true }
        },
        states: {
            hover: { filter: { type: 'darken', value: 0.1 } }
        },
        series: roleDistribution.value.series,
        labels: roleDistribution.value.labels,
        colors: chartColors,
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
                            label: 'Partisipasi',
                            color: '#64748b',
                            fontSize: '14px',
                            formatter: () => roleDistribution.value.total
                        }
                    }
                },
                dataLabels: { offset: 40 }
            }
        },
        legend: {
            show: true,
            position: 'bottom',
            fontSize: '13px',
            markers: { radius: 12 },
            itemMargin: { horizontal: 10, vertical: 5 }
        },
        tooltip: {
            theme: 'light',
            y: {
                formatter: function (val) {
                    return val + " Peran";
                }
            }
        }
    });
    chartInstance.value.render();
};

onMounted(() => renderChart());
watch(() => props.respondents, () => renderChart(), { deep: true });
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
                    class="btn btn-sm btn-ghost-success"
                    @click="downloadChart"
                    :disabled="isExporting || roleDistribution.total === 0"
                >
                    <i v-if="isExporting" class="fa-solid fa-spinner fa-spin me-1"></i>
                    <i v-else class="fa-solid fa-download me-1"></i>
                    PNG
                </button>
            </div>
        </div>

        <div class="card-body border-top border-bottom bg-light-lt py-4">
            <div id="respondent-distribution-chart"></div>

            <div v-if="roleDistribution.total === 0" class="text-center text-muted py-4">
                Belum ada data responden.
            </div>
        </div>

        <div class="card-footer bg-light-lt" v-if="roleDistribution.total > 0">
            <div class="row row-cards">
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
                                        {{ roleDistribution.series[i] }} Peran
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center d-flex justify-content-center gap-3">
                <div class="d-inline-flex align-items-center px-3 py-1 bg-blue-lt text-blue rounded-pill fw-bold small">
                    <i class="fa-solid fa-layer-group me-2"></i>
                    Total Partisipasi: {{ roleDistribution.total }}
                </div>
                <div class="d-inline-flex align-items-center px-3 py-1 bg-green-lt text-green rounded-pill fw-bold small">
                    <i class="fa-solid fa-users me-2"></i>
                    Total Orang Unik: {{ roleDistribution.uniqueRespondents }}
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.card-status-top { height: 3px; }
.bg-light-lt { background-color: #f8fafc !important; }
</style>
