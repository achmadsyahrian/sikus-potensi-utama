<script setup>
import { computed, ref } from 'vue';
import QuestionnaireInfoCard from './QuestionnaireInfoCard.vue'; // Sesuaikan path import
import RespondentSummary from './Respondents/RespondentSummary.vue';
import RespondentFilter from './Respondents/RespondentFilter.vue';
import RespondentTable from './Respondents/RespondentTable.vue';

const props = defineProps({
    questionnaire: Object,
    respondents: Object,
    programStudies: Array,
});

const emit = defineEmits(['show-answers']);

// State Filter
const searchQuery = ref('');
const selectedRole = ref('all');
const selectedProdi = ref('all');

// 1. Ambil Daftar Role Unik (Termasuk Eksternal Spesifik)
const availableRoles = computed(() => {
    const rolesSet = new Set();

    if (props.respondents && props.respondents.data) {
        props.respondents.data.forEach(item => {
            if (item.type === 'external' && item.respondent_external) {
                // Konversi slug role ke Nama Cantik
                const roleSlug = item.respondent_external.role;
                let roleName = 'Eksternal';
                if (roleSlug === 'alumni') roleName = 'Alumni';
                else if (roleSlug === 'mitra') roleName = 'Mitra Kerjasama';
                else if (roleSlug === 'pengguna_lulusan') roleName = 'Pengguna Lulusan';

                rolesSet.add(roleName);
            } else if (item.roles) {
                item.roles.forEach(r => rolesSet.add(r.name));
            }
        });
    }
    return Array.from(rolesSet);
});

// 2. Logic Filter Utama (Search & Dropdown)
const filteredData = computed(() => {
    if (!props.respondents?.data) return [];

    return props.respondents.data.filter(item => {
        // --- A. Normalisasi Data (Biar gampang difilter) ---
        let itemName = '';
        let itemRoles = [];
        let itemProdiCode = null;
        let itemIdentity = ''; // NIM/NIDN/Kontak

        if (item.type === 'external') {
            // Data Eksternal
            itemName = item.respondent_external?.name || 'Tanpa Nama';
            itemIdentity = item.respondent_external?.contact_number || '';

            // Mapping Role Eksternal
            const slug = item.respondent_external?.role;
            if (slug === 'alumni') itemRoles.push('Alumni');
            else if (slug === 'mitra') itemRoles.push('Mitra Kerjasama');
            else if (slug === 'pengguna_lulusan') itemRoles.push('Pengguna Lulusan');
            else itemRoles.push('Eksternal');

        } else {
            // Data Internal (User)
            itemName = item.user?.name || 'Tanpa Nama';
            itemRoles = item.roles.map(r => r.name);
            itemProdiCode = item.user?.student_detail?.program_study_code; // Hanya mahasiswa punya prodi

            // Gabung NIM & NIDN untuk pencarian
            const nim = item.user?.student_detail?.nim || '';
            const nidn = item.user?.lecturer_detail?.nidn || '';
            itemIdentity = `${nim} ${nidn}`;
        }

        // --- B. Eksekusi Filter ---

        // 1. Filter Role
        const roleMatch = selectedRole.value === 'all' || itemRoles.includes(selectedRole.value);

        // 2. Filter Prodi (Hanya berlaku jika Internal Mahasiswa)
        // Jika item tidak punya prodi, dan user memilih filter prodi tertentu -> Hide item
        // Kecuali user pilih 'all', maka tampilkan semua.
        let prodiMatch = true;
        if (selectedProdi.value !== 'all') {
            prodiMatch = itemProdiCode == selectedProdi.value;
        }

        // 3. Filter Search (Nama & Identitas)
        const query = searchQuery.value.toLowerCase();
        const searchMatch = !query ||
                            itemName.toLowerCase().includes(query) ||
                            itemIdentity.toLowerCase().includes(query);

        return roleMatch && prodiMatch && searchMatch;
    });
});

const handleShowAnswers = (payload) => {
    emit('show-answers', payload);
}
</script>

<template>
    <div class="card-body">
        <QuestionnaireInfoCard :questionnaire="questionnaire" />

        <RespondentSummary :respondents="respondents" />

        <RespondentFilter
            v-model:searchQuery="searchQuery"
            v-model:selectedRole="selectedRole"
            v-model:selectedProdi="selectedProdi"
            :availableRoles="availableRoles"
            :programStudies="programStudies"
        />

        <div class="mt-3">
            <RespondentTable
                :data="filteredData"
                @show-answers="handleShowAnswers"
            />
        </div>
    </div>
</template>
