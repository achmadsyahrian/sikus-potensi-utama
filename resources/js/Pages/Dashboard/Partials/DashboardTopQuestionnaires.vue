<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    questionnaires: Array
});
</script>

<template>
    <div class="card shadow-sm border-0 h-100">
        <div class="card-header border-0 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="card-title text-primary">Performa Kuesioner</h3>
                <p class="card-subtitle text-muted small">5 aktivitas kuesioner terbaru.</p>
            </div>
            <Link :href="route('questionnaires.index')" class="btn btn-sm btn-ghost-primary">Lihat Semua</Link>
        </div>

        <div class="list-group list-group-flush">
            <div v-for="q in questionnaires" :key="q.id" class="list-group-item py-3">
                <div class="row align-items-center">

                    <div class="col">
                        <div class="d-flex align-items-center mb-1">
                            <span class="text-truncate fw-bold text-dark d-block" style="max-width: 200px;" :title="q.name">
                                {{ q.name }}
                            </span>
                            <span v-if="q.status === 'Active'" class="ms-2 badge badge-sm bg-green-lt" style="font-size: 9px;">Aktif</span>
                        </div>

                        <div class="text-muted small mb-2">
                            <i class="fa-solid fa-calendar-day me-1"></i> {{ q.period }}
                        </div>

                        <div v-if="q.total_responses > 0" class="d-flex align-items-center">
                            <div class="progress progress-sm flex-grow-1" style="height: 6px; background-color: #f1f5f9;">
                                <div class="progress-bar rounded"
                                     :style="{ width: q.percentage + '%', backgroundColor: q.color }"></div>
                            </div>
                            <span class="ms-2 fw-bold small" :style="{ color: q.color }">{{ q.percentage }}%</span>
                        </div>

                        <div v-else class="d-flex align-items-center text-muted small italic">
                            <i class="fa-solid fa-spinner fa-spin-pulse me-2" style="font-size: 10px;"></i>
                            Menunggu responden pertama...
                        </div>
                    </div>

                    <div class="col-auto text-end ps-3">
                        <div class="mb-1">
                            <span v-if="q.total_responses > 0" class="badge"
                                :style="{
                                    backgroundColor: q.color + '15',
                                    color: q.color,
                                    border: '1px solid ' + q.color + '40'
                                }">
                                {{ q.label }}
                            </span>
                            <span v-else class="badge bg-light text-muted border">
                                Belum Terdata
                            </span>
                        </div>
                        <div class="text-muted small" style="font-size: 11px;">
                            <strong>{{ q.total_responses }}</strong> Responden
                        </div>
                    </div>

                    <div class="col-auto">
                        <Link :href="route('questionnaires.show', q.id)" class="btn btn-icon btn-sm btn-ghost-secondary border-0">
                            <i class="fa-solid fa-chevron-right"></i>
                        </Link>
                    </div>
                </div>
            </div>

            <div v-if="questionnaires.length === 0" class="p-5 text-center text-muted">
                <i class="fa-regular fa-folder-open fa-2x mb-2 text-light"></i>
                <p class="small">Belum ada data kuesioner.</p>
            </div>
        </div>
    </div>
</template>
