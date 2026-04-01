<script setup>
const props = defineProps({
    question: Object,
    previewAnswers: Array, // Terima 5 array preview
    totalAnswers: Number,  // Jumlah aslinya di DB
    index: Number
});

const emit = defineEmits(['open-detail']);

const getRespondentName = (ans) => ans.user?.name || ans.respondent_external?.name || 'Responden';
</script>

<template>
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <div class="list-group list-group-flush border rounded">
                <div v-for="(ans, idx) in previewAnswers" :key="idx" class="list-group-item py-3">
                    </div>
            </div>

            <div v-if="totalAnswers > previewAnswers.length" class="mt-3 text-center">
                <button class="btn btn-ghost-primary btn-sm" @click="emit('open-detail', { question, answers: previewAnswers })">
                    Lihat Total {{ totalAnswers }} Jawaban (Memerlukan API Tambahan)
                </button>
            </div>
        </div>
    </div>
</template>
