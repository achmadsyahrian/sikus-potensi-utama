<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';


</script>

<template>
    <div class="sidebar" data-background-color="dark">
        <div class="sidebar-logo">
            <div class="logo-header" data-background-color="dark">
                <a href="index.html" class="logo">
                    <ApplicationLogo />
                </a>
                <div class="nav-toggle">
                    <button class="btn btn-toggle toggle-sidebar">
                        <i class="gg-menu-right"></i>
                    </button>
                    <button class="btn btn-toggle sidenav-toggler">
                        <i class="gg-menu-left"></i>
                    </button>
                </div>
                <button class="topbar-toggler more">
                    <i class="gg-more-vertical-alt"></i>
                </button>
            </div>
            <!-- End Logo Header -->
        </div>

        <div class="sidebar-wrapper scrollbar scrollbar-inner">
            <div class="sidebar-content">
                <ul class="nav nav-secondary">
                    <li class="nav-item">
                        <Link :href="route('dashboard')">
                        <a>
                            <i class="fas fa-home"></i>
                            <p>Dashboard</p>
                        </a>
                        </Link>
                    </li>
                    <!-- <li class="nav-section">
                        <span class="sidebar-mini-icon">
                            <i class="fa fa-ellipsis-h"></i>
                        </span>
                        <h4 class="text-section">Components</h4>
                    </li> -->
                </ul>
            </div>
        </div>
    </div>
</template>