<script setup>
import { Link } from '@inertiajs/vue3';
import { useSlots } from 'vue';

const props = defineProps({
    data: {
        type: Object,
        required: true,
    },
    columns: {
        type: Array,
        required: true,
    },
    actions: {
        type: Array,
        default: () => [],
    }
});

const hasSlot = (name) => {
    const slots = useSlots();
    return !!slots[name];
};
</script>

<template>
    <div class="card-table table-responsive">
        <table class="card-table table table-vcenter">
            <thead>
                <tr>
                    <th v-for="column in columns" :key="column.key" :class="column.class">{{ column.label }}</th>
                    <th class="w-1" v-if="actions.length > 0 || hasSlot('cell(actions)')">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in data.data" :key="item.id">
                    <td v-for="column in columns" :key="column.key" :class="column.dataClass">

                        <template v-if="hasSlot(`cell(${column.key})`)">
                            <slot :name="`cell(${column.key})`" :item="item"></slot>
                        </template>

                        <span v-else>{{ item[column.key] }}</span>
                    </td>
                    <td v-if="actions.length > 0 || hasSlot('cell(actions)')">
                        <slot name="cell(actions)" :item="item"></slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex align-items-center">
        <p class="m-0 text-muted">Menampilkan {{ data.from }} sampai {{ data.to }} dari {{ data.total }} entri</p>
        <ul class="pagination m-0 ms-auto">
            <li
                v-for="(link, index) in data.links"
                :key="index"
                class="page-item"
                :class="{ 'active': link.active, 'disabled': !link.url }"
            >
                <Link
                    :href="link.url"
                    class="page-link"
                >
                    <span v-if="link.label.includes('Previous')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M15 6l-6 6l6 6"></path>
                        </svg>
                    </span>
                    <span v-else-if="link.label.includes('Next')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M9 6l6 6l-6 6"></path>
                        </svg>
                    </span>
                    <span v-else>{{ link.label }}</span>
                </Link>
            </li>
        </ul>
    </div>
</template>
