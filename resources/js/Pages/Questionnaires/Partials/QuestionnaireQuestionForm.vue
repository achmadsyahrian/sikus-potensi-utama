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
    <div class="row">
        <div v-if="editingQuestion" class="col-md-6 mb-3">
            <label for="order" class="form-label required">Urutan</label>
            <BaseInput type="number" v-model="form.order" :error="form.errors.order" required
                :class="{ 'border border-warning': editingOrderWarning }" />
            <div v-if="editingOrderWarning" class="fs-5 text-warning p-0">{{ editingOrderWarning }}</div>
        </div>
        <div class="col-md-6 mb-3">
            <BaseInput label="Teks Pertanyaan" type="text" v-model="form.question_text"
                :error="form.errors.question_text" required />
        </div>
        <div class="col-md-6 mb-3">
            <label for="question_type" class="form-label required">Tipe Jawaban</label>
            <select id="question_type" v-model="form.question_type" class="form-select"
                :class="{ 'is-invalid': form.errors.question_type }" required>
                <option value="multiple_choice">Pilihan Ganda</option>
                <option value="text">Teks Bebas</option>
            </select>
            <div v-if="form.errors.question_type" class="invalid-feedback">{{ form.errors.question_type }}</div>
        </div>
        <div class="col-md-6 mb-3">
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
        <div class="col-md-6 mb-3 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_required" v-model="form.is_required">
                <label class="form-check-label" for="is_required">
                    Pertanyaan Wajib Diisi
                </label>
            </div>
        </div>
    </div>
</template>
