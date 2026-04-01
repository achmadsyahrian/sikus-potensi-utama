<script setup>
import { router, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import BaseButton from '@/Components/BaseButton.vue';
import { debounce } from 'lodash';

const props = defineProps({
    filters: Object,
    academicPeriods: Array,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || 'all');
const period = ref(props.filters?.period || 'all');

const applyFilters = debounce(() => {
    router.get(
        route('questionnaires.index'),
        {
            search: search.value,
            status: status.value,
            period: period.value
        },
        { preserveState: true, replace: true }
    );
}, 300);

watch([search, status, period], () => {
    applyFilters();
});

const handleRefresh = () => {
    search.value = '';
    status.value = 'all';
    period.value = 'all';
};
</script>

<template>
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">

                <div class="col-12 col-md-4">
                    <div class="input-icon">
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Cari nama kuesioner..."
                            v-model="search"
                        >
                        <button v-if="search" @click="search = ''" class="btn-clear position-absolute top-50 end-0 translate-middle-y me-2 border-0 bg-transparent text-muted">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <div class="col-6 col-md-2">
                    <select class="form-select text-muted" v-model="status">
                        <option value="all">Semua Status</option>
                        <option value="active">🟢 Aktif</option>
                        <option value="inactive">🔴 Non-aktif</option>
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <select class="form-select text-muted" v-model="period">
                        <option value="all">Semua Periode</option>
                        <option v-for="ap in academicPeriods" :key="ap.id" :value="ap.id">
                            {{ ap.name }}
                        </option>
                    </select>
                </div>

                <div class="col-12 col-md-auto ms-auto d-flex gap-2">
                    <BaseButton type="button" variant="secondary" outline @click="handleRefresh" title="Reset Filter">
                        <i class="fa-solid fa-arrow-rotate-right me-2"></i> Refresh
                    </BaseButton>

                    <Link :href="route('questionnaires.create')">
                        <BaseButton type="button" variant="primary">
                            <i class="fa-solid fa-plus me-2"></i> Kuesioner Baru
                        </BaseButton>
                    </Link>
                </div>

            </div>
        </div>
    </div>
</template>

<style scoped>
.btn-clear {
    z-index: 10;
    transition: color 0.2s;
}
.btn-clear:hover {
    color: #1e293b !important;
}
</style>
