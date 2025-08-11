<script setup>
import { defineProps, ref, reactive, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import BaseButton from '@/Components/BaseButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import DataTable from '@/Components/DataTable.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import FlashAlert from '@/Components/FlashAlert.vue';

const props = defineProps({
    questionnaire: Object,
    questionOptions: {
        type: Array,
        default: () => [],
    },
});

const isAdding = ref(false);
const isEditing = ref(null);
const optionToDelete = ref(null);
const confirmDeleteModal = ref(null);

const sortedOptions = computed(() => {
    return [...props.questionOptions].sort((a, b) => a.order - b.order);
});

const addForm = useForm({
    option_text: '',
    option_value: null,
    order: 1,
    questionnaire_id: props.questionnaire.id,
});

const editForm = useForm({
    option_text: '',
    option_value: null,
    order: 1,
});

const columns = ref([
    { label: 'Urutan', key: 'order', class: 'w-10' },
    { label: 'Opsi Jawaban', key: 'option_text', class: '' },
    { label: 'Nilai', key: 'option_value', class: 'w-10' },
]);

const addingOrderError = computed(() => {
    if (sortedOptions.value.some(opt => opt.order === addForm.order)) {
        return 'Urutan sudah ada.';
    }
    return addForm.errors.order;
});

const editingOrderWarning = computed(() => {
    const otherOptions = sortedOptions.value.filter(opt => opt.id !== isEditing.value);
    if (otherOptions.some(opt => opt.order === editForm.order)) {
        return `Urutan ini sudah digunakan. Jika disimpan, urutan opsi '${otherOptions.find(o => o.order === editForm.order)?.option_text}' akan ditukar.`;
    }
    return null;
});

const createOption = () => {
    if (addingOrderError.value) {
        return;
    }
    addForm.post(route('question-options.store'), {
        onSuccess: () => {
            isAdding.value = false;
            addForm.reset('option_text', 'option_value');
        },
    });
};

const cancelAdd = () => {
    isAdding.value = false;
    addForm.reset();
};

const startAdd = () => {
    isAdding.value = true;
    addForm.order = sortedOptions.value.length > 0 ? Math.max(...sortedOptions.value.map(o => o.order)) + 1 : 1;
};

const startEdit = (option) => {
    isAdding.value = false;
    isEditing.value = option.id;
    editForm.option_text = option.option_text;
    editForm.option_value = option.option_value;
    editForm.order = option.order;
};

const cancelEdit = () => {
    isEditing.value = null;
    editForm.reset();
};

const updateOption = (optionId) => {
    editForm.put(route('question-options.update', optionId), {
        onSuccess: () => {
            isEditing.value = null;
            editForm.reset();
        },
    });
};

const confirmDelete = (option) => {
    optionToDelete.value = option;
};

const deleteOption = () => {
    if (optionToDelete.value) {
        router.delete(route('question-options.destroy', optionToDelete.value.id), {
            onSuccess: () => {
                optionToDelete.value = null;
            }
        });
    }
};

// Computed property untuk cek apakah form tambah tidak valid
const isAddFormInvalid = computed(() => {
  return addForm.processing || !addForm.option_text || !addForm.option_value || !addForm.order || !!addingOrderError.value;
});

// Computed property untuk cek apakah form edit tidak valid
const isEditFormInvalid = computed(() => {
  return editForm.processing || !editForm.option_text || !editForm.option_value || !editForm.order;
});

</script>

<template>
    <div class="card-body">
        <FlashAlert />
        <QuestionnaireInfoCard :questionnaire="questionnaire" />
        <div class="d-flex align-items-center justify-content-between pt-2 pb-4">
            <div>
                <h3 class="fw-bold mb-1">Manajemen Opsi</h3>
                <h5 class="op-7 mb-2 text-muted">Kelola opsi jawaban yang dapat digunakan kembali untuk pertanyaan kuesioner.</h5>
            </div>
            <div class="ms-md-auto py-2 py-md-0">
                <BaseButton type="button" label="Tambah Opsi" variant="primary" @click="startAdd"
                    v-if="!isAdding">
                    <i class="fa-solid fa-plus me-2"></i> Tambah Opsi
                </BaseButton>
            </div>
        </div>
        <div class="card">
            <DataTable :data="{ data: sortedOptions }" :columns="columns">
                <template #before-tbody>
                    <tr v-if="isAdding">
                        <td class="w-10">
                            <BaseInput type="number" v-model="addForm.order" :error="addForm.errors.order || addingOrderError"
                                :disabled="addForm.processing" />
                        </td>
                        <td>
                            <BaseInput type="text" v-model="addForm.option_text" :error="addForm.errors.option_text"
                                :disabled="addForm.processing" placeholder="Masukkan teks opsi jawaban" />
                        </td>
                        <td class="w-10">
                            <BaseInput type="number" v-model="addForm.option_value" :error="addForm.errors.option_value"
                                :disabled="addForm.processing" />
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <BaseButton type="button" @click="createOption"
                                    :disabled="isAddFormInvalid"
                                    label="Simpan" variant="primary" />
                                <BaseButton type="button" @click="cancelAdd" label="Batal" variant="secondary" outline />
                            </div>
                        </td>
                    </tr>
                </template>
                <template #cell(order)="{ item }">
                    <template v-if="isEditing === item.id">
                        <div class="w-20">
                            <BaseInput type="number" v-model="editForm.order" :error="editForm.errors.order"
                                :disabled="editForm.processing" :class="{'border border-warning': editingOrderWarning}" />
                            <span v-if="editingOrderWarning" class="fs-5 text-warning p-0">
                                {{ editingOrderWarning }}
                            </span>
                        </div>
                    </template>
                    <template v-else>
                        <div class="ms-3">{{ item.order }}</div>
                    </template>
                </template>
                <template #cell(option_text)="{ item }">
                    <template v-if="isEditing === item.id">
                        <BaseInput type="text" v-model="editForm.option_text" :error="editForm.errors.option_text"
                            :disabled="editForm.processing" />
                    </template>
                    <template v-else>
                        {{ item.option_text }}
                    </template>
                </template>
                <template #cell(option_value)="{ item }">
                    <template v-if="isEditing === item.id">
                        <BaseInput type="number" v-model="editForm.option_value" :error="editForm.errors.option_value"
                            :disabled="editForm.processing" />
                    </template>
                    <template v-else>
                        {{ item.option_value }}
                    </template>
                </template>
                <template #cell(actions)="{ item }">
                    <div v-if="isEditing !== item.id" class="d-flex gap-2">
                        <BaseButton variant="info" class="btn-icon" outline @click.prevent="startEdit(item)">
                            <i class="fa-solid fa-pencil-alt"></i>
                        </BaseButton>
                        <BaseButton variant="danger" class="btn-icon" outline data-bs-toggle="modal"
                            data-bs-target="#confirmDeleteModal" @click.prevent="confirmDelete(item)">
                            <i class="fa-solid fa-trash"></i>
                        </BaseButton>
                    </div>
                    <div v-else class="d-flex gap-2">
                        <BaseButton type="button" @click="updateOption(item.id)"
                            :disabled="isEditFormInvalid" 
                            label="Simpan" variant="primary" />
                        <BaseButton type="button" @click="cancelEdit" label="Batal" variant="secondary" outline />
                    </div>
                </template>
            </DataTable>
        </div>
    </div>
    <ConfirmModal id="confirmDeleteModal" title="Hapus Opsi"
        :message="`Apakah Anda yakin ingin menghapus opsi '${optionToDelete?.option_text}'? Aksi ini tidak dapat dibatalkan.`"
        confirm-text="Ya, Hapus" @confirm="deleteOption" />
</template>
