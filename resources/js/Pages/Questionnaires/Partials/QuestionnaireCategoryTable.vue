<script setup>
import { defineProps, ref, reactive, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import BaseButton from '@/Components/BaseButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import DataTable from '@/Components/DataTable.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import BaseAlert from '@/Components/BaseAlert.vue';

const props = defineProps({
    questionnaire: Object,
    questionCategories: {
        type: Array,
        default: () => [],
    },
});

const isAdding = ref(false);
const isEditing = ref(null);
const categoryToDelete = ref(null);
const confirmDeleteModal = ref(null);

const sortedCategories = computed(() => {
    return [...props.questionCategories].sort((a, b) => a.order - b.order);
});

// Computed property untuk mengecek apakah kuesioner sudah ada jawabannya
const hasAnswers = computed(() => {
    return props.questionnaire.total_answers > 0;
});

const addForm = useForm({
    name: '',
    order: 1,
    questionnaire_id: props.questionnaire.id,
});

const editForm = useForm({
    name: '',
    order: 1,
});

const columns = ref([
    { label: 'Urutan', key: 'order', class: 'w-10' },
    { label: 'Nama Kategori', key: 'name', class: '' },
]);

const addingOrderError = computed(() => {
    if (sortedCategories.value.some(cat => cat.order === addForm.order)) {
        return 'Urutan sudah ada.';
    }
    return addForm.errors.order;
});

const editingOrderWarning = computed(() => {
    const otherCategories = sortedCategories.value.filter(cat => cat.id !== isEditing.value);
    if (otherCategories.some(cat => cat.order === editForm.order)) {
        return `Urutan ini sudah digunakan. Jika disimpan, urutan kategori '${otherCategories.find(c => c.order === editForm.order)?.name}' akan ditukar.`;
    }
    return null;
});

const createCategory = () => {
    if (addingOrderError.value) {
        return;
    }
    addForm.post(route('question-categories.store'), {
        onSuccess: () => {
            isAdding.value = false;
            addForm.reset('name');
        },
    });
};

const cancelAdd = () => {
    isAdding.value = false;
    addForm.reset();
};

const startAdd = () => {
    isAdding.value = true;
    addForm.order = sortedCategories.value.length > 0 ? Math.max(...sortedCategories.value.map(c => c.order)) + 1 : 1;
};

const startEdit = (category) => {
    isAdding.value = false;
    isEditing.value = category.id;
    editForm.name = category.name;
    editForm.order = category.order;
};

const cancelEdit = () => {
    isEditing.value = null;
    editForm.reset();
};

const updateCategory = (categoryId) => {
    if (editForm.errors.order) {
        return;
    }
    editForm.put(route('question-categories.update', categoryId), {
        onSuccess: () => {
            isEditing.value = null;
            editForm.reset();
        },
    });
};

const confirmDelete = (category) => {
    categoryToDelete.value = category;
};

const deleteCategory = () => {
    if (categoryToDelete.value) {
        router.delete(route('question-categories.destroy', categoryToDelete.value.id), {
            onSuccess: () => {
                categoryToDelete.value = null;
            }
        });
    }
};

const isAddFormInvalid = computed(() => {
    return addForm.processing || !addForm.name || !!addingOrderError.value;
});

const isEditFormInvalid = computed(() => {
    return editForm.processing || !editForm.name || editForm.errors.order;
});
</script>

<template>
    <BaseAlert v-if="hasAnswers" type="warning" title="Kuesioner Telah Dijawab"
        message="Kuesioner ini sudah memiliki jawaban. Kategori pertanyaan tidak dapat diubah, ditambah, atau dihapus untuk menjaga konsistensi data."
        class="mb-4" />

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Daftar Kategori</h3>
            <BaseButton type="button" variant="primary" @click="startAdd" v-if="!isAdding && !hasAnswers">
                <i class="fa-solid fa-plus me-2"></i> Tambah Kategori
            </BaseButton>
        </div>
        <DataTable :data="{ data: sortedCategories }" :columns="columns">
            <template #before-tbody>
                <tr v-if="isAdding && !hasAnswers">
                    <td class="w-10">
                        <BaseInput type="number" v-model="addForm.order"
                            :error="addForm.errors.order || addingOrderError" :disabled="addForm.processing" />
                    </td>
                    <td>
                        <BaseInput type="text" v-model="addForm.name" :error="addForm.errors.name"
                            :disabled="addForm.processing" placeholder="Masukkan nama kategori baru" />
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            <BaseButton type="button" @click="createCategory"
                                :disabled="isAddFormInvalid" label="Simpan"
                                variant="primary" />
                            <BaseButton type="button" @click="cancelAdd" label="Batal" variant="secondary" outline />
                        </div>
                    </td>
                </tr>
            </template>
            <template #cell(order)="{ item }">
                <template v-if="isEditing === item.id">
                    <div class="w-20">
                        <BaseInput type="number" v-model="editForm.order" :error="editForm.errors.order"
                            :disabled="editForm.processing" :class="{ 'border border-warning': editingOrderWarning }" />
                        <span v-if="editingOrderWarning" class="fs-5 text-warning p-0">
                            {{ editingOrderWarning }}
                        </span>
                    </div>
                </template>
                <template v-else>
                    <div class="ms-3">
                        {{ item.order }}
                    </div>
                </template>
            </template>
            <template #cell(name)="{ item }">
                <template v-if="isEditing === item.id">
                    <BaseInput type="text" v-model="editForm.name" :error="editForm.errors.name"
                        :disabled="editForm.processing" />
                </template>
                <template v-else>
                    {{ item.name }}
                </template>
            </template>
            <template #cell(actions)="{ item }">
                <div v-if="isEditing !== item.id" class="d-flex gap-2">
                    <BaseButton variant="info" class="btn-icon" outline @click.prevent="startEdit(item)" :disabled="hasAnswers">
                        <i class="fa-solid fa-pencil-alt"></i>
                    </BaseButton>
                    <BaseButton variant="danger" class="btn-icon" outline data-bs-toggle="modal"
                        data-bs-target="#confirmDeleteModal" @click.prevent="confirmDelete(item)" :disabled="hasAnswers">
                        <i class="fa-solid fa-trash"></i>
                    </BaseButton>
                </div>
                <div v-else class="d-flex gap-2">
                    <BaseButton type="button" @click="updateCategory(item.id)"
                        :disabled="isEditFormInvalid || hasAnswers" label="Simpan" variant="primary" />
                    <BaseButton type="button" @click="cancelEdit" label="Batal" variant="secondary" outline />
                </div>
            </template>
        </DataTable>
    </div>
    <ConfirmModal id="confirmDeleteModal" title="Hapus Kategori"
        :message="`Apakah Anda yakin ingin menghapus kategori '${categoryToDelete?.name}'? Aksi ini tidak dapat dibatalkan.`"
        confirm-text="Ya, Hapus" @confirm="deleteCategory" />
</template>