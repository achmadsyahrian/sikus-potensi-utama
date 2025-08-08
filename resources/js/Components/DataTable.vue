<script setup>
import { Link } from '@inertiajs/vue3';
import { useSlots, computed } from 'vue';

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

const visibleLinks = computed(() => {
    if (!props.data.links) {
        return [];
    }

    const links = props.data.links.slice();
    const current = props.data.current_page;
    const last = props.data.last_page;

    // Ambil tombol Previous dan Next
    const prevLink = links.shift();
    const nextLink = links.pop();

    // Ambil halaman pertama dan terakhir
    const first = { label: "1", url: links.find(l => l.label === "1")?.url, active: current === 1 };
    const lastLink = { label: String(last), url: links.find(l => l.label === String(last))?.url, active: current === last };

    // Kalau cuma 1 halaman → langsung return satu halaman aja
    if (last === 1) {
        return [first];
    }

    // Tentukan range sekitar halaman aktif
    let start = Math.max(2, current - 2);
    let end = Math.min(last - 1, current + 2);

    // Buat array halaman tengah
    const middle = [];
    for (let i = start; i <= end; i++) {
        middle.push({
            label: String(i),
            url: links.find(l => l.label === String(i))?.url,
            active: current === i
        });
    }

    // Sisipkan "..." bila ada gap dari halaman pertama ke tengah
    if (start > 2) {
        middle.unshift({ label: "...", url: null });
    }

    // Sisipkan "..." bila ada gap dari tengah ke halaman terakhir
    if (end < last - 1) {
        middle.push({ label: "...", url: null });
    }

    // Gabungkan semua link
    return [
        prevLink,
        first,
        ...middle,
        lastLink,
        nextLink
    ];
});


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
                <tr v-if="!data.data.length">
                    <td :colspan="columns.length + (actions.length > 0 || hasSlot('cell(actions)') ? 1 : 0)"
                        class="text-center text-muted">
                        Tidak ada data yang tersedia.
                    </td>
                </tr>
                <tr v-for="(item, index) in data.data" :key="item.id">
                    <td v-for="column in columns" :key="column.key" :class="column.dataClass">
                        <template v-if="hasSlot(`cell(${column.key})`)">
                            <slot :name="`cell(${column.key})`" :item="item" :index="index"></slot>
                        </template>
                        <span v-else>{{ item[column.key] || '-' }}</span>
                    </td>
                    <td v-if="actions.length > 0 || hasSlot('cell(actions)')">
                        <slot name="cell(actions)" :item="item"></slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex align-items-center">
        <p class="m-0 text-muted">Menampilkan {{ data.from || 0 }} sampai {{ data.to || 0 }} dari {{ data.total || 0 }}
            entri</p>
        <ul class="pagination m-0 ms-auto">
            <li v-for="(link, index) in visibleLinks" :key="index" class="page-item"
                :class="{ 'active': link.active, 'disabled': !link.url }">
                <Link v-if="link.url" :href="link.url" class="page-link">
                <span v-if="link.label.includes('Previous')">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
                        stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M15 6l-6 6l6 6"></path>
                    </svg>
                </span>
                <span v-else-if="link.label.includes('Next')">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
                        stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M9 6l6 6l-6 6"></path>
                    </svg>
                </span>
                <span v-else>{{ link.label }}</span>
                </Link>
                <span v-else class="page-link">
                    <span v-if="link.label.includes('Previous')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
                            stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M15 6l-6 6l6 6"></path>
                        </svg>
                    </span>
                    <span v-else-if="link.label.includes('Next')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
                            stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M9 6l6 6l-6 6"></path>
                        </svg>
                    </span>
                    <span v-else>{{ link.label }}</span>
                </span>
            </li>
        </ul>
    </div>
</template>
