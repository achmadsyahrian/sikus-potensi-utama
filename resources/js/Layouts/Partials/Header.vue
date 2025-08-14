<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import EmptyAvatar from '../../Components/EmptyAvatar.vue';
import { computed } from 'vue';

const page = usePage();

const activeRole = computed(() => {
    const activeRoleId = page.props.auth.activeRoleId;
    if (activeRoleId) {
        return page.props.auth.user.roles.find(role => role.id === activeRoleId);
    }
    return page.props.auth.user.roles[0];
});

const hasRole = (roleName) => {
    return activeRole.value?.slug === roleName;
};

const isSuperadmin = computed(() => hasRole('superadmin'));
const isAdmin = computed(() => hasRole('admin'));
const isUser = computed(() => ['dosen', 'pegawai', 'mahasiswa', 'mitra'].includes(activeRole.value?.slug));
</script>

<template>
    <header class="navbar navbar-expand-md d-print-none">
        <div class="container-xl">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu"
                aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                <Link :href="route('dashboard')">
                <ApplicationLogo width="110" height="32" class="navbar-brand-image" />
                </Link>
            </h1>

            <div class="navbar-nav flex-row order-md-last">
                <div class="d-none d-md-flex">
                    <!-- Notification Dropdown -->
                    <div class="nav-item dropdown d-none d-md-flex me-3">
                        <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1"
                            aria-label="Show notifications">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path
                                    d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" />
                                <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
                            </svg>
                            <span class="badge bg-red"></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Last updates</h3>
                                </div>
                                <div class="list-group list-group-flush list-group-hoverable">
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-auto"><span
                                                    class="status-dot status-dot-animated bg-red d-block"></span>
                                            </div>
                                            <div class="col text-truncate">
                                                <a href="#" class="text-body d-block">Example 1</a>
                                                <div class="d-block text-muted text-truncate mt-n1">
                                                    Change deprecated html tags to text decoration classes (#29604)
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <a href="#" class="list-group-item-actions">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon text-muted"
                                                        width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path
                                                            d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="nav-item dropdown">
                    <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown"
                        aria-label="Open user menu">
                        <EmptyAvatar />
                        <div class="d-none d-xl-block ps-2">
                            <div class="text-capitalize">{{ page.props.auth.user.name }}</div>
                            <div class="mt-1 small text-muted">
                                <template v-if="page.props.auth.user.roles && page.props.auth.user.roles.length > 0">
                                    <span v-if="activeRole">{{ activeRole.name }}</span>
                                    <span v-else>{{ page.props.auth.user.roles[0].name }}</span>
                                </template>
                            </div>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        <Link :href="route('profile.edit')" class="dropdown-item">Profil Saya</Link>
                        <Link :href="route('role-selection.index')" class="dropdown-item">Pilih Peran</Link>
                        <div class="dropdown-divider"></div>
                        <Link :href="route('logout')" method="post" class="dropdown-item" as="button">Logout</Link>
                    </div>
                </div>
            </div>

            <div class="collapse navbar-collapse" id="navbar-menu">
                <div class="d-flex flex-column flex-md-row flex-fill align-items-stretch align-items-md-center">
                    <ul class="navbar-nav">
                        <!-- Menu Home -->
                        <li class="nav-item">
                            <Link class="nav-link" :href="route('dashboard')">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M5 12l-2 0l9 -9l9 9l-2 0" />
                                    <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                                    <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
                                </svg>
                            </span>
                            <span class="nav-link-title">
                                Beranda
                            </span>
                            </Link>
                        </li>

                        <!-- Menu Manajemen Kuesioner (Untuk Admin & Superadmin) -->
                        <li class="nav-item" v-if="isAdmin || isSuperadmin">
                            <Link class="nav-link" :href="route('questionnaires.index')">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-file-text">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                    <path d="M9 11h6" />
                                    <path d="M9 15h6" />
                                </svg>
                            </span>
                            <span class="nav-link-title">
                                Manajemen Kuesioner
                            </span>
                            </Link>
                        </li>

                        <!-- Menu Daftar Kuesioner (Untuk Pengguna) -->
                        <li class="nav-item" v-if="isUser">
                            <Link class="nav-link" :href="route('answers.index')">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="fa-solid fa-list-check"></i>
                            </span>
                            <span class="nav-link-title">
                                Daftar Kuesioner
                            </span>
                            </Link>
                        </li>

                        <!-- Menu Data Master (Untuk Admin & Superadmin) -->
                        <li class="nav-item dropdown" v-if="isAdmin || isSuperadmin">
                            <a class="nav-link dropdown-toggle" href="#navbar-base" data-bs-toggle="dropdown"
                                data-bs-auto-close="outside" role="button" aria-expanded="false">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="icon icon-tabler icons-tabler-outline icon-tabler-database">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M12 6m-8 0a8 3 0 1 0 16 0a8 3 0 1 0 -16 0" />
                                        <path d="M4 6v6a8 3 0 0 0 16 0v-6" />
                                        <path d="M4 12v6a8 3 0 0 0 16 0v-6" />
                                    </svg>
                                </span>
                                <span class="nav-link-title">
                                    Data Master
                                </span>
                            </a>
                            <div class="dropdown-menu">
                                <div class="dropdown-menu-columns">
                                    <div class="dropdown-menu-column">
                                        <Link class="dropdown-item" :href="route('academic-periods.index')">
                                        Periode Akademik
                                        </Link>
                                        <Link class="dropdown-item" :href="route('faculties.index')">
                                        Fakultas
                                        </Link>
                                        <Link class="dropdown-item" :href="route('program-studies.index')">
                                        Program Studi
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </li>

                        <li class="nav-item" v-if="isSuperadmin">
                            <Link class="nav-link" :href="route('users.index')">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-users">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                                    <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                    <path d="M21 21v-2a4 4 0 0 0 -3 -3.85" />
                                </svg>
                            </span>
                            <span class="nav-link-title">
                                Pengguna
                            </span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>
</template>
