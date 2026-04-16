<script setup>
defineProps({
    title: String,
    body: String,
    accent: {
        type: String,
        default: '#5b7cff',
    },
});
</script>

<template>
    <article class="relative overflow-hidden rounded-[28px] bg-[#09121f] p-7 text-white shadow-[0_22px_60px_rgba(0,0,0,0.35)]">
        <div
            class="absolute inset-0 opacity-95"
            :style="{
                background:
                    `radial-gradient(circle at 28% 48%, ${accent} 0%, rgba(32,111,255,0.82) 16%, rgba(16,22,46,0.92) 42%, rgba(5,10,18,1) 100%)`
            }"
        />
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_68%_20%,rgba(255,255,255,0.14),transparent_18%),radial-gradient(circle_at_78%_62%,rgba(59,130,246,0.22),transparent_24%),linear-gradient(135deg,rgba(255,255,255,0.05),transparent_30%)]" />

        <div class="relative h-full min-h-[360px]">
            <div class="absolute left-1/2 top-1/2 w-[min(88%,400px)] -translate-x-1/2 -translate-y-1/2 rounded-[34px] border border-white/10 bg-white/8 px-6 py-7 text-center backdrop-blur-md">
                <div class="mx-auto mb-5 flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-white/10 text-base font-semibold">
                    ✦
                </div>
                <p class="text-[1.2rem] font-semibold leading-tight whitespace-nowrap overflow-hidden text-ellipsis">{{ title }}</p>
                <p class="mx-auto mt-3 max-w-[280px] truncate text-xs leading-5 text-white/75">{{ body }}</p>
            </div>

            <div class="absolute left-6 top-6 h-16 w-16 rounded-[28px] border border-white/10 bg-white/8 shadow-[0_16px_30px_rgba(0,0,0,0.18)]" />
            <div class="absolute bottom-8 right-8 h-24 w-24 rounded-[34px] border border-white/10 bg-white/8 shadow-[0_16px_30px_rgba(0,0,0,0.18)]" />
        </div>
    </article>
</template>
