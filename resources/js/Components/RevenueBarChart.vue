<script setup>
import { computed } from 'vue';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
} from 'chart.js';
import { Bar } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps({
    payments: {
        type: Array,
        default: () => [],
    },
});

const chartData = computed(() => {
    // Group payments by day or recent transactions
    const map = {};
    props.payments.forEach((p) => {
        const date = (p.paid_at || '').split('T')[0] || 'Unknown';
        map[date] = (map[date] || 0) + (p.amount || 0);
    });

    const labels = Object.keys(map).slice(-10);
    const data = labels.map((l) => map[l]);

    return {
        labels: labels.length > 0 ? labels : ['Tidak ada data'],
        datasets: [
            {
                label: 'Pendapatan (Rp)',
                backgroundColor: '#1B4332',
                borderRadius: 8,
                data: data.length > 0 ? data : [0],
            },
        ],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            callbacks: {
                label: (ctx) => `Rp ${Number(ctx.raw).toLocaleString('id-ID')}`,
            },
        },
    },
    scales: {
        y: {
            ticks: {
                callback: (val) => `Rp ${(val / 1000).toLocaleString('id-ID')}k`,
            },
        },
    },
};
</script>

<template>
    <div class="h-64 w-full">
        <Bar :data="chartData" :options="chartOptions" />
    </div>
</template>

