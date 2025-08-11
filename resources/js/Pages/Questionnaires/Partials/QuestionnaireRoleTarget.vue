<script setup>
import { defineProps, computed } from 'vue';

const props = defineProps({
    form: Object,
    roles: Array,
    isDisabled: {
        type: Boolean,
        default: false,
    },
    isEditing: {
        type: Boolean,
        default: false,
    },
    isCreate: {
        type: Boolean,
        default: false,
    },
});

const ensureNoDuplicatePush = (item) => {
    const exists = props.form.targets.some(t => t.target_type === item.target_type && t.target_value == item.target_value);
    if (!exists) props.form.targets.push(item);
};

const removeTarget = (type, value) => {
    props.form.targets = props.form.targets.filter(target => !(target.target_type === type && target.target_value == value));
};

const handleRoleTargetChange = (roleName, isChecked) => {
    if (isChecked) {
        ensureNoDuplicatePush({ target_type: 'role', target_value: roleName });
    } else {
        removeTarget('role', roleName);
    }
};

const hasRoleTarget = (roleName) => {
    return props.form.targets.some(target => target.target_type === 'role' && String(target.target_value).toLowerCase() === String(roleName).toLowerCase());
};

const targetedRoles = computed(() => {
    return props.form.targets
        .filter(t => t.target_type === 'role')
        .map(t => t.target_value);
});
</script>

<template>
    <div class="col-12">
        <template v-if="isEditing || isCreate">
            <label class="form-label">Pilih Target Berdasarkan Role</label>
            <div class="d-flex flex-wrap gap-2">
                <div v-for="role in roles" :key="role.id" class="form-check form-check-inline">
                    <input type="checkbox" :value="role.name" :checked="hasRoleTarget(role.name)"
                        @change="handleRoleTargetChange(role.name, $event.target.checked)" class="form-check-input"
                        :id="`role-check-${role.id}`" :disabled="isDisabled">
                    <label class="form-check-label text-capitalize" :for="`role-check-${role.id}`">
                        {{ role.name }}
                    </label>
                </div>
            </div>
        </template>
        <template v-else>
            <label class="form-label">Target Role</label>
            <div class="d-flex flex-wrap gap-2">
                <template v-if="targetedRoles.length > 0">
                    <span v-for="role in targetedRoles" :key="role" class="badge bg-primary-lt text-capitalize">
                        {{ role }}
                    </span>
                </template>
                <template v-else>
                    <p class="form-control-plaintext">Tidak Ada</p>
                </template>
            </div>
        </template>
    </div>
</template>
