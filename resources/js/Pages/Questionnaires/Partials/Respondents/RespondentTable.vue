<script setup>
const props = defineProps({
    data: Array
});

const emit = defineEmits(['show-answers']);

// Helper: Ambil Identitas (NIM/NIDN/Kontak/Perusahaan)
const getIdentitas = (item) => {
    if (item.type === 'external') {
        const ext = item.respondent_external;
        // Jika Alumni: Tampilkan Kontak
        // Jika Mitra/User: Tampilkan Perusahaan + Kontak
        if (ext.role === 'alumni') {
            return ext.contact_number; // Alumni biasanya tidak ada perusahaan
        }
        return `${ext.company_or_institution || '-'} (${ext.contact_number})`;
    }

    // Internal
    if (!item.user) return '-';
    if (item.user.student_detail) return `NIM: ${item.user.student_detail.nim}`;
    if (item.user.lecturer_detail) return `NIDN: ${item.user.lecturer_detail.nidn}`;
    return '-';
};

// Helper: Ambil Label Role untuk Tampilan
const getRoles = (item) => {
    if (item.type === 'external') {
        const r = item.respondent_external?.role;
        if (r === 'alumni') return ['Alumni'];
        if (r === 'mitra') return ['Mitra Kerjasama'];
        if (r === 'pengguna_lulusan') return ['Pengguna Lulusan'];
        return ['Eksternal'];
    }
    return item.roles.map(r => r.name);
};

// Helper: Warna Badge Role
const getRoleBadgeColor = (name) => {
    const colors = {
        'Dosen': 'bg-blue',
        'Mahasiswa': 'bg-yellow',
        'Pegawai': 'bg-green',
        'Alumni': 'bg-purple',
        'Mitra Kerjasama': 'bg-orange',
        'Pengguna Lulusan': 'bg-teal',
        'Eksternal': 'bg-gray'
    };
    return (colors[name] || 'bg-secondary') + '-lt';
};
</script>

<template>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
            <table class="table table-vcenter card-table table-striped table-hover">
                <thead class="sticky-top bg-white shadow-sm" style="z-index: 10;">
                    <tr>
                        <th class="w-1">No.</th>
                        <th>Nama Responden</th>
                        <th>Peran</th>
                        <th>Identitas / Instansi</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in data" :key="index">
                        <td class="text-muted">{{ index + 1 }}.</td>

                        <td>
                            <div class="fw-bold text-dark">
                                {{ item.type === 'external' ? item.respondent_external?.name : item.user?.name }}
                            </div>
                            <div class="small text-muted" v-if="item.type === 'external'">
                                <i class="fa-solid fa-globe me-1"></i> Responden Luar
                            </div>
                        </td>

                        <td>
                            <span v-for="roleName in getRoles(item)" :key="roleName" class="badge me-1" :class="getRoleBadgeColor(roleName)">
                                {{ roleName }}
                            </span>
                        </td>

                        <td>
                            <div v-if="item.type === 'internal' && item.user?.student_detail">
                                <div class="fw-bold small">{{ item.user.student_detail.nim }}</div>
                                <div class="text-muted small">{{ item.user.student_detail.study_program }}</div>
                            </div>
                            <div v-else-if="item.type === 'internal' && item.user?.lecturer_detail">
                                <div class="fw-bold small">NIDN: {{ item.user.lecturer_detail.nidn }}</div>
                            </div>
                            <div v-else-if="item.type === 'external'">
                                <div class="fw-bold small text-truncate" style="max-width: 200px;" :title="item.respondent_external?.company_or_institution">
                                    {{ item.respondent_external?.role === 'alumni' ? 'Lulusan UPU' : (item.respondent_external?.company_or_institution || '-') }}
                                </div>
                                <div class="text-muted small">
                                    <i class="fa-solid fa-phone me-1 text-green" style="font-size: 10px;"></i>
                                    {{ item.respondent_external?.contact_number }}
                                </div>
                            </div>
                            <div v-else class="text-muted">-</div>
                        </td>

                        <td class="text-center">
                            <button type="button" class="btn btn-ghost-primary btn-icon btn-sm"
                                @click="emit('show-answers', { type: item.type, id: item.id })"
                                title="Lihat Jawaban Detail">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </td>
                    </tr>

                    <tr v-if="data.length === 0">
                        <td colspan="5" class="text-center py-5 text-muted">
                            <div class="empty-icon">
                                <i class="fa-solid fa-magnifying-glass fa-2x"></i>
                            </div>
                            <div class="mt-2">Data responden tidak ditemukan sesuai filter.</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer py-2 bg-light d-flex justify-content-between align-items-center">
            <span class="text-muted small">Menampilkan <strong>{{ data.length }}</strong> responden.</span>
        </div>
    </div>
</template>

<style scoped>
/* Styling tambahan untuk badge agar teks lebih mudah dibaca */
.badge { font-weight: 600; letter-spacing: 0.3px; }
</style>
