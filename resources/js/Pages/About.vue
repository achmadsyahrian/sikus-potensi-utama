<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();
const appName = page.props.app_name;

</script>

<template>

    <Head :title="`Tentang Kami - ${appName}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Tentang Kami
                    </h2>
                </div>
            </div>
        </template>

        <div class="row row-cards">
            <div class="col-lg-12">
                <div class="card card-lg">
                    <div class="card-body">
                        <div class="markdown">
                            <h3 class="fw-bold mb-3">Apa itu SIKU?</h3>
                            <p>
                                <strong>SIKU</strong> adalah singkatan dari **Sistem Kuesioner**. Aplikasi
                                ini
                                merupakan platform layanan kuesioner terintegrasi yang dirancang khusus untuk memenuhi
                                kebutuhan
                                evaluasi di **Universitas Potensi Utama**.
                            </p>
                            <p>
                                Tujuan utama SIKU adalah memfasilitasi pengumpulan data yang akurat dan relevan dari
                                berbagai
                                pihak, termasuk **mahasiswa, dosen, staf pegawai, dan mitra**. Dengan sistem ini,
                                Universitas
                                dapat memperoleh umpan balik yang berharga untuk terus meningkatkan kualitas layanan dan
                                operasionalnya.
                            </p>
                            <p>
                                Setiap kuesioner dirancang secara dinamis oleh admin, memastikan relevansi data yang
                                dikumpulkan
                                sesuai dengan kebutuhan evaluasi yang spesifik. Kami berkomitmen untuk menjaga
                                kerahasiaan data
                                dan menggunakan hasilnya semata-mata untuk kemajuan bersama.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>