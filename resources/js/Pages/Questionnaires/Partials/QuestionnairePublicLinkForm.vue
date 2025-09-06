<script setup>
import { computed, ref } from 'vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseAlert from '@/Components/BaseAlert.vue';

const props = defineProps({
    questionnaire: {
        type: Object,
        required: true,
    },
    isEditing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['generatePublicLink']);

const publicLink = computed(() => {
    if (props.questionnaire.public_link_token) {
        return `${window.location.origin}/questionnaires/public/${props.questionnaire.public_link_token}`;
    }
    return null;
});

const canGenerate = computed(() => {
    return props.questionnaire.is_active && !props.questionnaire.public_link_token;
});

// State untuk notifikasi
const notification = ref({
    show: false,
    message: '',
    type: 'success',
});

const copyLink = async () => {
    const linkInput = document.createElement('textarea');
    linkInput.value = publicLink.value;
    document.body.appendChild(linkInput);
    linkInput.select();
    
    try {
        document.execCommand('copy');
        
        notification.value = {
            show: true,
            message: 'Tautan kuesioner berhasil disalin!',
            type: 'success',
        };
    } catch (err) {
        console.error('Gagal menyalin tautan: ', err);
        notification.value = {
            show: true,
            message: 'Gagal menyalin tautan. Silakan salin secara manual.',
            type: 'danger',
        };
    } finally {
        document.body.removeChild(linkInput);
        // Sembunyikan notifikasi setelah 3 detik
        setTimeout(() => {
            notification.value.show = false;
        }, 3000);
    }
};
</script>

<template>
    <div class="card-body" v-if="!isEditing">
        <h5 class="card-title text-primary">Tautan Publik</h5>
        <p class="card-subtitle mb-4">Bagikan tautan ini kepada responden eksternal (contoh: mitra) yang tidak memiliki akun di sistem.</p>

        <BaseAlert
            v-if="notification.show"
            :title="notification.message"
            :type="notification.type"
            class="mb-3"
        />

        <div v-if="canGenerate">
            <p>Tautan publik belum dibuat.</p>
            <BaseButton
                label="Generate Tautan Publik"
                variant="primary"
                @click="emit('generatePublicLink')"
            />
        </div>

        <div v-else-if="publicLink">
            <div class="input-group">
                <input
                    type="text"
                    :value="publicLink"
                    class="form-control"
                    readonly
                >
                <BaseButton
                    type="button"
                    label="Salin"
                    variant="secondary"
                    @click="copyLink"
                />
            </div>
            <div class="form-text">
              <span class="text-success">Tautan berhasil dibuat dan siap dibagikan.</span>
            </div>
        </div>
        
        <div v-else>
            <p class="text-muted">Kuesioner harus aktif terlebih dahulu untuk dapat membuat tautan publik.</p>
        </div>
    </div>
</template>
