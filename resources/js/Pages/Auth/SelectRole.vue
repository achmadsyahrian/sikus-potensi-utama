<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import GedungUpuImage from '@/assets/img/university/gedung-upu.jpg';
import FlashAlert from '@/Components/FlashAlert.vue';

const page = usePage();

const props = defineProps({
    userRoles: {
        type: Array,
        required: true,
    }
});

const user = page.props.auth.user;

const form = useForm({
    role_id: null
});

const selectRole = (roleId) => {
    form.role_id = roleId;
    form.post(route('role-selection.store'));
};
</script>

<template>

    <Head title="Pilih Peran" />
    <div class="min-vh-100 d-flex align-items-center" style="background-color: #f5f7fa">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="text-center mb-4">
                        <ApplicationLogo width="90" class="mx-auto d-block" />
                        <h1 class="h3 mt-2 text-primary">SIKUS</h1>
                        <p class="text-muted small">
                            Sistem Kuesioner & Survei Universitas Potensi Utama
                        </p>
                    </div>
                    <div class="bg-white p-4 p-md-5 rounded-3 shadow" style="border-top: 4px solid #0062cc">

                        <!-- Identitas User -->
                        <div class="d-flex align-items-center mb-4 p-3 rounded-3 bg-light">
                            <img :src="user.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.name) + '&background=0062cc&color=fff&size=64'"
                                alt="User Avatar" class="rounded-circle me-3 shadow-sm"
                                style="width: 64px; height: 64px; object-fit: cover;">
                            <div>
                                <h6 class="mb-0 fw-bold">{{ user.name }}</h6>
                                <small class="text-muted">{{ user.email }}</small>
                            </div>
                        </div>

                        <h2 class="h5 text-center mb-3 fw-bold text-uppercase" style="color: #0062cc">
                            Pilih Peran
                        </h2>
                        <FlashAlert />
                        <p class="text-center text-muted mb-4">
                            Silakan pilih peran yang ingin Anda gunakan.
                        </p>

                        <div class="list-group">
                            <button v-for="role in userRoles" :key="role.id" @click="selectRole(role.id)"
                                class="list-group-item list-group-item-action border-0 py-3"
                                :class="{ 'active': form.role_id === role.id }" :disabled="form.processing"
                                style="transition: all 0.3s ease">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>{{ role.name }}</span>
                                    <i class="fas fa-chevron-right small"></i>
                                </div>
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.list-group-item {
    margin-bottom: 0.75rem;
    border-radius: 0.5rem !important;
    border-left: 3px solid transparent;
}

.list-group-item:not(.active):hover {
    background-color: #f8f9fa;
    border-left: 3px solid #0062cc;
    transform: translateX(3px);
}

.list-group-item.active {
    background-color: #0062cc;
    border-left: 3px solid #004a99;
    color: #ffffff;
}

.bg-white {
    background-color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.text-primary {
    color: #0062cc !important;
}
</style>
