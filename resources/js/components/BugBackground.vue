<script setup lang="ts">
import bug1 from '@/../assets/bug1.png';
import bug2 from '@/../assets/bug2.png';
import { ref, onMounted } from 'vue';

const bugImages = [bug1, bug2];
const bugs = ref<any[]>([]);
const totalBugs = 25;
const documentHeight = ref(window.innerHeight);
const documentWidth = ref(window.innerWidth);

function seededRandom(seed: number) {
    const x = Math.sin(seed) * 10000;
    return x - Math.floor(x);
}

function generateBugs() {
    bugs.value = Array.from({ length: totalBugs }, (_, i) => {
        const src = bugImages[Math.floor(seededRandom(i + 1) * bugImages.length)];
        const size = 80 + seededRandom(i + 5) * 40;
        const top = seededRandom(i + 2) * (documentHeight.value - size);
        const left = seededRandom(i + 3) * (documentWidth.value - size);
        const rotation = seededRandom(i + 4) * 360;
        return { src, top, left, rotation, size };
    });
}

onMounted(() => {
    const updateSize = () => {
        documentHeight.value = document.body.scrollHeight;
        documentWidth.value = document.body.scrollWidth;
        generateBugs();
    };

    updateSize();
    window.addEventListener('resize', updateSize);
});
</script>

<template>
    <div
        class="pointer-events-none"
        :style="{ position: 'absolute', top: 0, left: 0, width: '100%', height: documentHeight + 'px', overflow: 'visible' }"
    >
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
