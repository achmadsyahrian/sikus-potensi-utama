<script setup>
const props = defineProps({
    question: Object,
    answers: Array,
    index: Number
});

const emit = defineEmits(['open-detail']);

const getRespondentName = (ans) => ans.user?.name || ans.respondent_external?.name || 'Responden';
</script>

<template>
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header py-3 bg-white">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-purple-lt">Q{{ index + 1 }}</span>
                <h4 class="card-title mb-0">{{ question.question_text }}</h4>
            </div>
        </div>
        <div class="card-body">
            <div class="list-group list-group-flush border rounded">
                <div v-for="(ans, idx) in answers.slice(0, 5)" :key="idx" class="list-group-item py-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar avatar-sm rounded-circle bg-blue-lt text-uppercase">{{ getRespondentName(ans).charAt(0) }}</span>
                        </div>
                        <div class="col">
                            <div class="fw-semibold small">{{ getRespondentName(ans) }}</div>
                            <div class="text-muted small">"{{ ans.answer_value }}"</div>
                        </div>
                        <div class="col-auto text-muted small" style="font-size: 10px;">
                            {{ new Date(ans.created_at).toLocaleDateString('id-ID') }}
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="answers.length > 5" class="mt-3 text-center">
                <button class="btn btn-ghost-primary btn-sm" @click="emit('open-detail', { question, answers })">
                    Lihat Semua {{ answers.length }} Jawaban
                </button>
            </div>
        </div>
    </div>
</template>
