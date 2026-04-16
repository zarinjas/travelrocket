<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    text: {
        type: String,
        required: true,
    },
    position: {
        type: String,
        default: 'top', // top, bottom, left, right
    },
    icon: {
        type: String,
        default: '❓',
    },
    isOpen: {
        type: Boolean,
        default: false,
    },
});

const showTooltip = ref(props.isOpen);

const positionClasses = {
    top: 'bottom-full mb-3 -translate-x-1/2 left-1/2',
    bottom: 'top-full mt-3 -translate-x-1/2 left-1/2',
    left: 'right-full mr-3 -translate-y-1/2 top-1/2',
    right: 'left-full ml-3 -translate-y-1/2 top-1/2',
};

const arrowClasses = {
    top: 'top-full left-1/2 -translate-x-1/2 border-t-slate-700 border-l-transparent border-r-transparent border-b-transparent',
    bottom: 'bottom-full left-1/2 -translate-x-1/2 border-b-slate-700 border-l-transparent border-r-transparent border-t-transparent',
    left: 'left-full top-1/2 -translate-y-1/2 border-l-slate-700 border-t-transparent border-b-transparent border-r-transparent',
    right: 'right-full top-1/2 -translate-y-1/2 border-r-slate-700 border-t-transparent border-b-transparent border-l-transparent',
};
</script>

<template>
    <div class="relative inline-block">
        <!-- Help Icon Button -->
        <button
            @click="showTooltip = !showTooltip"
            @blur="showTooltip = false"
            type="button"
            class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-600 hover:bg-blue-200 transition-colors hover:scale-110"
            :title="text"
        >
            {{ icon }}
        </button>

        <!-- Tooltip -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-show="showTooltip"
                :class="positionClasses[position]"
                class="absolute z-50 w-64 rounded-lg bg-slate-700 px-3 py-2 text-xs font-medium text-white shadow-lg"
            >
                <slot>{{ text }}</slot>

                <!-- Arrow -->
                <div
                    :class="[arrowClasses[position], 'absolute h-0 w-0 border-4']"
                />
            </div>
        </transition>
    </div>
</template>
