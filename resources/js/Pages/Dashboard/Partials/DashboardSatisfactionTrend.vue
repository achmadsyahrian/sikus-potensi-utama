<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    data: Array // Format sekarang: [{ period_name: '2023 Ganjil', average_score: 76.4 }, ...]
});

const chartEl = ref(null);

onMounted(() => {
    if (!props.data || props.data.length === 0) return;

    // PERBAIKAN: Langsung pakai nilainya, karena dari Controller sudah Persen (0-100)
    const seriesData = props.data.map(d => d.average_score);
    const categories = props.data.map(d => d.period_name);

    const options = {
        chart: {
            type: 'area',
            height: 300,
            fontFamily: 'inherit',
            toolbar: { show: false },
            animations: { enabled: true }
        },
        dataLabels: { enabled: false },
        fill: { opacity: 0.16, type: 'solid' },
        stroke: { width: 2, lineCap: "round", curve: "smooth" },
        series: [{ name: "Indeks Kepuasan", data: seriesData }],
        xaxis: { categories: categories },
        yaxis: {
            min: 0,
            max: 100, // Y-Axis tetap 0-100%
            labels: { formatter: (val) => val.toFixed(0) + "%" }
        },
        colors: ["#206bc4"],
        tooltip: {
            theme: 'light',
            y: { formatter: (val) => val + "%" }
        },
        grid: { strokeDashArray: 4 }
    };

    new ApexCharts(chartEl.value, options).render();
});
</script>

<template>
    <div class="card shadow-sm border-0 h-100">
        <div class="card-header border-0">
            <div>
                <h3 class="card-title">Tren Kepuasan Akademik</h3>
                <p class="card-subtitle text-muted small">Rata-rata indeks kepuasan global per periode.</p>
            </div>
        </div>
        <div class="card-body">
            <div ref="chartEl"></div>
        </div>
    </div>
</template>
