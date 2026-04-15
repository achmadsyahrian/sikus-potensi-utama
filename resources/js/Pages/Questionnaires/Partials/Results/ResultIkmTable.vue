<script setup>
import { getCriterion } from '@/Utilities/scoringUtils';
import { computed } from 'vue';

const props = defineProps({
    categoryScores:       Array,
    satisfactionCriteria: Array,
});

const average = computed(() => {
    if (!props.categoryScores.length) return 0;
    return (props.categoryScores.reduce((s, c) => s + Number(c.score), 0) / props.categoryScores.length).toFixed(1);
});

const averageCriterion = computed(() => getCriterion(Number(average.value), props.satisfactionCriteria));
const sortedScores     = computed(() => [...props.categoryScores].sort((a, b) => b.score - a.score));
</script>

<template>
    <div class="mb-4 mt-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="fw-bold mb-0">
                    <i class="fa-solid fa-table me-2 text-primary"></i>
                    Tabel Indeks Kepuasan per Aspek
                </h5>
                <p class="text-muted small mb-0">Diurutkan dari skor tertinggi. Bahan laporan akreditasi.</p>
            </div>
            <div
                v-if="averageCriterion"
                class="badge px-3 py-2 text-white"
                :style="{ backgroundColor: averageCriterion.color }"
            >
                Rata-rata: {{ average }}% — {{ averageCriterion.label }}
            </div>
        </div>

        <div class="table-responsive border rounded-3 overflow-hidden">
            <table class="table table-vcenter table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 40px;">#</th>
                        <th>Aspek / Kategori</th>
                        <th class="text-center" style="width: 100px;">Skor</th>
                        <th class="text-center" style="width: 130px;">Kategori</th>
                        <th class="pe-3" style="width: 200px;">Distribusi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(cat, idx) in sortedScores" :key="cat.category_id">
                        <td class="ps-3 text-muted small">{{ idx + 1 }}</td>
                        <td class="fw-semibold">{{ cat.category_name }}</td>
                        <td class="text-center">
                            <span class="fw-bold fs-5">{{ cat.score }}<span class="text-muted small fw-normal">%</span></span>
                        </td>
                        <td class="text-center">
                            <span
                                class="badge px-3 py-1 text-white"
                                :style="{ backgroundColor: getCriterion(cat.score, satisfactionCriteria)?.color ?? '#6c757d' }"
                            >
                                {{ getCriterion(cat.score, satisfactionCriteria)?.label ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="pe-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 10px; border-radius: 99px;">
                                    <div
                                        class="progress-bar"
                                        style="border-radius: 99px; transition: width 0.6s ease;"
                                        :style="{
                                            width: cat.score + '%',
                                            backgroundColor: getCriterion(cat.score, satisfactionCriteria)?.color ?? '#206bc4'
                                        }"
                                    ></div>
                                </div>
                                <span class="text-muted small" style="min-width: 38px;">{{ cat.score }}%</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
                <tfoot class="table-light border-top">
                    <tr>
                        <td colspan="2" class="ps-3 fw-bold">Rata-rata Keseluruhan</td>
                        <td class="text-center fw-bold fs-5">
                            {{ average }}<span class="text-muted small fw-normal">%</span>
                        </td>
                        <td class="text-center">
                            <span
                                v-if="averageCriterion"
                                class="badge px-3 py-1 text-white"
                                :style="{ backgroundColor: averageCriterion.color }"
                            >
                                {{ averageCriterion.label }}
                            </span>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>
