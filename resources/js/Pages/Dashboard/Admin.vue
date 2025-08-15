<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps({
    activeRole: String,
    userName: String,
    activeRoleSlug: String,
    totalQuestionnairesCount: Number,
    activeQuestionnairesCount: Number,
    totalResponsesCount: Number,
    totalProgramStudiesCount: Number,
    totalFacultiesCount: Number,
    totalAcademicPeriodsCount: Number,
    totalUsersCount: Number,
    totalLocalUsersCount: Number,
    totalSevimaUsersCount: Number,
    latestQuestionnaires: Array,
    totalResponsesByFaculty: Array,
    monthlyResponses: Array,
    monthlyLabels: Array,
    totalResponsesByProgramStudy: Array,
    totalResponsesByRole: Array,
});

const stats = [
    { label: "Kuesioner Aktif", value: props.activeQuestionnairesCount, icon: "fa-list-check", color: "bg-primary" },
    { label: "Total Jawaban", value: props.totalResponsesCount, icon: "fa-poll-h", color: "bg-success" },
    { label: "Total Kuesioner", value: props.totalQuestionnairesCount, icon: "fa-file-alt", color: "bg-info" },
    { label: "Total Fakultas", value: props.totalFacultiesCount, icon: "fa-building", color: "bg-warning" },
    { label: "Total Program Studi", value: props.totalProgramStudiesCount, icon: "fa-graduation-cap", color: "bg-danger" },
    { label: "Periode Akademik", value: props.totalAcademicPeriodsCount, icon: "fa-calendar", color: "bg-secondary" },
    { label: "Total Pengguna", value: props.totalUsersCount, icon: "fa-users", color: "bg-dark", requiredRole: 'superadmin' },
    { label: "Pengguna Lokal", value: props.totalLocalUsersCount, icon: "fa-user-lock", color: "bg-purple", requiredRole: 'superadmin' },
    { label: "Pengguna Sevima", value: props.totalSevimaUsersCount, icon: "fa-user-check", color: "bg-teal", requiredRole: 'superadmin' },
];

onMounted(() => {
    // Pie Chart Responses by Faculty
    const facultyData = props.totalResponsesByFaculty.map(item => item.total);
    const facultyLabels = props.totalResponsesByFaculty.map(item => item.faculty_name);

    window.ApexCharts && (new ApexCharts(document.getElementById('chart-responses-by-faculty'), {
        chart: {
            type: "donut",
            fontFamily: 'inherit',
            height: 240,
            sparkline: {
                enabled: true
            },
            animations: {
                enabled: false
            },
        },
        fill: {
            opacity: 1,
        },
        series: facultyData,
        labels: facultyLabels,
        tooltip: {
            theme: 'dark'
        },
        grid: {
            strokeDashArray: 4,
        },
        colors: [
            '#F03D51',
            '#3E8ED0',
            '#4F9C6E',
            '#F7C948',
            '#9C27B0',
            '#607D8B'
        ],
        legend: {
            show: true,
            position: 'bottom',
            offsetY: 12,
            markers: {
                width: 10,
                height: 10,
                radius: 100,
            },
            itemMargin: {
                horizontal: 8,
                vertical: 8
            },
        },
        tooltip: {
            fillSeriesColor: false
        },
    })).render();

    // Pie Chart Responses by Role
    const roleData = props.totalResponsesByRole.map(item => item.total);
    const roleLabels = props.totalResponsesByRole.map(item => item.role_name);

    window.ApexCharts && (new ApexCharts(document.getElementById('chart-responses-by-role'), {
        chart: {
            type: "donut",
            fontFamily: 'inherit',
            height: 240,
            sparkline: {
                enabled: true
            },
            animations: {
                enabled: false
            },
        },
        fill: {
            opacity: 1,
        },
        series: roleData,
        labels: roleLabels,
        tooltip: {
            theme: 'dark'
        },
        grid: {
            strokeDashArray: 4,
        },
        colors: [
            '#4F9C6E',
            '#F03D51',
            '#3E8ED0',
            '#F7C948'
        ],
        legend: {
            show: true,
            position: 'bottom',
            offsetY: 12,
            markers: {
                width: 10,
                height: 10,
                radius: 100,
            },
            itemMargin: {
                horizontal: 8,
                vertical: 8
            },
        },
        tooltip: {
            fillSeriesColor: false
        },
    })).render();

    // Line Chart Monthly Responses by Role
    window.ApexCharts && (new ApexCharts(document.getElementById('chart-monthly-responses'), {
        chart: {
            type: "line",
            fontFamily: 'inherit',
            height: 288,
            parentHeightOffset: 0,
            toolbar: {
                show: false,
            },
            animations: {
                enabled: false
            },
        },
        fill: {
            opacity: 1,
        },
        stroke: {
            width: 2,
            lineCap: "round",
            curve: "smooth",
        },
        series: props.monthlyResponses,
        tooltip: {
            theme: 'dark'
        },
        grid: {
            padding: {
                top: -20,
                right: 0,
                left: -4,
                bottom: -4
            },
            strokeDashArray: 4,
            xaxis: {
                lines: {
                    show: true
                }
            },
        },
        xaxis: {
            labels: {
                padding: 0,
            },
            tooltip: {
                enabled: false
            },
            categories: props.monthlyLabels,
        },
        yaxis: {
            labels: {
                padding: 4
            },
        },
        colors: [
            '#F03D51', // Warna untuk role pertama (misal: Dosen)
            '#3E8ED0', // Warna untuk role kedua (misal: Mahasiswa)
            '#4F9C6E', // Warna untuk role ketiga (misal: Pegawai)
            '#F7C948', // Warna untuk role keempat (misal: Mitra)
            '#9C27B0',
            '#607D8B'
        ],
        legend: {
            show: true,
            position: 'bottom',
            offsetY: 12,
            markers: {
                width: 10,
                height: 10,
                radius: 100,
            },
            itemMargin: {
                horizontal: 8,
                vertical: 8
            },
        },
    })).render();

    // Bar Chart Responses by Program Study
    const programStudyData = props.totalResponsesByProgramStudy.map(item => item.total);
    const programStudyLabels = props.totalResponsesByProgramStudy.map(item => item.program_study_name);

    window.ApexCharts && (new ApexCharts(document.getElementById('chart-responses-by-program-study'), {
        chart: {
            type: "bar",
            fontFamily: 'inherit',
            height: 320,
            parentHeightOffset: 0,
            toolbar: {
                show: false,
            },
            animations: {
                enabled: false
            },
        },
        plotOptions: {
            bar: {
                columnWidth: '50%',
            }
        },
        dataLabels: {
            enabled: false,
        },
        fill: {
            opacity: 1,
        },
        series: [{
            name: "Total Jawaban",
            data: programStudyData
        }],
        tooltip: {
            theme: 'dark'
        },
        grid: {
            padding: {
                top: -20,
                right: 0,
                left: -4,
                bottom: -4
            },
            strokeDashArray: 4,
        },
        xaxis: {
            labels: {
                padding: 0,
            },
            tooltip: {
                enabled: false
            },
            axisBorder: {
                show: false,
            },
            categories: programStudyLabels,
        },
        yaxis: {
            labels: {
                padding: 4
            },
        },
        colors: ["#206bc4"],
        legend: {
            show: false,
        },
    })).render();
});
</script>

<template>

    <Head title="Dashboard Admin" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="page-title">Dashboard Admin</h2>
        </template>

        <div class="page-body">
            <div class="container-xl">

                <div class="col-12 mb-3">
                    <div class="card card-md">
                        <div class="card-stamp card-stamp-lg">
                            <div class="card-stamp-icon bg-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-school">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M22 9l-10 -4l-10 4l10 4l10 -4v6" />
                                    <path d="M6 10.6v5.4a6 3 0 0 0 12 0v-5.4" />
                                </svg>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-11">
                                    <h3 class="h1">Halo, {{ userName }}!</h3>
                                    <p class="text-muted mb-2">Anda saat ini beroperasi sebagai: <span
                                            class="fw-bold">{{
                                                activeRole }}</span></p>
                                    <p class="text-muted">
                                        Selamat datang kembali di Sikus — Sistem Kuesioner & Survei Universitas Potensi
                                        Utama.
                                        Aplikasi ini dirancang untuk mempermudah pengelolaan, distribusi, dan analisis
                                        kuesioner
                                        serta survei, guna mendukung peningkatan mutu akademik dan layanan di
                                        Universitas
                                        Potensi Utama.
                                    </p>

                                    <div class="mt-3">
                                        <Link :href="route('questionnaires.create')" class="btn btn-primary"
                                            rel="noopener"><i class="fa-solid fa-plus me-2"></i> Tambah Kuesioner</Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart Role Answer -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <h3 class="card-title">Tren Jawaban Bulanan Berdasarkan Peran</h3>
                            </div>
                            <div id="chart-monthly-responses"></div>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="row row-cards">
                    <div v-for="(stat, i) in stats" :key="i" class="col-sm-6 col-lg-4">
                        <div v-if="!stat.requiredRole || stat.requiredRole === activeRoleSlug" class="card card-sm">
                            <div class="card-body d-flex align-items-center">
                                <span class="avatar me-3 text-white" :class="stat.color">
                                    <i :class="`fa-solid ${stat.icon}`"></i>
                                </span>
                                <div>
                                    <div class="text-muted">{{ stat.label }}</div>
                                    <div class="h2 mb-0">{{ stat.value }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row row-cards mt-4">
                    <div class="col-12 col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Distribusi Jawaban Berdasarkan Fakultas</h3>
                            </div>
                            <div class="card-body">
                                <div id="chart-responses-by-faculty" class="chart-lg"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Distribusi Jawaban Berdasarkan Peran</h3>
                            </div>
                            <div class="card-body">
                                <div id="chart-responses-by-role" class="chart-lg"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex">
                                    <h3 class="card-title">Jawaban Per Program Studi</h3>
                                </div>
                                <div id="chart-responses-by-program-study"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Latest Questionnaires -->
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Kuesioner Terbaru</h3>
                        <Link :href="route('questionnaires.index')" class="btn btn-sm btn-primary">
                        Lihat Semua
                        </Link>
                    </div>
                    <div v-if="latestQuestionnaires.length > 0" class="list-group list-group-flush">
                        <div v-for="q in latestQuestionnaires" :key="q.id"
                            class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold">{{ q.name }}</div>
                                <small class="text-muted">
                                    Aktif: {{ new Date(q.start_date).toLocaleDateString() }} - {{ new
                                        Date(q.end_date).toLocaleDateString() }}
                                </small>
                            </div>
                            <Link :href="route('questionnaires.show', q)" class="btn btn-outline-primary btn-sm">
                            Detail
                            </Link>
                        </div>
                    </div>
                    <div v-else class="p-4 text-center text-muted">
                        Belum ada kuesioner terbaru yang dibuat.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
