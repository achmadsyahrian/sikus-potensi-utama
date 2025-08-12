<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    questionnaires: Array,
});

const userName = computed(() => {
    return usePage().props.auth.user.name;
});

// Computed property untuk mengelompokkan kuesioner berdasarkan peran target
const groupedQuestionnaires = computed(() => {
    const groups = {};
    props.questionnaires.forEach(q => {
        if (!groups[q.targetRole]) {
            groups[q.targetRole] = [];
        }
        groups[q.targetRole].push(q);
    });
    return groups;
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-xl font-bold mb-4">Selamat Datang, {{ userName }}!</h3>
                        <p class="text-base text-gray-700">Berikut adalah daftar kuesioner yang harus Anda isi.</p>
                        
                        <!-- Looping untuk setiap grup peran -->
                        <div v-for="(questionnaires, role) in groupedQuestionnaires" :key="role" class="mt-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Kuesioner untuk {{ role }}</h3>
                                </div>
                                <div class="list-group list-group-flush">
                                    <div v-if="questionnaires.length > 0" v-for="q in questionnaires" :key="q.id" class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h4 class="mb-1">{{ q.name }}</h4>
                                                <div class="text-muted small">
                                                    Batas Waktu: {{ q.dueDate }}
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span :class="q.status === 'Diisi' ? 'badge bg-success-lt me-3' : 'badge bg-warning-lt me-3'">
                                                    {{ q.status }}
                                                </span>
                                                <Link v-if="q.status === 'Belum Diisi'" href="#">
                                                    <BaseButton variant="primary" size="sm">Isi Kuesioner</BaseButton>
                                                </Link>
                                                <BaseButton v-else variant="secondary" size="sm" disabled>Sudah Diisi</BaseButton>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="list-group-item text-center text-muted">
                                        Tidak ada kuesioner yang tersedia untuk Anda dalam peran ini.
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
