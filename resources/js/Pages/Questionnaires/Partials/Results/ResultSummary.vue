<script setup>
import { computed, ref, onMounted, watch } from 'vue';
import BaseSelect from '@/Components/BaseSelect.vue';

const props = defineProps({
    questionnaire: Object,
    roles: Array
});

const selectedCategory = ref('all');
const selectedRole = ref('all'); // State Filter Role
const chartType = ref('bar');
const chartInstance = ref(null);
const isExporting = ref(false);

const stats = computed(() => {
    const mcQuestions = props.questionnaire.questions.filter(q => {
        const isMC = q.question_type === 'multiple_choice';
        if (selectedCategory.value === 'all') return isMC;
        return isMC && q.category_id == selectedCategory.value;
    });

    const allAnswers = props.questionnaire.answers.filter(a => {
        const matchQuestion = mcQuestions.some(q => q.id === a.question_id);

        // --- LOGIC FILTER ROLE TERBARU ---
        if (selectedRole.value === 'all') {
            return matchQuestion;
        } else if (['alumni', 'mitra', 'pengguna_lulusan'].includes(selectedRole.value)) {
            // Filter spesifik eksternal role
            return matchQuestion && a.respondent_external?.role === selectedRole.value;
        } else if (selectedRole.value === 'external_all') {
            // Filter semua eksternal
            return matchQuestion && a.respondent_external_id !== null;
        } else {
            // Filter Internal
            return matchQuestion && a.role_id == selectedRole.value;
        }
        // ---------------------------------
    });

    const counts = {};
    props.questionnaire.options.forEach(opt => counts[opt.option_text] = 0);
    allAnswers.forEach(ans => {
        const opt = props.questionnaire.options.find(o => o.option_value == ans.answer_value);
        if (opt) counts[opt.option_text]++;
    });

    const total = allAnswers.length;
    return {
        labels: Object.keys(counts),
        series: Object.values(counts),
        total: total,
        percentages: Object.values(counts).map(v => total > 0 ? ((v / total) * 100).toFixed(1) : 0)
    };
});

const downloadChart = async () => {
    if (!chartInstance.value) return;

    isExporting.value = true;
    try {
        // Nama file dinamis
        let roleName = 'Semua';
        if (selectedRole.value !== 'all') {
             if (selectedRole.value === 'external_all') roleName = 'Semua-Eksternal';
             else if (['alumni', 'mitra', 'pengguna_lulusan'].includes(selectedRole.value)) roleName = selectedRole.value;
             else roleName = props.roles.find(r => r.id == selectedRole.value)?.name || 'Internal';
        }

        const fileName = `Analisis-Opsi-${props.questionnaire.name}-${selectedCategory.value}-${roleName}`;

        await chartInstance.value.updateOptions({
            chart: { animations: { enabled: false } },
            plotOptions: { pie: { customScale: 0.7 } },
            grid: { padding: { left: 100, right: 100 } }
        });

        await new Promise(resolve => setTimeout(resolve, 200));
        const { imgURI } = await chartInstance.value.dataURI({ scale: 2 });

        const downloadLink = document.createElement('a');
        downloadLink.href = imgURI;
        downloadLink.download = `${fileName}.png`;
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);

        await chartInstance.value.updateOptions({
            chart: { animations: { enabled: true } },
            plotOptions: { pie: { customScale: 1 } },
            grid: { padding: { left: 0, right: 0 } }
        });
    } catch (error) {
        console.error(error);
    } finally {
        isExporting.value = false;
    }
};

const renderChart = () => {
    if (chartInstance.value) chartInstance.value.destroy();
    if (stats.value.total === 0) return;

    const colors = ['#206bc4', '#4299e1', '#63b3ed', '#93c5fd', '#bfdbfe'];
    let options = {
        chart: {
            animations: { enabled: true },
            background: 'transparent',
            fontFamily: 'inherit'
        },
        states: {
            hover: {
                filter: { type: 'darken', value: 0.1 }
            }
        },
        tooltip: {
            theme: 'light'
        }
    };

    if (chartType.value === 'bar') {
        options = {
            ...options,
            chart: { ...options.chart, type: 'bar', height: 320, toolbar: { show: false } },
            plotOptions: { bar: { borderRadius: 4, distributed: true, columnWidth: '50%', dataLabels: { position: 'top' } } },
            colors: colors,
            series: [{ name: 'Total', data: stats.value.series }],
            xaxis: { categories: stats.value.labels, axisBorder: { show: false } },
            dataLabels: { enabled: true, offsetY: -20, style: { fontSize: '12px', colors: ["#334155"] } },
            legend: { show: false }
        };
    } else {
        options = {
            ...options,
            chart: { ...options.chart, type: 'donut', height: 400, toolbar: { show: false } },
            series: stats.value.series,
            labels: stats.value.labels,
            colors: colors,
            stroke: { width: 2, colors: ['#ffffff'] },
            dataLabels: {
                enabled: true,
                formatter: (val, opts) => `${val.toFixed(1)}%`,
                style: { fontSize: '12px', fontWeight: '600', colors: ['#1e293b'] },
                dropShadow: { enabled: false }
            },
            plotOptions: {
                pie: {
                    customScale: 1,
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Jawaban',
                                color: '#64748b',
                                fontSize: '14px',
                                formatter: () => stats.value.total
                            }
                        }
                    },
                    dataLabels: { offset: 50 }
                }
            },
            legend: { position: 'bottom', fontSize: '13px', markers: { radius: 12 }, itemMargin: { horizontal: 10 } }
        };
    }

    chartInstance.value = new ApexCharts(document.getElementById('global-distribution-chart'), options);
    chartInstance.value.render();
};

onMounted(() => renderChart());
watch([selectedCategory, selectedRole, chartType, stats], () => renderChart());
</script>

<template>
    <div class="card card-md">
        <div class="card-status-top bg-primary"></div>
        <div class="card-header border-0">
            <div>
                <h3 class="card-title">Analisis Dominansi Kuesioner</h3>
                <p class="card-subtitle text-muted small">Visualisasi akumulasi jawaban berdasarkan filter aktif.</p>
            </div>
            <div class="card-actions">
                <button class="btn btn-success w-100" @click="downloadChart" :disabled="isExporting || stats.total === 0">
                    <i v-if="isExporting" class="fa-solid fa-spinner fa-spin me-1"></i>
                    <i v-else class="fa-solid fa-download me-1"></i>
                    Export PNG
                </button>
            </div>
        </div>

        <div class="card-body border-top border-bottom py-4">
            <div class="row g-3 bg-light-lt">
                <div class="col-md-3">
                    <label class="form-label text-dark">Tipe Visual</label>
                    <div class="btn-group w-100">
                        <button class="btn btn-outline-primary" :class="{ 'active': chartType === 'bar' }" @click="chartType = 'bar'">Bar</button>
                        <button class="btn btn-outline-primary" :class="{ 'active': chartType === 'donut' }" @click="chartType = 'donut'">Donut</button>
                    </div>
                </div>

                <div class="col-md-4">
                    <BaseSelect
                        label="Kategori"
                        v-model="selectedCategory"
                        class="text-dark"
                        :options="[{id: 'all', name: 'Semua Kategori'}, ...questionnaire.categories]"
                    />
                </div>

                <div class="col-md-5">
                    <label class="form-label text-dark">Peran Responden</label>
                    <select v-model="selectedRole" class="form-select text-dark">
                        <option value="all">Semua Responden</option>
                        <optgroup label="Internal Kampus">
                            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                        </optgroup>
                        <optgroup label="Pihak Luar (Eksternal)">
                            <option value="external_all">Semua Pihak Luar</option>
                            <option value="alumni">Alumni</option>
                            <option value="mitra">Mitra Kerjasama</option>
                            <option value="pengguna_lulusan">Pengguna Lulusan</option>
                        </optgroup>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body border-top border-bottom bg-light-lt py-4">
            <div id="global-distribution-chart" class="text-dark"></div>
            <div class="empty py-4" v-if="stats.total === 0">
                <div class="empty-icon text-muted">
                    <i class="fa-solid fa-database fa-2x"></i>
                </div>
                <p class="empty-title">Tidak ada data</p>
                <p class="empty-subtitle text-muted small">Silakan sesuaikan filter untuk melihat statistik.</p>
            </div>
        </div>

        <div class="card-footer bg-light-lt" v-if="stats.total > 0">
        <div class="row row-cards">
            <div v-for="(label, idx) in stats.labels" :key="idx" class="col-sm-6 col-lg-3">
                <div class="card card-sm shadow-sm border-0">
                    <div class="card-status-start" :style="{ backgroundColor: ['#206bc4', '#4299e1', '#63b3ed', '#93c5fd', '#bfdbfe'][idx % 5] }"></div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="badge badge-outline text-blue fw-bold">{{ stats.percentages[idx] }}%</span>
                            </div>
                            <div class="col">
                                <div class="fw-bold text-truncate" style="max-width: 100px;" :title="label">
                                    {{ label }}
                                </div>
                                <div class="text-muted small">
                                    {{ stats.series[idx] }} Responden
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 text-center">
            <div class="d-inline-flex align-items-center px-3 py-1 bg-blue-lt text-blue rounded-pill fw-bold small">
                <i class="fa-solid fa-circle-info me-2"></i>
                Total akumulasi: {{ stats.total }} data jawaban
            </div>
        </div>
</div>
    </div>
</template>

<style scoped>
.card-status-top { height: 3px; }
.bg-light-lt { background-color: #f8fafc !important; }
</style>
