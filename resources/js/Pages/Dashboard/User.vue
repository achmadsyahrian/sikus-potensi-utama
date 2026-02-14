<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    questionnaires: Array,
    completedQuestionnairesCount: Number,
    uncompletedQuestionnairesCount: Number,
});

const page = usePage();
const userName = page.props.auth.user.name;
const chartInstance = ref(null);

const activeRole = computed(() => {
    const activeRoleId = page.props.auth.activeRoleId;
    if (activeRoleId) {
        return page.props.auth.user.roles.find(role => role.id === activeRoleId);
    }
    return page.props.auth.user.roles[0];
});

const renderChart = () => {
    const el = document.getElementById('chart-questionnaire-progress');
    if (!el) return;

    const options = {
        chart: {
            type: 'bar',
            fontFamily: 'inherit',
            height: 280,
            toolbar: { show: false },
            animations: { enabled: true },
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                columnWidth: '45%',
                distributed: true,
            },
        },
        dataLabels: {
            enabled: true,
            style: { fontWeight: '600' }
        },
        series: [{
            name: 'Jumlah',
            data: [props.completedQuestionnairesCount, props.uncompletedQuestionnairesCount]
        }],
        xaxis: {
            categories: ['Selesai', 'Belum Diisi'],
            axisBorder: { show: false },
        },
        fill: { opacity: 1 },
        colors: ['#2fb344', '#d63939'],
        states: {
            hover: {
                filter: { type: 'darken', value: 0.1 }
            }
        },
        legend: { show: false },
        tooltip: {
            theme: 'dark',
            y: { formatter: (val) => `${val} Kuesioner` }
        }
    };

    if (chartInstance.value) chartInstance.value.destroy();
    chartInstance.value = new ApexCharts(el, options);
    chartInstance.value.render();
};

onMounted(() => {
    if (props.questionnaires.length > 0) {
        renderChart();
    }
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Overview</div>
                    <h2 class="page-title">Dashboard Pengguna</h2>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">
                <div class="card card-md mb-4 border-0 shadow-sm bg-primary-lt">
                    <div class="card-status-start bg-primary"></div>
                    <div class="card-stamp">
                        <div class="card-stamp-icon bg-primary">
                            <i class="fa-solid fa-user-check"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-12 col-md-8">
                                <h1 class="text-primary">Halo, {{ userName }}!</h1>
                                <p class="text-muted fs-3 mb-3">
                                    Selamat datang di <strong>Siku</strong>. Kontribusi Anda sangat berharga bagi peningkatan mutu Universitas Potensi Utama.
                                </p>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-blue-lt px-3 py-2">
                                        <i class="fa-solid fa-briefcase me-1"></i>
                                        Login Sebagai: {{ activeRole?.name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row row-cards">
                    <div class="col-lg-5">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-header">
                                <h3 class="card-title">Statistik Partisipasi</h3>
                            </div>
                            <div class="card-body">
                                <div v-if="props.questionnaires.length > 0" id="chart-questionnaire-progress"></div>
                                <div v-else class="empty py-5">
                                    <div class="empty-icon text-muted">
                                        <i class="fa-solid fa-chart-pie fa-2x"></i>
                                    </div>
                                    <p class="empty-title">Belum ada data</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="card-title">Daftar Kuesioner</h3>
                                    <p class="card-subtitle text-muted small">Kelola dan isi kuesioner yang tersedia.</p>
                                </div>
                                <span class="badge bg-primary-lt text-primary">{{ questionnaires.length }} Total</span>
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
                                        <tr v-for="q in questionnaires" :key="q.id">
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
                                                <Link
                                                    :href="route(q.status === 'Diisi' ? 'answers.submitted' : 'answers.show', q)"
                                                    class="btn btn-sm btn-white px-3 shadow-sm"
                                                >
                                                    {{ q.status === 'Diisi' ? 'Detail' : 'Isi' }}
                                                </Link>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tbody v-else>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted small">
                                                Tidak ada kuesioner yang tersedia untuk Anda saat ini.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
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
