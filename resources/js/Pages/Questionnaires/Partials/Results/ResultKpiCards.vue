<script setup>
const props = defineProps({
    totalRespondents:    Number,
    totalInternal:       Number,
    totalExternal:       Number,
    totalQuestions:      Number,
    totalCategories:     Number,
    bestCategory:        Object,
    worstCategory:       Object,
    respondentBreakdown: Array,
    externalBreakdown:   Array,
});

const externalRoleLabels = {
    alumni:           'Alumni',
    mitra:            'Mitra Kerjasama',
    pengguna_lulusan: 'Pengguna Lulusan',
};
</script>

<template>
    <div>
        <!-- 4 KPI Cards -->
        <div class="row g-3">
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-status-top bg-primary"></div>
                    <div class="card-body text-center">
                        <div class="text-muted small fw-semibold mb-1">Total Responden</div>
                        <div class="h1 fw-bold text-primary mb-0">{{ totalRespondents }}</div>
                        <div class="text-muted" style="font-size:11px;">
                            {{ totalInternal }} internal · {{ totalExternal }} eksternal
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-status-top bg-teal"></div>
                    <div class="card-body text-center">
                        <div class="text-muted small fw-semibold mb-1">Total Pertanyaan</div>
                        <div class="h1 fw-bold text-teal mb-0">{{ totalQuestions }}</div>
                        <div class="text-muted" style="font-size:11px;">{{ totalCategories }} aspek/kategori</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-status-top bg-success"></div>
                    <div class="card-body text-center">
                        <div class="text-muted small fw-semibold mb-1">
                            <i class="fa-solid fa-arrow-trend-up me-1 text-success"></i>Aspek Terbaik
                        </div>
                        <div class="fw-bold text-success small text-truncate" :title="bestCategory?.category_name">
                            {{ bestCategory?.category_name ?? '-' }}
                        </div>
                        <div class="text-muted" style="font-size:11px;">Skor {{ bestCategory?.score ?? 0 }}%</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-status-top bg-danger"></div>
                    <div class="card-body text-center">
                        <div class="text-muted small fw-semibold mb-1">
                            <i class="fa-solid fa-arrow-trend-down me-1 text-danger"></i>Perlu Perhatian
                        </div>
                        <div class="fw-bold text-danger small text-truncate" :title="worstCategory?.category_name">
                            {{ worstCategory?.category_name ?? '-' }}
                        </div>
                        <div class="text-muted" style="font-size:11px;">Skor {{ worstCategory?.score ?? 0 }}%</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Breakdown Responden -->
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="text-muted small fw-semibold">
                        <i class="fa-solid fa-users me-1"></i> Komposisi Responden:
                    </span>
                    <span
                        v-for="rb in respondentBreakdown" :key="rb.role_name"
                        class="badge bg-blue-lt text-blue px-3 py-2"
                    >
                        {{ rb.role_name }}: <strong class="ms-1">{{ rb.total }}</strong>
                    </span>
                    <span
                        v-for="eb in externalBreakdown" :key="eb.role_name"
                        class="badge bg-purple-lt text-purple px-3 py-2"
                    >
                        {{ externalRoleLabels[eb.role_name] ?? eb.role_name }}: <strong class="ms-1">{{ eb.total }}</strong>
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
