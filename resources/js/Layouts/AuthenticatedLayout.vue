<script setup>
import Sidebar from '@/Layouts/Partials/Sidebar.vue';
import Header from '@/Layouts/Partials/Header.vue';
import Footer from '@/Layouts/Partials/Footer.vue';
import FlashAlert from '@/Components/FlashAlert.vue';

</script>

<template>
    
    <div class="page">
        <FlashAlert />
        <Header />
        
        <div class="page-wrapper">
            <div class="page-header">
                <div class="container-xl">
                    <slot name="header" />
                </div>
            </div>
            
            <div class="page-body">
                <div class="container-xl">
                    <slot />
                </div>
            </div>
            <Footer />
        </div>
    </div>
</template>