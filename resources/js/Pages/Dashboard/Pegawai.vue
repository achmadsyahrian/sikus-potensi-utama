<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { capitalizeWords } from '../../Utilities/string';

const props = defineProps({
    questionnaires: Array,
    completedQuestionnairesCount: Number,
    uncompletedQuestionnairesCount: Number,
    activePeriod: { type: Object, default: null },
});

const page     = usePage();
const userName = page.props.auth.user.name;
const chartInstance = ref(null);

const completionRate = computed(() => {
    const total = props.completedQuestionnairesCount + props.uncompletedQuestionnairesCount;
    return total > 0 ? Math.round((props.completedQuestionnairesCount / total) * 100) : 0;
});

const renderChart = () => {
    const el = document.getElementById('chart-pegawai-progress');
    if (!el) return;
    if (chartInstance.value) chartInstance.value.destroy();
    chartInstance.value = new ApexCharts(el, {
        chart: { type: 'bar', fontFamily: 'inherit', height: 220, toolbar: { show: false } },
        plotOptions: { bar: { borderRadius: 4, columnWidth: '45%', distributed: true } },
        dataLabels: { enabled: true, style: { fontWeight: '600' } },
        series: [{ name: 'Jumlah', data: [props.completedQuestionnairesCount, props.uncompletedQuestionnairesCount] }],
        xaxis: { categories: ['Selesai', 'Belum Diisi'], axisBorder: { show: false } },
        colors: ['#2fb344', '#d63939'],
        legend: { show: false },
        tooltip: { y: { formatter: (val) => `${val} Kuesioner` } },
    });
    chartInstance.value.render();
};

onMounted(() => {
    if (props.questionnaires.length > 0) renderChart();
});
</script>

<template>
    <Head title="Dashboard Pegawai" />
    <AuthenticatedLayout>
        <template #header>
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Overview</div>
                    <h2 class="page-title">Dashboard Pegawai</h2>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">

                <!-- Welcome -->
                <div class="card card-md mb-4 border-0 shadow-sm" style="background-color: #f0fff4;">
                    <div class="card-status-start bg-green"></div>
                    <div class="card-stamp">
                        <div class="card-stamp-icon bg-green">
                            <i class="fa-solid fa-user-tie"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h1 class="text-green mb-1">Halo, {{ capitalizeWords(userName) }}!</h1>
                        <p class="text-muted fs-3 mb-2">Selamat datang di <strong>Siku</strong>. Masukan Anda membantu peningkatan layanan universitas.</p>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span class="badge bg-green-lt text-green px-3 py-2">
                                <i class="fa-solid fa-briefcase me-1"></i> Pegawai
                            </span>
                            <span v-if="activePeriod" class="badge bg-azure-lt text-azure px-3 py-2">
                                <i class="fa-solid fa-calendar me-1"></i> {{ activePeriod.name }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Stat + Chart -->
                <div class="row g-3 mb-4">
                    <div class="col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header border-0">
                                <h3 class="card-title">Statistik Partisipasi</h3>
                            </div>
                            <div class="card-body">
                                <div v-if="questionnaires.length > 0" id="chart-pegawai-progress"></div>
                                <div v-else class="empty py-4">
                                    <div class="empty-icon text-muted"><i class="fa-solid fa-chart-pie fa-2x"></i></div>
                                    <p class="empty-title">Belum ada data</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="row g-3 h-100">
                            <div class="col-sm-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <div class="h2 fw-bold text-primary mb-0">{{ questionnaires.length }}</div>
                                        <div class="text-muted small">Total Kuesioner</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <div class="h2 fw-bold text-success mb-0">{{ completedQuestionnairesCount }}</div>
                                        <div class="text-muted small">Sudah Diisi</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <div class="h2 fw-bold text-danger mb-0">{{ uncompletedQuestionnairesCount }}</div>
                                        <div class="text-muted small">Belum Diisi</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-semibold text-muted small">Progress Pengisian</span>
                                            <span class="fw-bold text-green">{{ completionRate }}%</span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-green" :style="{ width: completionRate + '%' }"></div>
                                        </div>
                                        <div class="text-muted small mt-2">
                                            {{ completedQuestionnairesCount }} dari {{ questionnaires.length }} kuesioner telah diisi
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daftar Kuesioner -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title">Daftar Kuesioner</h3>
                            <p class="card-subtitle text-muted small">Kuesioner yang perlu Anda isi.</p>
                        </div>
                        <Link :href="route('answers.index')" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-list me-1"></i> Lihat Semua
                        </Link>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover">
                            <thead>
                                <tr>
                                    <th>Judul Kuesioner</th>
                                    <th>Periode</th>
                                    <th class="w-1">Status</th>
                                    <th class="w-1">Aksi</th>
                                </tr>
                            </thead>
                            <tbody v-if="questionnaires.length > 0">
                                <tr v-for="q in questionnaires.slice(0, 5)" :key="q.id">
                                    <td>
                                        <div class="fw-bold">{{ q.name }}</div>
                                        <div class="text-muted small">
                                            <i class="fa-regular fa-calendar-check me-1"></i>
                                            Berakhir: {{ q.formatted_end_date || '-' }}
                                        </div>
                                    </td>
                                    <td class="text-nowrap">
                                        <span class="badge badge-outline text-azure px-2 py-1">
                                            {{ q.academic_period?.name || '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span :class="q.status === 'Diisi' ? 'badge bg-success-lt text-success' : 'badge bg-danger-lt text-danger'">
                                            {{ q.status }}
                                        </span>
                                    </td>
                                    <td>
                                        <Link :href="route(q.status === 'Diisi' ? 'answers.submitted' : 'answers.show', q)" class="btn btn-sm btn-white px-3 shadow-sm">
                                            {{ q.status === 'Diisi' ? 'Detail' : 'Isi' }}
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted small">
                                        Tidak ada kuesioner yang tersedia saat ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.card-status-start { width: 4px; }
</style>
