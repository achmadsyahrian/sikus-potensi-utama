<script setup>
import { computed } from 'vue';
import { defineProps, defineEmits } from 'vue';
import BaseButton from '@/Components/BaseButton.vue';
import QuestionnaireInfoCard from './QuestionnaireInfoCard.vue';
import DataTable from '@/Components/DataTable.vue';
import BaseTooltip from '@/Components/BaseTooltip.vue';

const props = defineProps({
    questionnaire: Object,
    respondents: Object,
});

// Definisikan event yang akan dikirim ke parent
const emit = defineEmits(['show-answers']);

// Computed property untuk mengelompokkan jawaban per user

// Emit event dengan userId saat tombol diklik
const showRespondentAnswers = (userId) => {
    emit('show-answers', userId);
};

// Fungsi untuk mendapatkan warna badge berdasarkan nama peran
const getRoleBadgeColor = (roleName) => {
    switch (roleName) {
        case 'Dosen':
            return 'bg-blue-lt';
        case 'Pegawai':
            return 'bg-green-lt';
        case 'Mahasiswa':
            return 'bg-yellow-lt';
        default:
            return 'bg-gray-lt';
    }
};

const columns = [
    { key: 'index', label: 'No.', class: 'fw-bold text-dark w-1' },
    { key: 'user.name', label: 'Nama Responden', class: 'fw-bold text-dark' },
    { key: 'roles', label: 'Peran', class: 'fw-bold text-dark' },
    { key: 'identitas', label: 'NIM/NIDN', class: 'fw-bold text-dark' },
];

const tableData = computed(() => {
    return props.respondents;
});

// Tambahkan computed property baru untuk mendapatkan identitas
const getIdentitas = (item) => {
    if (!item || !item.user) return '-';

    const rolesArray = Array.isArray(item.roles) ? item.roles : Object.values(item.roles);
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
                <template #cell(identitas)="{ item }">
                    {{ getIdentitas(item) }}
                </template>
                <template #cell(actions)="{ item }">
                    <BaseTooltip title="Lihat Jawaban" data-bs-toggle="tooltip" data-bs-placement="top">
                        <BaseButton type="button" variant="primary" class="btn-icon" outline
                            @click="showRespondentAnswers(item.user.id)">
                            <i class="fa-solid fa-eye"></i>
                        </BaseButton>
                    </BaseTooltip>
                </template>
            </DataTable>
        </div>
    </div>
</template>
