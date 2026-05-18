<script setup lang="ts">
import bug1 from '@/../assets/bug1.png';
import bug2 from '@/../assets/bug2.png';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const bugImages = [bug1, bug2];
const bugs = ref<any[]>([]);
const totalBugs = 25;

function seededRandom(seed: number) {
    const x = Math.sin(seed) * 10000;
    return x - Math.floor(x);
}

function generateBugs() {
    const w = window.innerWidth;
    const h = window.innerHeight;
    bugs.value = Array.from({ length: totalBugs }, (_, i) => {
        const src = bugImages[Math.floor(seededRandom(i + 1) * bugImages.length)];
        const size = 80 + seededRandom(i + 5) * 40;
        const top = seededRandom(i + 2) * (h - size);
        const left = seededRandom(i + 3) * (w - size);
        const rotation = seededRandom(i + 4) * 360;
        return { src, top, left, rotation, size };
    });
}

let resizeObserver: ResizeObserver | null = null;
let debounceTimer: ReturnType<typeof setTimeout> | null = null;

onMounted(() => {
    generateBugs();

    const onResize = () => {
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(generateBugs, 300);
    };

    resizeObserver = new ResizeObserver(onResize);
    resizeObserver.observe(document.body);
});

onBeforeUnmount(() => {
    if (debounceTimer) clearTimeout(debounceTimer);
    if (resizeObserver) resizeObserver.disconnect();
});
</script>

<template>
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <img
            v-for="(bug, index) in bugs"
            :key="index"
            :src="bug.src"
            :style="{
                top: bug.top + 'px',
                left: bug.left + 'px',
                width: bug.size + 'px',
                height: bug.size + 'px',
                transform: `rotate(${bug.rotation}deg)`,
            }"
            class="absolute opacity-10"
            alt="bug"
        />
    </div>
</template>

<style scoped></style>
