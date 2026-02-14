<script setup>
import { onMounted, computed, ref } from 'vue';
// Import Utility
import { getCriterion, calculateQuestionScore } from '@/Utilities/scoringUtils';

const props = defineProps({
    question: Object,
    options: Array,
    answers: Array,
    criteria: Array, // <--- Terima data kriteria
    index: Number
});

const chartInstance = ref(null);
const isExporting = ref(false);

// 1. Gunakan Utility untuk menghitung Skor & Kriteria
const analysis = computed(() => {
    const stats = calculateQuestionScore(props.answers, props.options);
    const criterion = getCriterion(stats.percentage, props.criteria);

    return {
        ...stats,
        criterion
    };
});

// Hitung distribusi opsi (untuk grafik)
const stats = computed(() => {
    const total = props.answers.length;
    const counts = {};
    props.options.forEach(opt => counts[opt.option_text] = 0);
    props.answers.forEach(ans => {
        const opt = props.options.find(o => o.option_value == ans.answer_value);
        if (opt) counts[opt.option_text]++;
    });

    return Object.keys(counts).map(text => ({
        label: text,
        count: counts[text],
        percentage: total > 0 ? parseFloat(((counts[text] / total) * 100).toFixed(1)) : 0
    }));
});

const downloadChart = async () => {
    if (!chartInstance.value) return;
    isExporting.value = true;
    try {
        const fileName = `Pertanyaan-${props.index + 1}`;
        const { imgURI } = await chartInstance.value.dataURI({
            width: 1200, height: 800, scale: 2
        });
        const link = document.createElement('a');
        link.href = imgURI;
        link.download = `${fileName}.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (e) {
        console.error(e);
    } finally {
        isExporting.value = false;
    }
};

onMounted(() => {
    const options = {
        chart: {
            type: "donut",
            height: 350,
            background: '#ffffff',
            toolbar: { show: false },
            fontFamily: 'inherit',
            sparkline: { enabled: false }
        },
        states: { hover: { filter: { type: 'darken', value: 0.15 } } },
        series: stats.value.map(s => s.count),
        labels: stats.value.map(s => s.label),
        colors: ['#206bc4', '#4299e1', '#63b3ed', '#93c5fd', '#bfdbfe'],
        stroke: { width: 2, colors: ['#ffffff'] },
        dataLabels: {
            enabled: true,
            formatter: (val) => `${val.toFixed(1)}%`,
            style: { fontSize: '11px', fontWeight: 'bold', colors: ['#333'] },
            dropShadow: { enabled: false }
        },
        plotOptions: {
            pie: {
                customScale: 0.8,
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            fontSize: '14px',
                            color: '#64748b',
                            formatter: () => props.answers.length
                        }
                    }
                },
                dataLabels: { offset: 40 }
            }
        },
        legend: {
            show: true,
            position: 'bottom',
            fontSize: '12px',
            markers: { radius: 12 },
            itemMargin: { horizontal: 5, vertical: 5 }
        }
    };

    chartInstance.value = new ApexCharts(document.getElementById(`chart-q-${props.question.id}`), options);
    chartInstance.value.render();
});
</script>

<template>
    <div class="card shadow-sm mb-3">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-blue-lt mb-2">Pertanyaan #{{ index + 1 }}</span>
                <h4 class="card-title mb-0 d-block text-wrap">{{ question.question_text }}</h4>
            </div>
            <button
                class="btn btn-icon btn-ghost-success border-0 shadow-none"
                @click="downloadChart"
                :disabled="isExporting || answers.length === 0"
                title="Download Chart"
            >
                <i v-if="isExporting" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-download"></i>
            </button>
        </div>

        <div class="card-body row align-items-center">
            <div class="col-md-5 border-end-md">
                <div :id="`chart-q-${question.id}`"></div>
            </div>

            <div class="col-md-7 ps-md-4">

                <div class="d-flex align-items-center mb-4 p-3 bg-light-lt rounded border border-light">
                    <div class="me-3">
                        <div class="display-6 fw-bold text-dark">{{ analysis.percentage }}%</div>
                    </div>
                    <div class="border-start ps-3">
                        <div class="text-muted text-uppercase small fw-bold">Indeks Kepuasan</div>
                        <div class="d-flex align-items-center mt-1">
                            <span class="badge me-2" :style="{ backgroundColor: analysis.criterion.color || '#6c757d' }">
                                {{ analysis.criterion.label }}
                            </span>
                            <small class="text-muted">(Rata-rata: {{ analysis.average }} / {{ analysis.maxValue }})</small>
                        </div>
                    </div>
                </div>

                <div v-for="(s, idx) in stats" :key="s.label" class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-bold small">{{ s.label }}</span>
                        <span class="text-muted smaller">{{ s.count }} ({{ s.percentage }}%)</span>
                    </div>
                    <div class="progress progress-sm shadow-none bg-light">
                        <div
                            class="progress-bar"
                            :style="{
                                width: s.percentage + '%',
                                backgroundColor: ['#206bc4', '#4299e1', '#63b3ed', '#93c5fd', '#bfdbfe'][idx % 5]
                            }"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media (min-width: 768px) {
    .border-end-md { border-right: 1px solid rgba(101, 109, 119, 0.16) !important; }
}
.smaller { font-size: 11px; }
.bg-light-lt { background-color: #f8fafc !important; }
</style>
