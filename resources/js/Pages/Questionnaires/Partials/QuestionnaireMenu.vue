<script setup>
const props = defineProps({
    modelValue: String,
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div class="list-group list-group-transparent mb-3">
        <a class="list-group-item list-group-item-action d-flex align-items-center"
           :class="{ 'active': modelValue === 'basic' }"
           @click.prevent="emit('update:modelValue', 'basic')">
            <span class="icon me-2">
                <i class="fa-solid fa-file-alt"></i>
            </span>
            Informasi Dasar
        </a>
        <a class="list-group-item list-group-item-action d-flex align-items-center"
           :class="{ 'active': modelValue === 'questions' }"
           @click.prevent="emit('update:modelValue', 'questions')">
            <span class="icon me-2">
                <i class="fa-solid fa-question-circle"></i>
            </span>
            Manajemen Pertanyaan
        </a>
        <a class="list-group-item list-group-item-action d-flex align-items-center"
           :class="{ 'active': modelValue === 'results' }"
           @click.prevent="emit('update:modelValue', 'results')">
            <span class="icon me-2">
                <i class="fa-solid fa-chart-bar"></i>
            </span>
            Hasil Kuesioner
        </a>
    </div>
</template>
