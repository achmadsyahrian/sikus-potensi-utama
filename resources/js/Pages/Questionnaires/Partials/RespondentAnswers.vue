<script setup>
import { computed, ref, watch } from 'vue';
import { defineProps, defineEmits } from 'vue';
import BaseButton from '@/Components/BaseButton.vue';
import QuestionnaireInfoCard from './QuestionnaireInfoCard.vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    questionnaire: Object,
    userId: Number,
});

const emit = defineEmits(['back']);
const page = usePage();

// State reaktif untuk menyimpan ID peran yang sedang aktif
const activeRoleId = ref(null);

const selectedUser = computed(() => {
    return props.questionnaire.answers.find(answer => answer.user_id === props.userId)?.user;
});

// Computed property untuk mendapatkan semua peran unik dari user yang dipilih
const userRoles = computed(() => {
    const roles = props.questionnaire.answers
        .filter(answer => answer.user_id === props.userId)
        .map(answer => answer.role);
    
    // Gunakan Set untuk memastikan peran unik
    const uniqueRoles = Array.from(new Set(roles.map(r => r.id)))
        .map(id => roles.find(r => r.id === id));
    
    return uniqueRoles.filter(role => role !== null);
});

// Set activeRoleId ke peran pertama saat komponen dimuat atau userId berubah
watch(() => props.userId, (newUserId) => {
    if (newUserId && userRoles.value.length > 0) {
        activeRoleId.value = userRoles.value[0].id;
    }
}, { immediate: true });

// Computed property untuk mengelompokkan jawaban berdasarkan user dan peran yang aktif
const userAnswersByActiveRole = computed(() => {
    if (!activeRoleId.value) return [];
    
    // Filter jawaban berdasarkan user dan peran yang sedang aktif
    return props.questionnaire.answers.filter(answer => 
        answer.user_id === props.userId && answer.role_id === activeRoleId.value
    );
});

// Computed property untuk mengambil nama peran yang aktif
const activeRoleName = computed(() => {
    const role = userRoles.value.find(r => r.id === activeRoleId.value);
    return role ? role.name : 'Peran tidak ditemukan';
});

const allQuestions = computed(() => {
    return props.questionnaire.categories.flatMap(category => category.questions);
});

const getAnswerForQuestion = (questionId) => {
    return userAnswersByActiveRole.value.find(answer => answer.question_id === questionId)?.answer_value;
};

const getOptionText = (optionValue) => {
    const option = props.questionnaire.options.find(o => o.option_value == optionValue);
    return option ? option.option_text : null;
};
</script>

<template>
    <div class="card-body">
        <QuestionnaireInfoCard :questionnaire="questionnaire" />

        <!-- Header -->
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
        
        <!-- Info Responden -->
        <div class="row g-3 mt-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-shape bg-primary-lt text-primary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width:45px;height:45px;">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small text-muted">Nama Responden</div>
                            <div class="fw-bold">{{ selectedUser?.name }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-shape bg-success-lt text-success rounded-circle me-3 d-flex align-items-center justify-content-center" style="width:45px;height:45px;">
                            <i class="fa-solid fa-user-tag"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small text-muted">Peran</div>
                            <!-- Dropdown untuk memilih peran -->
                            <div class="dropdown">
                                <a class="dropdown-toggle text-dark fw-bold" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                    {{ activeRoleName }}
                                </a>
                                <div class="dropdown-menu">
                                    <a v-for="role in userRoles" :key="role.id" class="dropdown-item" @click.prevent="activeRoleId = role.id">
                                        {{ role.name }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- List Pertanyaan & Jawaban -->
        <div class="mt-4">
            <h5 class="fw-bold mb-3">Jawaban Kuesioner</h5>
            <div v-for="(question, index) in allQuestions" :key="question.id" class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <div class="badge bg-primary rounded-circle me-3" style="width: 32px; height: 32px; display:flex; align-items:center; justify-content:center;">
                            {{ index + 1 }}
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold mb-2">{{ question.question_text }}</div>
                            <div v-if="question.question_type === 'multiple_choice'">
                                <div class="fw-bold" :class="getOptionText(getAnswerForQuestion(question.id)) ? 'text-primary' : 'text-danger'">
                                     {{ getOptionText(getAnswerForQuestion(question.id)) || '**Jawaban tidak ditemukan**' }}
                                </div>
                            </div>
                            <div v-else>
                                <div class="fw-bold" :class="getAnswerForQuestion(question.id) ? 'text-primary' : 'text-danger'">
                                    {{ getAnswerForQuestion(question.id) || '**Jawaban tidak ditemukan**' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
