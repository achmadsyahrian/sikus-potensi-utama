<script setup>
import { defineProps } from 'vue';
import BaseInput from '@/Components/BaseInput.vue';

const props = defineProps({
    form: Object,
    editingQuestion: Object,
    questionCategories: {
        type: Array,
        default: () => [],
    },
    editingOrderWarning: String,
});
</script>

<template>
    <div class="row g-3">
        <div v-if="editingQuestion" class="col-md-4">
            <BaseInput label="Urutan" type="number" v-model="form.order" :error="form.errors.order" required
                :class="{ 'border border-warning': editingOrderWarning }" />
            <div v-if="editingOrderWarning" class="form-text text-warning mt-1">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ editingOrderWarning }}
            </div>
        </div>

        <div :class="editingQuestion ? 'col-md-8' : 'col-12'">
            <BaseInput label="Teks Pertanyaan" type="textarea" v-model="form.question_text"
                :error="form.errors.question_text" required rows="3" />
        </div>

        <div class="col-md-6">
            <label for="question_type" class="form-label required">Tipe Jawaban</label>
            <select id="question_type" v-model="form.question_type" class="form-select"
                :class="{ 'is-invalid': form.errors.question_type }" required>
                <option value="multiple_choice">Pilihan Ganda</option>
            </select>
            <div v-if="form.errors.question_type" class="invalid-feedback">{{ form.errors.question_type }}</div>
        </div>

        <div v-if="!editingQuestion" class="col-md-6">
            <label for="category_id" class="form-label">Kategori (Opsional)</label>
            <select id="category_id" v-model="form.category_id" class="form-select"
                :class="{ 'is-invalid': form.errors.category_id }">
                <option :value="null">-- Tanpa Kategori --</option>
                <option v-for="category in questionCategories" :key="category.id" :value="category.id">
                    {{ category.name }}
                </option>
            </select>
            <div v-if="form.errors.category_id" class="invalid-feedback">{{ form.errors.category_id }}</div>
        </div>

        <div class="col-12 mt-4">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="is_required" v-model="form.is_required">
                <label class="form-check-label user-select-none" for="is_required">
                    Jadikan pertanyaan ini wajib diisi oleh responden
                </label>
            </div>
        </div>
    </div>
</template>
