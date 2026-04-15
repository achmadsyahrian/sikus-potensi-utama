<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    questionnaire: Object,
});

const tabs = [
    { label: 'Info Dasar',     icon: 'fa-circle-info',        routeName: 'questionnaires.show' },
    { label: 'Kategori',       icon: 'fa-layer-group',        routeName: 'questionnaires.categories' },
    { label: 'Opsi Jawaban',   icon: 'fa-list-check',         routeName: 'questionnaires.options' },
    { label: 'Pertanyaan',     icon: 'fa-clipboard-question', routeName: 'questionnaires.questions' },
    { label: 'Hasil Analisis', icon: 'fa-chart-pie',          routeName: 'questionnaires.results' },
    { label: 'Responden',      icon: 'fa-users',              routeName: 'questionnaires.respondents' },
];

const isActive = (routeName) => route().current(routeName);
</script>

<template>
    <div class="col-12 col-md-3 col-lg-2">
        <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
            <div class="card-body p-2">
                <div class="d-flex flex-column gap-1">
                    <Link
                        v-for="tab in tabs"
                        :key="tab.routeName"
                        :href="route(tab.routeName, questionnaire.id)"
                        class="tab-link d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none"
                        :class="isActive(tab.routeName) ? 'tab-link-active' : 'tab-link-inactive'"
                    >
                        <i :class="`fa-solid ${tab.icon}`" style="width: 16px; text-align: center;"></i>
                        <span class="small fw-semibold">{{ tab.label }}</span>
                    </Link>
                </div>
            </div>
            <div class="card-footer border-0 bg-transparent p-3">
                <div class="text-muted" style="font-size: 11px;">
                    <div class="d-flex align-items-center gap-1 mb-1">
                        <i class="fa-solid fa-calendar-days fa-xs"></i>
                        <span>{{ questionnaire.academic_period?.name ?? '-' }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <i class="fa-solid fa-clock fa-xs"></i>
                        <span>s/d {{ questionnaire.formatted_end_date ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.tab-link { transition: all 0.15s ease; border: 1px solid transparent; }
.tab-link-active { background-color: #e8f0fe; color: #206bc4 !important; border-color: #c5d8f8; }
.tab-link-inactive { color: #64748b !important; }
.tab-link-inactive:hover { background-color: #f1f5f9; color: #1e293b !important; }
</style>
