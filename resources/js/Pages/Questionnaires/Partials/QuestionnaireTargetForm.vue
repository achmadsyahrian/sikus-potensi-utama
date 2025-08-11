<script setup>
import { defineProps, computed } from 'vue';
import QuestionnaireRoleTarget from './QuestionnaireRoleTarget.vue';
import QuestionnaireStudentTarget from './QuestionnaireStudentTarget.vue';

const props = defineProps({
    form: Object,
    roles: Array,
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
    },
});

const hasRoleTarget = (roleName) => {
    return props.form.targets.some(target => target.target_type === 'role' && String(target.target_value).toLowerCase() === String(roleName).toLowerCase());
};
</script>

<template>
    <div>
        <div class="card-title text-primary">Penargetan Kuesioner</div>
        <p class="card-subtitle mb-4">Tentukan siapa saja yang dapat mengisi kuesioner ini.</p>
        <div class="row g-3">
            <!-- Form Target Role -->
            <QuestionnaireRoleTarget
                :form="form"
                :roles="roles"
                :is-disabled="isDisabled"
                :is-editing="isEditing"
                :is-create="isCreate"
            />

            <!-- Form Target Student (hanya jika role mahasiswa terpilih) -->
            <template v-if="hasRoleTarget('Mahasiswa')">
                <QuestionnaireStudentTarget
                    :form="form"
                    :faculties="faculties"
                    :programStudies="programStudies"
                    :is-disabled="isDisabled"
                    :is-editing="isEditing"
                    :is-create="isCreate"
                />
            </template>
        </div>
    </div>
</template>
