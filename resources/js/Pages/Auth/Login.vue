<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { reactive } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import BaseAlert from '@/Components/BaseAlert.vue';
import GedungUpuImage from '@/assets/img/university/gedung-upu.jpg';
import BaseInput from '@/Components/BaseInput.vue';

const page = usePage();
const appName = page.props.app_name;

const form = reactive({
    email: '',
    password: '',
    remember: false,
});
</script>

<template>

    <Head :title="`Login - ${appName}`" />

    <div class="row g-0 flex-fill">
        <div
            class="col-12 col-lg-6 col-xl-4 bg-light border-top-wide border-primary d-flex flex-column justify-content-center">
            <div class="container container-tight my-5 px-lg-5">
                <div class="text-center mb-4">
                    <a href="#" class="navbar-brand navbar-brand-autodark">
                        <ApplicationLogo width="100" />
                    </a>
                </div>
                <h2 class="h3 text-center mb-1">
                    SIKUS
                </h2>
                <p class="text-center text-muted mb-3">
                    Sistem Kuesioner & Survei Universitas Potensi Utama
                </p>

                <BaseAlert type="info" title="Gunakan akun SIAKAD Anda"
                    message="Email dan password sama dengan yang digunakan di sistem SIAKAD" />

                <form @submit.prevent="" autocomplete="off" novalidate>
                    <BaseInput label="Email" type="email" placeholder="Email Anda" v-model="form.email" />
                    <BaseInput label="Password" type="password" placeholder="Password Anda" v-model="form.password"/>

                    <div class="mb-2">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" v-model="form.remember" />
                            <span class="form-check-label">Ingat saya</span>
                        </label>
                    </div>
                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </div>
                </form>

            </div>
        </div>
        <div class="col-12 col-lg-6 col-xl-8 d-none d-lg-block">
            <div class="bg-cover h-100 min-vh-100"
                :style="{ backgroundImage: `linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url(${GedungUpuImage})` }">
            </div>
        </div>
    </div>
</template>