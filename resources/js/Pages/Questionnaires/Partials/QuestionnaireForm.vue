<script setup>
import BaseButton from '@/Components/BaseButton.vue';
import { Link } from '@inertiajs/vue3';
import QuestionnaireTargetForm from './QuestionnaireTargetForm.vue';
import QuestionnaireBasicInfoForm from './QuestionnaireBasicInfoForm.vue';

const props = defineProps({
    form: Object,
    roles: Array,
    questionnaire: Object,
    academicPeriods: Array,
    faculties: Array,
    programStudies: Array,
    isDisabled: {
        type: Boolean,
        default: false,
    },
    isEditing: {
        type: Boolean,
        default: false,
    },
    isCreate: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits(['submit', 'editToggle']);
</script>

<template>
    <form @submit.prevent="$emit('submit')">
        <!-- Form Aksi Kuesioner (Dipindahkan ke atas) -->
        <div class="card-header d-flex justify-content-end mb-3">
            <template v-if="isCreate">
                <Link :href="route('questionnaires.index')">
                    <BaseButton type="button" label="Kembali" variant="secondary" outline class="me-2" />
                </Link>
                <BaseButton type="submit" label="Simpan Kuesioner" variant="primary" :disabled="form.processing" />
            </template>
            <template v-else>
                <template v-if="isEditing">
                    <BaseButton type="button" label="Batal" variant="secondary" outline @click="emit('editToggle')"
                        class="me-2" />
                    <BaseButton type="submit" label="Simpan Perubahan" variant="primary" :disabled="form.processing" />
                </template>
                <template v-else>
                    <BaseButton type="button" label="Edit Kuesioner" variant="primary" @click="emit('editToggle')" />
                </template>
            </template>
        </div>

        <!-- Form Informasi Dasar -->
        <QuestionnaireBasicInfoForm :form="form" :academicPeriods="academicPeriods" :is-disabled="isDisabled"
            :is-editing="isEditing" :is-create="isCreate" :questionnaire="questionnaire" />

        <hr>

        <!-- Form Penargetan Kuesioner -->
        <div class="card-body">
            <QuestionnaireTargetForm :form="form" :roles="roles" :faculties="faculties" :programStudies="programStudies"
                :is-disabled="isDisabled" :is-editing="isEditing" :is-create="isCreate" />
        </div>
    </form>
</template>
