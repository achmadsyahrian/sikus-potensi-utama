<script setup>
import { Link } from '@inertiajs/vue3';
import BaseButton from '@/Components/BaseButton.vue';

const props = defineProps({
    modelValue: String,
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div>
        <div class="list-group list-group-transparent mb-3">
            <a class="list-group-item list-group-item-action d-flex align-items-center"
                :class="{ 'active': modelValue === 'basic' }" @click.prevent="emit('update:modelValue', 'basic')">
                <span class="icon me-2 mb-2">
                    <i class="fa-solid fa-file-alt"></i>
                </span>
                Informasi Dasar
            </a>
            <a class="list-group-item list-group-item-action d-flex align-items-center"
                :class="{ 'active': modelValue === 'options' }" @click.prevent="emit('update:modelValue', 'options')">
                <span class="icon me-2 mb-2">
                    <i class="fa-solid fa-list-check"></i>
                </span>
                Manajemen Opsi
            </a>
            <a class="list-group-item list-group-item-action d-flex align-items-center"
                :class="{ 'active': modelValue === 'categories' }"
                @click.prevent="emit('update:modelValue', 'categories')">
                <span class="icon me-2 mb-2">
                    <i class="fa-solid fa-list-alt"></i>
                </span>
                Manajemen Kategori
            </a>
            <a class="list-group-item list-group-item-action d-flex align-items-center"
                :class="{ 'active': modelValue === 'questions' }"
                @click.prevent="emit('update:modelValue', 'questions')">
                <span class="icon me-2 mb-2">
                    <i class="fa-solid fa-question-circle"></i>
                </span>
                Manajemen Pertanyaan
            </a>
            <a class="list-group-item list-group-item-action d-flex align-items-center"
                :class="{ 'active': modelValue === 'results' }" @click.prevent="emit('update:modelValue', 'results')">
                <span class="icon me-2 mb-2">
                    <i class="fa-solid fa-chart-bar"></i>
                </span>
                Ringkasan Analisis
            </a>
            <a class="list-group-item list-group-item-action d-flex align-items-center"
                :class="{ 'active': modelValue === 'respondents' }"
                @click.prevent="emit('update:modelValue', 'respondents')">
                <span class="icon me-2 mb-2">
                    <i class="fa-solid fa-users"></i>
                </span>
                Responden
            </a>
        </div>
        <div class="d-grid">
            <Link :href="route('questionnaires.index')">
            <BaseButton type="button" variant="secondary" outline class="w-100">
                <i class="fa-solid fa-arrow-left me-2"></i> Kembali
            </BaseButton>
            </Link>
        </div>
    </div>
</template>
