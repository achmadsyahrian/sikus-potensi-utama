<script setup>
import BaseButton from '@/Components/BaseButton.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    search: String,
    handleSearch: Function,
    handleRefresh: Function,
});

const emit = defineEmits(['update:search']);
</script>

<template>
    <div class="card mb-3">
        <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between">
            <div class="d-flex w-100 w-md-auto mb-2 mb-md-0">
                <div class="input-icon me-2 w-100">
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Cari label..."
                        :value="search"
                        @keyup="handleSearch"
                        @input="emit('update:search', $event.target.value)"
                    >
                    <span class="input-icon-addon">
                        <i class="fa-solid fa-search"></i>
                    </span>
                </div>
                <BaseButton type="button" variant="secondary" outline class="me-md-2 mb-2 mb-md-0" @click="handleRefresh">
                    <i class="fa-solid fa-arrow-rotate-right me-2"></i>
                    Refresh
                </BaseButton>
            </div>

            <Link :href="route('satisfaction-criteria.create')">
                <BaseButton type="button" variant="primary">
                    <i class="fa-solid fa-plus me-2"></i>
                    Tambah Kriteria
                </BaseButton>
            </Link>
        </div>
    </div>
</template>
