<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BaseInput from '@/Components/BaseInput.vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseAlert from '@/Components/BaseAlert.vue';

const page = usePage();
const appName = page.props.app_name;
const user = page.props.auth.user;

const isSevimaUser = computed(() => user.auth_provider === 'sevima');
const showPasswordFields = ref(false);

const form = useForm({
    name: user.name,
    email: user.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('profile.update'), {
        onSuccess: () => {
            form.reset('password', 'password_confirmation');
            showPasswordFields.value = false;
        },
    });
};

const cancelPasswordChange = () => {
    showPasswordFields.value = false;
    form.reset('password', 'password_confirmation');
};
</script>

<template>
    <Head :title="`Profil Saya - ${appName}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Profil Saya
                    </h2>
                </div>
            </div>
        </template>

        <div class="row row-cards">
            <div class="col-lg-12">
                
                <BaseAlert
                    v-if="isSevimaUser"
                    type="warning"
                    title="Informasi!"
                    message="Data Anda disinkronisasi dari SIAKAD. Anda tidak dapat mengubahnya di sini."
                />
                
                <div class="card">
                    <div class="row g-0">
                        <div class="col d-flex flex-column">
                            <form @submit.prevent="submit">
                                <div class="card-body">
                                    <h3 class="card-title">Informasi Dasar</h3>
                                    <p class="card-subtitle">Informasi profil dasar Anda.</p>
                                    <div class="row g-3">
                                        <div class="col-md">
                                            <BaseInput
                                                label="Nama"
                                                type="text"
                                                v-model="form.name"
                                                :error="form.errors.name"
                                                :disabled="isSevimaUser"
                                            />
                                        </div>
                                        <div class="col-md">
                                            <BaseInput
                                                label="Email"
                                                type="email"
                                                v-model="form.email"
                                                :error="form.errors.email"
                                                :disabled="isSevimaUser"
                                            />
                                        </div>
                                    </div>

                                    <h3 class="card-title mt-4">Password</h3>
                                    <p class="card-subtitle">Anda bisa mengubah password Anda di sini.</p>
                                    
                                    <div v-if="!showPasswordFields">
                                        <BaseButton
                                            type="button"
                                            label="Atur password baru"
                                            variant="secondary" outline
                                            @click="showPasswordFields = true"
                                            :disabled="isSevimaUser"
                                        />
                                    </div>
                                    
                                    <div v-if="showPasswordFields">
                                        <div class="row g-3">
                                            <div class="col-md">
                                                <BaseInput
                                                    label="Password"
                                                    type="password"
                                                    placeholder="Password baru"
                                                    v-model="form.password"
                                                    :error="form.errors.password"
                                                />
                                            </div>
                                            <div class="col-md">
                                                <BaseInput
                                                    label="Konfirmasi Password"
                                                    type="password"
                                                    placeholder="Konfirmasi password baru"
                                                    v-model="form.password_confirmation"
                                                    :error="form.errors.password_confirmation"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer bg-transparent mt-auto">
                                    <div class="btn-list justify-content-end">
                                        <BaseButton
                                            v-if="showPasswordFields"
                                            type="button"
                                            label="Batal"
                                            variant="secondary"
                                            @click="cancelPasswordChange"
                                        />
                                        <BaseButton
                                            type="submit"
                                            label="Simpan Perubahan"
                                            variant="primary"
                                            :disabled="isSevimaUser || form.processing"
                                        />
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>