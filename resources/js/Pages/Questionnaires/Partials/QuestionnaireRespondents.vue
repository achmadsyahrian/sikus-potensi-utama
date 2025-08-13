<script setup>
import { computed } from 'vue';
import { defineProps, defineEmits } from 'vue';
import BaseButton from '@/Components/BaseButton.vue';
import QuestionnaireInfoCard from './QuestionnaireInfoCard.vue';
import DataTable from '@/Components/DataTable.vue';
import BaseTooltip from '@/Components/BaseTooltip.vue';

const props = defineProps({
    questionnaire: Object,
});

// Definisikan event yang akan dikirim ke parent
const emit = defineEmits(['show-answers']);

// Computed property untuk mengelompokkan jawaban per user
const respondents = computed(() => {
    const groupedUsers = {};
    props.questionnaire.answers.forEach(answer => {
        if (!groupedUsers[answer.user_id]) {
            // Inisialisasi dengan data user dan array kosong untuk peran dan jawaban
            groupedUsers[answer.user_id] = {
                user: answer.user,
                roles: [],
                answers: [],
            };
        }
        
        // Cek jika peran sudah ada di daftar unik, jika belum, tambahkan
        const roleExists = groupedUsers[answer.user_id].roles.some(role => role.id === answer.role.id);
        if (answer.role && !roleExists) {
            groupedUsers[answer.user_id].roles.push(answer.role);
        }

        groupedUsers[answer.user_id].answers.push(answer);
    });
    return Object.values(groupedUsers);
});

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
    { key: 'identitas', label: 'Identitas', class: 'fw-bold text-dark' },
];

const tableData = computed(() => {
    return {
        data: respondents.value,
        links: [],
        last_page: 1,
        current_page: 1,
        from: 1,
        to: respondents.value.length,
        total: respondents.value.length,
    };
});

// Tambahkan computed property baru untuk mendapatkan identitas
const getIdentitas = (item) => {
    if (!item || !item.user) return '-';

    const isMahasiswa = item.roles.some(role => role.name === 'Mahasiswa');
    const isDosen = item.roles.some(role => role.name === 'Dosen');

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
                {{ respondents.length }} Responden
            </span>
        </div>

        <div class="card">
            <DataTable :data="tableData" :columns="columns">
                <template #cell(index)="{ index }">
                    {{ index + 1 }}.
                </template>
                <template #cell(roles)="{ item }">
                    <span v-for="role in item.roles" :key="role.id" class="badge me-1" :class="getRoleBadgeColor(role.name)">
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
