<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { capitalizeWords } from '../../Utilities/string';

const props = defineProps({
    questionnaires: Array,
    completedQuestionnairesCount: Number,
    uncompletedQuestionnairesCount: Number,
    lecturerScheduleInfo: { type: Object, default: null },
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
    const el = document.getElementById('chart-dosen-progress');
    if (!el) return;
    if (chartInstance.value) chartInstance.value.destroy();
    chartInstance.value = new ApexCharts(el, {
        chart: { type: 'donut', height: 220, toolbar: { show: false } },
        series: [props.completedQuestionnairesCount, props.uncompletedQuestionnairesCount],
        labels: ['Selesai', 'Belum Diisi'],
        colors: ['#2fb344', '#d63939'],
        legend: { position: 'bottom', fontSize: '13px' },
        dataLabels: { enabled: true, formatter: (val) => `${val.toFixed(0)}%` },
        plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Total', formatter: () => props.completedQuestionnairesCount + props.uncompletedQuestionnairesCount } } } } },
        tooltip: { y: { formatter: (val) => `${val} Kuesioner` } },
    });
    chartInstance.value.render();
};

onMounted(() => {
    if (props.questionnaires.length > 0) renderChart();
});
</script>

<template>
    <Head title="Dashboard Dosen" />
    <AuthenticatedLayout>
        <template #header>
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Overview</div>
                    <h2 class="page-title">Dashboard Dosen</h2>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">

                <!-- Welcome -->
                <div class="card card-md mb-4 border-0 shadow-sm bg-primary-lt">
                    <div class="card-status-start bg-primary"></div>
                    <div class="card-stamp">
                        <div class="card-stamp-icon bg-primary">
                            <i class="fa-solid fa-chalkboard-user"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h1 class="text-primary mb-1">Halo, {{ capitalizeWords(userName) }}!</h1>
                        <p class="text-muted fs-3 mb-2">Selamat datang di <strong>Siku</strong>. Terima kasih atas partisipasi Anda.</p>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span class="badge bg-blue-lt px-3 py-2">
                                <i class="fa-solid fa-briefcase me-1"></i> Dosen
                            </span>
                            <span v-if="activePeriod" class="badge bg-teal-lt text-teal px-3 py-2">
                                <i class="fa-solid fa-calendar me-1"></i> {{ activePeriod.name }}
                            </span>
                            <span v-if="lecturerScheduleInfo" class="badge bg-purple-lt text-purple px-3 py-2">
                                <i class="fa-solid fa-building-columns me-1"></i>
                                Mengajar di {{ lecturerScheduleInfo.total_prodi }} Program Studi
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Stat Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-lg-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="h1 text-primary fw-bold">{{ questionnaires.length }}</div>
                                <div class="text-muted small">Total Kuesioner</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="h1 text-success fw-bold">{{ completedQuestionnairesCount }}</div>
                                <div class="text-muted small">Sudah Diisi</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="h1 text-danger fw-bold">{{ uncompletedQuestionnairesCount }}</div>
                                <div class="text-muted small">Belum Diisi</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="h1 text-teal fw-bold">{{ completionRate }}%</div>
                                <div class="text-muted small">Tingkat Partisipasi</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <!-- Chart -->
                    <div class="col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header border-0">
                                <h3 class="card-title">Statistik Partisipasi</h3>
                            </div>
                            <div class="card-body">
                                <div v-if="questionnaires.length > 0" id="chart-dosen-progress"></div>
                                <div v-else class="empty py-4">
                                    <div class="empty-icon text-muted"><i class="fa-solid fa-chart-pie fa-2x"></i></div>
                                    <p class="empty-title">Belum ada data</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Jadwal Mengajar -->
                    <div class="col-lg-8">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-status-top bg-teal"></div>
                            <div class="card-header border-0">
                                <div>
                                    <h3 class="card-title text-teal">
                                        <i class="fa-solid fa-chalkboard-user me-2"></i>Jadwal Mengajar Aktif
                                    </h3>
                                    <p class="card-subtitle text-muted small" v-if="lecturerScheduleInfo">
                                        Periode <strong>{{ lecturerScheduleInfo.period_name }}</strong>
                                    </p>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div v-if="lecturerScheduleInfo" class="row g-2">
                                    <div v-for="(prodi, i) in lecturerScheduleInfo.prodi_list" :key="prodi.id_program_studi" class="col-12 col-sm-6">
                                        <div class="d-flex align-items-center p-3 rounded-3 bg-teal-lt gap-3">
                                            <span class="avatar avatar-sm bg-teal text-white rounded">{{ i + 1 }}</span>
                                            <div>
                                                <div class="fw-bold small">{{ prodi.program_studi }}</div>
                                                <div class="text-muted" style="font-size:11px;">
                                                    <span v-if="prodi.degree_level" class="badge bg-azure-lt text-azure me-1">{{ prodi.degree_level }}</span>
                                                    {{ prodi.id_program_studi }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-circle-info me-1"></i>
                                    Tidak ada jadwal mengajar pada periode aktif.
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
.bg-primary-lt { background-color: #f0f7ff !important; }
.card-status-start { width: 4px; }
</style>
