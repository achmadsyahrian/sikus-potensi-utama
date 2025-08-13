<script setup>
import { computed, ref, nextTick } from 'vue';
import { defineProps } from 'vue';
import QuestionnaireInfoCard from './QuestionnaireInfoCard.vue';

const props = defineProps({
    questionnaire: Object,
});

const totalResponden = computed(() => {
    const uniqueUsers = new Set(props.questionnaire.answers.map(a => a.user_id));
    return uniqueUsers.size;
});

const totalQuestions = computed(() => props.questionnaire.questions.length);

const answersByQuestion = computed(() => {
    const grouped = {};
    props.questionnaire.questions.forEach(q => {
        grouped[q.id] = {
            question: q,
            answers: props.questionnaire.answers.filter(a => a.question_id === q.id),
        };
    });
    return grouped;
});

const getOptionStatistics = (questionAnswers) => {
    const total = questionAnswers.length;
    const optionCounts = {};
    props.questionnaire.options.forEach(opt => {
        optionCounts[opt.option_text] = 0;
    });

    questionAnswers.forEach(ans => {
        const opt = props.questionnaire.options.find(o => o.option_value == ans.answer_value);
        if (opt) optionCounts[opt.option_text]++;
    });

    return Object.keys(optionCounts).map(optionText => ({
        option_text: optionText,
        count: optionCounts[optionText],
        percentage: total > 0 ? ((optionCounts[optionText] / total) * 100).toFixed(1) : 0,
    }));
};

// State navigasi & accordion
const selectedQuestionId = ref('all');
const openAccordionId = ref(null);

const selectQuestion = (qid) => {
    selectedQuestionId.value = qid;
    openAccordionId.value = qid === 'all' ? null : qid;

    // Biar animasi bootstrap tetap jalan
    nextTick(() => {
        document.querySelectorAll('.accordion-collapse').forEach(el => el.classList.remove('show'));
        if (qid !== 'all') {
            const el = document.getElementById(`collapse-${qid}`);
            if (el) {
                el.classList.add('show');
            }
        }
    });
};
</script>

<template>
    <div class="card-body">
        <QuestionnaireInfoCard :questionnaire="questionnaire" />

        <div class="d-flex align-items-center justify-content-between pt-2 pb-4">
            <div>
                <h3 class="fw-bold mb-1">Ringkasan Analisis</h3>
                <h5 class="op-7 mb-2 text-muted">Ringkasan hasil dan statistik jawaban kuesioner.</h5>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div 
                class="col-sm-4" 
                v-for="(info, idx) in [
                    { icon: 'fa-users', color: 'primary', value: totalResponden, label: 'Total Responden' },
                    { icon: 'fa-question-circle', color: 'success', value: totalQuestions, label: 'Total Pertanyaan' },
                    { icon: 'fa-check-circle', color: 'warning', value: questionnaire.options.length, label: 'Opsi Jawaban' }
                ]" 
                :key="idx"
            >
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body">
                        <i :class="`fa-solid ${info.icon} fa-2x text-${info.color} mb-2`"></i>
                        <h5 class="fw-bold">{{ info.value }}</h5>
                        <p class="text-muted small mb-0">{{ info.label }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Q -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body d-flex flex-wrap gap-2">
                <button 
                    class="btn btn-sm"
                    :class="selectedQuestionId === 'all' ? 'btn-primary' : 'btn-outline-primary'"
                    @click="selectQuestion('all')"
                >
                    Semua
                </button>
                <button
                    v-for="(q, index) in questionnaire.questions"
                    :key="q.id"
                    class="btn btn-sm"
                    :class="selectedQuestionId === q.id ? 'btn-primary' : 'btn-outline-primary'"
                    @click="selectQuestion(q.id)"
                >
                    Q{{ index + 1 }}
                </button>
            </div>
        </div>

        <!-- Question Results -->
        <div class="accordion" id="accordion-results">
            <div 
                v-for="(group, qid) in answersByQuestion" 
                :key="qid"
                v-show="selectedQuestionId === 'all' || selectedQuestionId === group.question.id"
                class="accordion-item border rounded-3 mb-2"
            >
                <h2 class="accordion-header">
                    <button 
                        class="accordion-button fw-semibold"
                        :class="{ collapsed: openAccordionId !== group.question.id }"
                        type="button"
                        data-bs-toggle="collapse" 
                        :data-bs-target="`#collapse-${qid}`"
                    >
                        {{ group.question.question_text }}
                        <span class="badge ms-2" :class="group.question.question_type === 'multiple_choice' ? 'bg-blue-lt' : 'bg-purple-lt'">
                            {{ group.question.question_type === 'multiple_choice' ? 'Pilihan Ganda' : 'Teks' }}
                        </span>
                    </button>
                </h2>
                <div 
                    :id="`collapse-${qid}`" 
                    class="accordion-collapse collapse"
                    :class="{ show: selectedQuestionId !== 'all' && openAccordionId === group.question.id }"
                >
                    <div class="accordion-body">
                        <!-- Multiple Choice -->
                        <div v-if="group.question.question_type === 'multiple_choice'">
                            <div v-for="stats in getOptionStatistics(group.answers)" :key="stats.option_text" class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>{{ stats.option_text }}</span>
                                    <small class="text-muted">{{ stats.count }} ({{ stats.percentage }}%)</small>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary" :style="{width: stats.percentage + '%'}"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Text Answers -->
                        <div v-else>
                            <ul class="list-group">
                                <li v-for="(answer, idx) in group.answers" :key="idx" class="list-group-item">
                                    <p class="mb-1">{{ answer.answer_value || '-' }}</p>
                                    <small class="text-muted">
                                        {{ answer.user.name }} 
                                        <span v-if="answer.created_at"> • {{ new Date(answer.created_at).toLocaleDateString() }}</span>
                                    </small>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
