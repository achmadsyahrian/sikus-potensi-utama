<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DashboardSatisfactionTrend from "./Partials/DashboardSatisfactionTrend.vue";
import DashboardTopQuestionnaires from "./Partials/DashboardTopQuestionnaires.vue";
import { Head, Link } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";

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
    globalSatisfactionTrend: Array,
    topQuestionnairesStats: Array,
});

// Filter statistik berdasarkan role (User Count hanya untuk Superadmin)
const filteredStats = [
    {
        label: "Total Kuesioner",
        value: props.totalQuestionnairesCount,
        icon: "fa-file-alt",
        color: "bg-primary",
    },
    {
        label: "Kuesioner Aktif",
        value: props.activeQuestionnairesCount,
        icon: "fa-list-check",
        color: "bg-green",
    },
    {
        label: "Total Jawaban",
        value: props.totalResponsesCount,
        icon: "fa-poll-h",
        color: "bg-azure",
    },
    {
        label: "Total Fakultas",
        value: props.totalFacultiesCount,
        icon: "fa-building",
        color: "bg-orange",
    },
    {
        label: "Total Program Studi",
        value: props.totalProgramStudiesCount,
        icon: "fa-graduation-cap",
        color: "bg-red",
    },
    {
        label: "Periode Akademik",
        value: props.totalAcademicPeriodsCount,
        icon: "fa-calendar",
        color: "bg-secondary",
    },
    ...(props.activeRoleSlug === "superadmin"
        ? [
              {
                  label: "Total Pengguna",
                  value: props.totalUsersCount,
                  icon: "fa-users",
                  color: "bg-dark",
              },
              {
                  label: "Pengguna Sevima",
                  value: props.totalSevimaUsersCount,
                  icon: "fa-user-check",
                  color: "bg-teal",
              },
          ]
        : []),
];

const renderCharts = () => {
    const colors = [
        "#206bc4",
        "#4299e1",
        "#63b3ed",
        "#93c5fd",
        "#bfdbfe",
        "#d1e8ff",
    ];

    // 1. Line Chart: Tren Bulanan
    new ApexCharts(document.getElementById("chart-monthly-responses"), {
        chart: {
            type: "line",
            height: 300,
            toolbar: { show: false },
            fontFamily: "inherit",
        },
        stroke: { width: 3, curve: "smooth" },
        series: props.monthlyResponses,
        xaxis: { categories: props.monthlyLabels },
        colors: colors,
        tooltip: { theme: "dark" },
        legend: { position: "bottom" },
    }).render();

    // 2. Donut Chart: Distribusi Fakultas
    new ApexCharts(document.getElementById("chart-responses-by-faculty"), {
        chart: { type: "donut", height: 280, fontFamily: "inherit" },
        series: props.totalResponsesByFaculty.map((i) => i.total),
        labels: props.totalResponsesByFaculty.map((i) => i.faculty_name),
        colors: colors,
        plotOptions: { pie: { donut: { size: "70%" } } },
        states: { hover: { filter: { type: "darken", value: 0.1 } } },
        legend: { position: "bottom" },
    }).render();

    // 3. Bar Chart: Program Studi (Warna Solid Hover)
    new ApexCharts(
        document.getElementById("chart-responses-by-program-study"),
        {
            chart: {
                type: "bar",
                height: 350,
                toolbar: { show: false },
                fontFamily: "inherit",
            },
            plotOptions: {
                bar: { borderRadius: 4, distributed: true, columnWidth: "60%" },
            },
            series: [
                {
                    name: "Total",
                    data: props.totalResponsesByProgramStudy.map(
                        (i) => i.total,
                    ),
                },
            ],
            xaxis: {
                categories: props.totalResponsesByProgramStudy.map(
                    (i) => i.program_study_name,
                ),
            },
            colors: colors,
            fill: { opacity: 1 },
            states: { hover: { filter: { type: "darken", value: 0.1 } } },
            legend: { show: false },
        },
    ).render();
};

onMounted(() => {
    setTimeout(renderCharts, 300);
});
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Administrator</div>
                    <h2 class="page-title">Dashboard Analitik</h2>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-list">
                        <Link
                            :href="route('questionnaires.create')"
                            class="btn btn-primary d-none d-sm-inline-block"
                        >
                            <i class="fa-solid fa-plus me-2"></i> Tambah
                            Kuesioner
                        </Link>
                    </div>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">
                <div class="card card-md mb-4 border-0 shadow-sm bg-primary-lt">
                    <div class="card-status-start bg-primary"></div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span
                                    class="avatar avatar-xl rounded bg-white text-primary shadow-sm"
                                >
                                    <i
                                        class="fa-solid fa-shield-halved fa-2x"
                                    ></i>
                                </span>
                            </div>
                            <div class="col">
                                <h1 class="mb-1 text-primary">
                                    Selamat Datang, {{ userName }}!
                                </h1>
                                <p class="text-muted fs-3">
                                    Anda masuk sebagai
                                    <strong>{{ activeRole }}</strong
                                    >. Pantau dan kelola seluruh aktivitas
                                    kuesioner Universitas Potensi Utama.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row row-cards mb-4">
                    <div class="col-lg-8">
                        <DashboardSatisfactionTrend :data="globalSatisfactionTrend" />
                    </div>
                    <div class="col-lg-4">
                        <DashboardTopQuestionnaires :questionnaires="topQuestionnairesStats" />
                    </div>
                </div>

                <div class="row row-cards mb-4">
                    <div
                        v-for="(stat, i) in filteredStats"
                        :key="i"
                        class="col-sm-6 col-lg-3"
                    >
                        <div class="card card-sm shadow-sm border-0 h-100">
                            <div class="card-body d-flex align-items-center">
                                <span
                                    class="avatar me-3 text-white rounded"
                                    :class="stat.color"
                                >
                                    <i :class="`fa-solid ${stat.icon}`"></i>
                                </span>
                                <div>
                                    <div class="text-muted small fw-bold">
                                        {{ stat.label }}
                                    </div>
                                    <div class="h2 mb-0">{{ stat.value }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row row-cards">
                    <div class="col-lg-8">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header">
                                <h3 class="card-title">Tren Jawaban Bulanan</h3>
                            </div>
                            <div class="card-body">
                                <div id="chart-monthly-responses"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Distribusi Per Fakultas
                                </h3>
                            </div>
                            <div class="card-body">
                                <div id="chart-responses-by-faculty"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row row-cards mt-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Partisipasi Per Program Studi
                                </h3>
                            </div>
                            <div class="card-body">
                                <div
                                    id="chart-responses-by-program-study"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4 shadow-sm border-0">
                    <div
                        class="card-header d-flex justify-content-between align-items-center"
                    >
                        <div>
                            <h3 class="card-title">Kuesioner Terbaru</h3>
                            <p class="card-subtitle text-muted small">
                                Daftar kuesioner yang terakhir ditambahkan ke
                                sistem.
                            </p>
                        </div>
                        <Link
                            :href="route('questionnaires.index')"
                            class="btn btn-sm btn-white px-3 shadow-sm"
                        >
                            Lihat Semua
                        </Link>
                    </div>
                    <div class="table-responsive">
                        <table
                            class="table table-vcenter card-table table-hover"
                        >
                            <thead>
                                <tr>
                                    <th>Nama Kuesioner</th>
                                    <th>Periode Akademik</th>
                                    <th>Masa Aktif</th>
                                    <th class="w-1 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody v-if="latestQuestionnaires.length > 0">
                                <tr
                                    v-for="q in latestQuestionnaires"
                                    :key="q.id"
                                >
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span
                                                class="badge bg-primary-lt me-2 p-2"
                                            >
                                                <i
                                                    class="fa-solid fa-file-lines text-primary"
                                                ></i>
                                            </span>
                                            <div class="fw-bold text-dark">
                                                {{ q.name }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="badge badge-outline text-azure border-azure-lt px-2 py-1"
                                        >
                                            <i
                                                class="fa-solid fa-calendar-day me-1"
                                            ></i>
                                            {{
                                                q.academic_period?.name ||
                                                "Umum"
                                            }}
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        <div class="d-flex flex-column">
                                            <span
                                                ><i
                                                    class="fa-solid fa-play text-success me-1"
                                                    style="font-size: 8px"
                                                ></i>
                                                {{
                                                    new Date(
                                                        q.start_date,
                                                    ).toLocaleDateString(
                                                        "id-ID",
                                                    )
                                                }}</span
                                            >
                                            <span
                                                ><i
                                                    class="fa-solid fa-stop text-danger me-1"
                                                    style="font-size: 8px"
                                                ></i>
                                                {{
                                                    new Date(
                                                        q.end_date,
                                                    ).toLocaleDateString(
                                                        "id-ID",
                                                    )
                                                }}</span
                                            >
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <Link
                                            :href="
                                                route('questionnaires.show', q)
                                            "
                                            class="btn btn-icon btn-sm btn-ghost-primary"
                                            title="Detail"
                                        >
                                            <i class="fa-solid fa-eye"></i>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td
                                        colspan="4"
                                        class="text-center py-5 text-muted"
                                    >
                                        <div class="empty">
                                            <p class="empty-title">
                                                Belum ada kuesioner
                                            </p>
                                            <p
                                                class="empty-subtitle text-muted small"
                                            >
                                                Silakan buat kuesioner pertama
                                                Anda melalui tombol tambah.
                                            </p>
                                        </div>
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
.bg-primary-lt {
    background-color: #f0f7ff !important;
}
.card-status-start {
    width: 4px;
}
:deep(.apexcharts-canvas) {
    margin: 0 auto;
}
</style>
