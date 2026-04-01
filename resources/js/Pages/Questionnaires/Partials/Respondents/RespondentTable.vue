<script setup>
import { capitalizeWords } from '../../../../Utilities/string';

const props = defineProps({
    data: Array,
    searchQuery: String
});
const emit = defineEmits(['show-answers', 'export-excel']);

const formatRoleName = (roleName) => {
    if(roleName === 'alumni') return 'Alumni';
    if(roleName === 'mitra') return 'Mitra Kerjasama';
    if(roleName === 'pengguna_lulusan') return 'Pengguna Lulusan';
    return roleName;
};
</script>

<template>
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header d-flex justify-content-between align-items-center bg-white">
            <h3 class="card-title text-primary">
                <i class="fa-solid fa-list me-2"></i> Daftar Responden
            </h3>
            <button class="btn btn-success" @click="emit('export-excel')">
                <i class="fa-solid fa-file-excel me-2"></i> Export Data (Excel)
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-vcenter card-table table-striped table-hover">
                <thead class="bg-white shadow-sm">
                    <tr>
                        <th class="w-1">No.</th>
                        <th>Nama Responden</th>
                        <th>Peran</th>
                        <th>Identitas / Instansi</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in data" :key="item.id">
                        <td class="text-muted">{{ index + 1 }}</td>
                        <td>
                            <div class="fw-bold">{{ capitalizeWords(item.name) }}</div>
                            <div class="small text-muted" v-if="item.type === 'external'">Responden Luar</div>
                        </td>
                        <td>
                            <span class="badge bg-blue-lt">{{ formatRoleName(item.role_name) }}</span>
                        </td>
                        <td>
                            <div v-if="item.type === 'internal'">
                                <span v-if="item.nim">NIM: {{ item.nim }}</span>
                                <span v-else-if="item.nidn">NIDN: {{ item.nidn }}</span>
                                <span v-else>-</span>
                            </div>
                            <div v-else>
                                <div class="fw-bold small">{{ item.company_or_institution || '-' }}</div>
                                <div class="text-muted small">{{ item.contact_number || '-' }}</div>
                            </div>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-ghost-primary btn-sm" @click="emit('show-answers', { type: item.type, id: item.id })">
                                Detail Jawaban
                            </button>
                        </td>
                    </tr>

                    <tr v-if="data.length === 0">
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fa-regular fa-face-frown fa-2x mb-2 d-block"></i>
                            Data tidak ditemukan.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer py-2 bg-light d-flex justify-content-between align-items-center" v-if="data.length > 0">
            <span class="text-muted small text-warning">Menampilkan maksimal 50 responden teratas. Gunakan filter/pencarian untuk mencari data spesifik.</span>
        </div>
    </div>
</template>
