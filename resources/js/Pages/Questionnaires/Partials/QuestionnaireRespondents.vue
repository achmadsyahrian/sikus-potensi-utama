<script setup>
import { computed, ref } from 'vue';
import { defineProps, defineEmits } from 'vue';
import BaseButton from '@/Components/BaseButton.vue';
import QuestionnaireInfoCard from './QuestionnaireInfoCard.vue';
import DataTable from '@/Components/DataTable.vue';
import BaseTooltip from '@/Components/BaseTooltip.vue';

const props = defineProps({
    questionnaire: Object,
    respondents: Object,
});

const emit = defineEmits(['show-answers']);

const selectedRole = ref('all');

const showRespondentAnswers = (type, id) => {
    emit('show-answers', { type, id });
};

const getRoleBadgeColor = (roleName) => {
    switch (roleName) {
        case 'Dosen':
            return 'bg-blue-lt';
        case 'Pegawai':
            return 'bg-green-lt';
        case 'Mahasiswa':
            return 'bg-yellow-lt';
        case 'Eksternal':
            return 'bg-purple-lt';
        default:
            return 'bg-gray-lt';
    }
};

const columns = [
    { key: 'index', label: 'No.', class: 'fw-bold text-dark w-1' },
    { key: 'name', label: 'Nama Responden', class: 'fw-bold text-dark' },
    { key: 'roles', label: 'Peran', class: 'fw-bold text-dark' },
    { key: 'details', label: 'Identitas / Perusahaan', class: 'fw-bold text-dark' },
    // { key: 'actions', label: 'Aksi', class: 'text-center fw-bold text-dark' }
];

const availableRoles = computed(() => {
    const rolesSet = new Set();
    rolesSet.add('Semua');
    props.respondents.data.forEach(item => {
        item.roles.forEach(role => rolesSet.add(role.name));
    });
    return Array.from(rolesSet);
});

const tableData = computed(() => {
    const filteredData = props.respondents.data.filter(item => {
        if (selectedRole.value === 'all' || selectedRole.value === 'Semua') {
            return true;
        }
        return item.roles.some(role => role.name === selectedRole.value);
    });

    return {
        ...props.respondents,
        data: filteredData,
        total: filteredData.length,
    };
});

const getIdentitas = (item) => {
    if (!item.user) return '-';

    const rolesArray = item.user.roles ? (Array.isArray(item.user.roles) ? item.user.roles : Object.values(item.user.roles)) : [];
    const isMahasiswa = rolesArray.some(role => role.name === 'Mahasiswa');
    const isDosen = rolesArray.some(role => role.name === 'Dosen');

    if (isMahasiswa) {
        return item.user.student_detail?.nim || '-';
    }
    if (isDosen) {
        return item.user.lecturer_detail?.nidn || '-';
    }
    return '-';
};
</script>

<template>
    <div class="card-body">
        <QuestionnaireInfoCard :questionnaire="questionnaire" />

        <div class="d-flex align-items-center justify-content-between pt-2 pb-4">
            <div>
                <h3 class="fw-bold mb-1">Daftar Responden</h3>
                <h5 class="op-7 mb-2 text-muted">Lihat daftar peserta yang telah mengisi kuesioner beserta ringkasan
                    jawaban mereka.</h5>
            </div>
            <span class="badge bg-green-lt text-green">
                {{ respondents.total }} Responden
            </span>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-4">
            <button :class="['btn', 'btn-sm', selectedRole === 'all' ? 'btn-primary' : 'btn-outline-primary']"
                @click="selectedRole = 'all'">
                Semua
            </button>
            <button v-for="role in availableRoles.filter(r => r !== 'Semua')" :key="role"
                :class="['btn', 'btn-sm', selectedRole === role ? 'btn-primary' : 'btn-outline-primary']"
                @click="selectedRole = role">
                {{ role }}
            </button>
        </div>

        <div class="card">
            <DataTable :data="tableData" :columns="columns">
                <template #cell(index)="{ item, index }">
                    {{ tableData.from + index }}.
                </template>
                <template #cell(roles)="{ item }">
                    <span v-for="role in item.roles" :key="role.id" class="badge me-1"
                        :class="getRoleBadgeColor(role.name)">
                        {{ role.name }}
                    </span>
                </template>
                <template #cell(actions)="{ item }">
                    <BaseTooltip title="Lihat Jawaban" data-bs-toggle="tooltip" data-bs-placement="top">
                        <BaseButton type="button" variant="primary" class="btn-icon" outline
                            @click="showRespondentAnswers(item.type, item.id)">
                            <i class="fa-solid fa-eye"></i>
                        </BaseButton>
                    </BaseTooltip>
                </template>
            </DataTable>
        </div>
    </div>
</template>
