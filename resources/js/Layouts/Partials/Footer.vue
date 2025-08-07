<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <footer class="footer footer-transparent d-print-none">
        <div class="container-xl">
            <div class="row text-center align-items-center flex-row-reverse">
                <div class="col-lg-auto ms-lg-auto">
                    <ul class="list-inline list-inline-dots mb-0">
                        <li class="list-inline-item">
                            <Link :href="route('about-us')" class="link-secondary">
                            Tentang Kami
                            </Link>
                        </li>
                    </ul>
                </div>
                <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                    <ul class="list-inline list-inline-dots mb-0">
                        <li class="list-inline-item">
                            Hak Cipta &copy; {{ new Date().getFullYear() }}
                            <a href="http://potensi-utama.ac.id" target="_blank">Universitas Potensi Utama</a>.
                            Dibuat untuk layanan terbaik.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
</template>