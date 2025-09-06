<script setup>
import { computed, ref, watch } from 'vue';
import { defineProps, defineEmits } from 'vue';
import BaseButton from '@/Components/BaseButton.vue';
import QuestionnaireInfoCard from './QuestionnaireInfoCard.vue';

const props = defineProps({
    questionnaire: Object,
    respondentType: String,
    respondentId: Number,
});

const emit = defineEmits(['back']);
const activeRoleId = ref(null);

const selectedRespondent = computed(() => {
    if (props.respondentType === 'internal') {
        const answer = props.questionnaire.answers.find(a => a.user_id === props.respondentId);
        return answer ? answer.user : null;
    } else if (props.respondentType === 'external') {
        const answer = props.questionnaire.answers.find(a => a.respondent_external_id === props.respondentId);
        return answer ? answer.respondent_external : null;
    }
    return null;
});

const userRoles = computed(() => {
    if (props.respondentType !== 'internal') return [];

    const roles = props.questionnaire.answers
        .filter(answer => answer.user_id === props.respondentId)
        .map(answer => answer.role);

    const uniqueRoles = Array.from(new Set(roles.map(r => r.id)))
        .map(id => roles.find(r => r.id === id));

    return uniqueRoles.filter(role => role !== null);
});

watch(() => props.respondentId, () => {
    if (props.respondentType === 'internal' && userRoles.value.length > 0) {
        activeRoleId.value = userRoles.value[0].id;
    } else {
        activeRoleId.value = null;
    }
}, { immediate: true });


const respondentAnswers = computed(() => {
    if (props.respondentType === 'internal') {
        if (!activeRoleId.value) return [];
        return props.questionnaire.answers.filter(a =>
            a.user_id === props.respondentId && a.role_id === activeRoleId.value
        );
    } else if (props.respondentType === 'external') {
        return props.questionnaire.answers.filter(a => a.respondent_external_id === props.respondentId);
    }
    return [];
});

const respondentName = computed(() => selectedRespondent.value?.name || '*Nama tidak ditemukan*');

const respondentDetails = computed(() => {
    if (props.respondentType === 'internal' && selectedRespondent.value) {
        if (selectedRespondent.value.student_detail) {
            return `NIM: ${selectedRespondent.value.student_detail?.nim}`;
        }
        if (selectedRespondent.value.lecturer_detail) {
            return `NIDN: ${selectedRespondent.value.lecturer_detail?.nidn}`;
        }
    } else if (props.respondentType === 'external' && selectedRespondent.value) {
        return `Perusahaan / PT: ${selectedRespondent.value.company_or_institution} <br>Kontak: ${selectedRespondent.value.contact_number}`;
    }
    return '-';
});

const activeRoleName = computed(() => {
    const role = userRoles.value.find(r => r.id === activeRoleId.value);
    return role ? role.name : 'Peran tidak ditemukan';
});

const respondentTypeDisplay = computed(() => {
    if (props.respondentType === 'internal') {
        const roles = respondentAnswers.value.map(a => a.user.roles).flat().map(r => r.name);
        return Array.from(new Set(roles)).join(', ');
    }
    return 'Eksternal';
});

const allQuestions = computed(() => props.questionnaire.questions || []);

const getAnswerObjectForQuestion = (questionId) => {
    return respondentAnswers.value.find(answer => answer.question_id === questionId);
};

const getAnswerForQuestion = (questionId) => {
    const answer = getAnswerObjectForQuestion(questionId);
    return answer?.answer_value || null;
};

const getOptionText = (optionValue) => {
    const option = props.questionnaire.options.find(o => o.option_value == optionValue);
    return option ? option.option_text : null;
};

const getCategoryName = (question) => {
    return question.category ? question.category.name : 'Tanpa Kategori';
};

const getAnswerDate = (questionId) => {
    const answer = getAnswerObjectForQuestion(questionId);
    if (answer && answer.created_at) {
        return new Date(answer.created_at).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    }
    return null;
};
</script>

<template>
    <div class="card-body">
        <QuestionnaireInfoCard :questionnaire="questionnaire" />

        <div class="d-flex align-items-center justify-content-between pt-3 pb-4 border-bottom">
            <div>
                <h3 class="fw-bold mb-1">
                    <i class="fa-solid fa-file-alt text-primary me-2"></i> Jawaban Responden
                </h3>
                <p class="text-muted mb-0">Detail jawaban kuesioner dari responden ini.</p>
            </div>
            <BaseButton type="button" variant="secondary" outline @click="emit('back')">
                <i class="fa-solid fa-arrow-left me-2"></i> Kembali
            </BaseButton>
        </div>

        <div class="row g-3 mt-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-shape bg-primary-lt text-primary rounded-circle me-3 d-flex align-items-center justify-content-center"
                            style="width:45px;height:45px;">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small text-muted">Nama Responden</div>
                            <div class="fw-bold">{{ respondentName }}</div>
                            <div v-if="respondentDetails !== '-'" class="small text-muted" v-html="respondentDetails">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-shape bg-success-lt text-success rounded-circle me-3 d-flex align-items-center justify-content-center"
                            style="width:45px;height:45px;">
                            <i class="fa-solid fa-user-tag"></i>
                        </div>
                        <div>
                            <div v-if="respondentType === 'internal'" class="fw-semibold small text-muted">Peran</div>
                            <div v-if="respondentType === 'external'" class="fw-semibold small text-muted">Jenis
                                Responden</div>
                            <div v-if="respondentType === 'internal'">
                                <div v-if="userRoles.length > 1" class="dropdown">
                                    <a class="dropdown-toggle text-dark fw-bold" href="#" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        {{ activeRoleName }}
                                    </a>
                                    <div class="dropdown-menu">
                                        <a v-for="role in userRoles" :key="role.id" class="dropdown-item"
                                            @click.prevent="activeRoleId = role.id">
                                            {{ role.name }}
                                        </a>
                                    </div>
                                </div>
                                <div v-else class="fw-bold">{{ activeRoleName }}</div>
                            </div>
                            <div v-else class="fw-bold">{{ respondentTypeDisplay }}</div>
                            <div v-if="respondentType === 'internal' && selectedRespondent?.student_detail?.study_program"
                                class="small text-muted mt-1">
                                Program Studi: {{ selectedRespondent.student_detail.study_program }}
                            </div>
                            <div v-else-if="respondentType === 'internal' && selectedRespondent?.lecturer_detail?.work_unit"
                                class="small text-muted mt-1">
                                Unit Kerja: {{ selectedRespondent.lecturer_detail.work_unit }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <h5 class="fw-bold mb-3">Jawaban Kuesioner</h5>
            <div v-for="(question, index) in allQuestions" :key="question.id" class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <div class="me-3 d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width: 32px; height: 32px;">
                            <div class="badge bg-primary rounded-circle w-100 h-100 d-flex align-items-center justify-content-center"
                                style="font-size: 0.8rem;">
                                {{ index + 1 }}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold mb-1">{{ question.question_text }}</div>
                            <small class="text-muted d-block mb-2">
                                {{ getCategoryName(question) }} •
                                <span v-if="getAnswerDate(question.id)">Dijawab pada {{ getAnswerDate(question.id)
                                }}</span>
                            </small>
                            <div v-if="question.question_type === 'multiple_choice'">
                                <div class="fw-bold"
                                    :class="getOptionText(getAnswerForQuestion(question.id)) ? 'text-primary' : 'text-danger'">
                                    {{ getOptionText(getAnswerForQuestion(question.id)) || '*Jawaban tidak ditemukan*'
                                    }}
                                </div>
                            </div>
                            <div v-else>
                                <div class="fw-bold"
                                    :class="getAnswerForQuestion(question.id) ? 'text-primary' : 'text-danger'">
                                    {{ getAnswerForQuestion(question.id) || '*Jawaban tidak ditemukan*' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>