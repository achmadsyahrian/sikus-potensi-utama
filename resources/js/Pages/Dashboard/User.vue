<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

const props = defineProps({
    questionnaires: Array,
    completedQuestionnairesCount: Number,
    uncompletedQuestionnairesCount: Number,
});

const page = usePage();
const userName = page.props.auth.user.name;

const activeRole = computed(() => {
    const activeRoleId = page.props.auth.activeRoleId;
    if (activeRoleId) {
        return page.props.auth.user.roles.find(role => role.id === activeRoleId);
    }
    return page.props.auth.user.roles[0];
});

onMounted(() => {
    // Bar Chart Progres Pengisian Kuesioner
    const completionData = [props.completedQuestionnairesCount, props.uncompletedQuestionnairesCount];
    const completionLabels = ['Selesai', 'Belum Diisi'];

    setTimeout(() => {
        if (completionData.length > 0) {
            window.ApexCharts && (new ApexCharts(document.getElementById('chart-questionnaire-progress'), {
                chart: {
                    type: 'bar',
                    fontFamily: 'inherit',
                    height: 250,
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
                        horizontal: false,
                        columnWidth: '50%',
                        endingShape: 'rounded'
                    },
                },
                dataLabels: {
                    enabled: false,
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent']
                },
                series: [{
                    name: 'Jumlah Kuesioner',
                    data: completionData
                }],
                xaxis: {
                    categories: completionLabels,
                },
                yaxis: {
                    title: {
                        text: 'Jumlah'
                    }
                },
                fill: {
                    opacity: 1
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val + " kuesioner"
                        }
                    }
                },
                colors: [
                    '#4F9C6E', // Selesai
                    '#F03D51'  // Belum Diisi
                ],
                legend: {
                    show: false,
                },
            })).render();
        }
    }, 500);
});
</script>

<template>

    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="page-title">Dashboard Pengguna</h2>
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
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-user-check">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                    <path d="M6 21v-2a4 4 0 0 1 4 -4h4" />
                                    <path d="M15 19l2 2l4 -4" />
                                </svg>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-11">
                                    <h3 class="h1">Halo, {{ userName }}!</h3>
                                    <p class="text-muted mb-2">
                                        Selamat datang kembali di Siku — Sistem Kuesioner Universitas Potensi
                                        Utama.
                                    </p>
                                    <p class="text-muted">
                                        Anda saat ini beroperasi sebagai:
                                        <span class="fw-bold" v-if="activeRole">{{ activeRole.name }}</span>
                                        <span class="fw-bold" v-else>{{ page.props.auth.user.roles[0].name }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progres Pengisian Kuesioner (Bar Chart) -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Progres Pengisian Kuesioner</h3>
                    </div>
                    <div class="card-body">
                        <div v-if="props.questionnaires.length > 0" id="chart-questionnaire-progress"></div>
                        <div v-else class="d-flex justify-content-center align-items-center" style="height: 250px;">
                            <p class="text-muted">Tidak ada kuesioner yang tersedia untuk Anda.</p>
                        </div>
                    </div>
                </div>

                <!-- Daftar Kuesioner -->
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Daftar Kuesioner</h3>
                    </div>
                    <div v-if="questionnaires.length > 0" class="list-group list-group-flush">
                        <div v-for="q in questionnaires" :key="q.id"
                            class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold">{{ q.name }}</div>
                                <small class="text-muted">
                                    Status: <span
                                        :class="{ 'text-success': q.status === 'Diisi', 'text-danger': q.status === 'Belum Diisi' }">{{
                                            q.status }}</span>
                                </small>
                            </div>
                            <Link :href="route(q.status === 'Diisi' ? 'answers.submitted' : 'answers.show', q)"
                                class="btn btn-outline-primary btn-sm">
                            {{ q.status === 'Diisi' ? 'Lihat Jawaban' : 'Isi Sekarang' }}
                            </Link>
                        </div>
                    </div>
                    <div v-else class="p-4 text-center text-muted">
                        <p class="mb-0">Tidak ada kuesioner yang tersedia untuk Anda.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
