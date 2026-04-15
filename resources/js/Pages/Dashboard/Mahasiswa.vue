<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { capitalizeWords } from '../../Utilities/string';

const props = defineProps({
    questionnaires: Array,
    completedQuestionnairesCount: Number,
    uncompletedQuestionnairesCount: Number,
    studentInfo: { type: Object, default: null },
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
    const el = document.getElementById('chart-mahasiswa-progress');
    if (!el) return;
    if (chartInstance.value) chartInstance.value.destroy();
    chartInstance.value = new ApexCharts(el, {
        chart: { type: 'radialBar', height: 220, toolbar: { show: false } },
        series: [completionRate.value],
        labels: ['Partisipasi'],
        colors: ['#206bc4'],
        plotOptions: {
            radialBar: {
                hollow: { size: '60%' },
                dataLabels: {
                    name: { fontSize: '13px' },
                    value: { fontSize: '22px', fontWeight: '700', formatter: (val) => `${val}%` }
                }
            }
        },
    });
    chartInstance.value.render();
};

onMounted(() => renderChart());
</script>

<template>
    <Head title="Dashboard Mahasiswa" />
    <AuthenticatedLayout>
        <template #header>
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Overview</div>
                    <h2 class="page-title">Dashboard Mahasiswa</h2>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">

                <!-- Welcome -->
                <div class="card card-md mb-4 border-0 shadow-sm bg-blue-lt">
                    <div class="card-status-start bg-primary"></div>
                    <div class="card-stamp">
                        <div class="card-stamp-icon bg-primary">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h1 class="text-primary mb-1">Halo, {{ capitalizeWords(userName) }}!</h1>
                        <p class="text-muted fs-3 mb-2">Selamat datang di <strong>Siku</strong>. Suara Anda penting untuk kemajuan kampus.</p>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span v-if="studentInfo?.nim" class="badge bg-blue-lt px-3 py-2">
                                <i class="fa-solid fa-id-card me-1"></i> NIM: {{ studentInfo.nim }}
                            </span>
                            <span v-if="studentInfo?.prodi" class="badge bg-teal-lt text-teal px-3 py-2">
                                <i class="fa-solid fa-building-columns me-1"></i>
                                {{ studentInfo.prodi }}
                                <span v-if="studentInfo.degree_level" class="ms-1 opacity-75">({{ studentInfo.degree_level }})</span>
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
                                <h3 class="card-title">Tingkat Partisipasi</h3>
                            </div>
                            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                                <div id="chart-mahasiswa-progress"></div>
                                <div class="row w-100 text-center mt-2">
                                    <div class="col">
                                        <div class="h3 text-success fw-bold mb-0">{{ completedQuestionnairesCount }}</div>
                                        <div class="text-muted small">Selesai</div>
                                    </div>
                                    <div class="col">
                                        <div class="h3 text-danger fw-bold mb-0">{{ uncompletedQuestionnairesCount }}</div>
                                        <div class="text-muted small">Belum Diisi</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info Card -->
                    <div class="col-lg-8">
                        <div class="row g-3 h-100">
                            <div class="col-12 col-sm-6">
                                <div class="card border-0 shadow-sm h-100 bg-success-lt">
                                    <div class="card-body d-flex align-items-center gap-3">
                                        <span class="avatar avatar-lg bg-success text-white rounded">
                                            <i class="fa-solid fa-circle-check fa-lg"></i>
                                        </span>
                                        <div>
                                            <div class="h2 fw-bold text-success mb-0">{{ completedQuestionnairesCount }}</div>
                                            <div class="text-muted">Kuesioner Selesai</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="card border-0 shadow-sm h-100 bg-danger-lt">
                                    <div class="card-body d-flex align-items-center gap-3">
                                        <span class="avatar avatar-lg bg-danger text-white rounded">
                                            <i class="fa-solid fa-hourglass-half fa-lg"></i>
                                        </span>
                                        <div>
                                            <div class="h2 fw-bold text-danger mb-0">{{ uncompletedQuestionnairesCount }}</div>
                                            <div class="text-muted">Belum Diisi</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-semibold text-muted small">Progress Pengisian</span>
                                            <span class="fw-bold text-primary">{{ completionRate }}%</span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-primary" :style="{ width: completionRate + '%' }"></div>
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
                            <p class="card-subtitle text-muted small">Kuesioner yang tersedia untuk Anda.</p>
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
