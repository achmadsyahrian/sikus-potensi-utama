<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    data: Object,
    activeRole: String,
});
</script>

<template>
    <Head title="Dashboard Admin" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard Admin</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-xl font-bold mb-4">Selamat Datang di Dashboard Administrator!</h3>
                        <p class="text-base text-gray-700 mb-4">
                            Anda saat ini beroperasi sebagai: <strong>{{ activeRole }}</strong>
                        </p>
                        
                        <div class="row g-3">
                            <!-- Card Jumlah Kuesioner Aktif -->
                            <div class="col-md-6">
                                <div class="card card-body text-bg-primary">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="fa-solid fa-list-check fa-3x"></i>
                                        </div>
                                        <div>
                                            <div class="h1 mb-1">{{ data.activeQuestionnairesCount }}</div>
                                            <div class="h5">Kuesioner Aktif</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Card Jumlah Responden -->
                            <div class="col-md-6">
                                <div class="card card-body text-bg-success">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="fa-solid fa-users fa-3x"></i>
                                        </div>
                                        <div>
                                            <div class="h1 mb-1">{{ data.totalResponsesCount }}</div>
                                            <div class="h5">Total Responden</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 text-end">
                            <Link :href="route('questionnaires.index')">
                                <BaseButton variant="primary">
                                    <i class="fa-solid fa-file-text me-2"></i> Kelola Kuesioner
                                </BaseButton>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
