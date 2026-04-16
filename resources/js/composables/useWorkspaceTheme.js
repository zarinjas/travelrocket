import { computed, ref } from 'vue';

const PALETTE_STORAGE_KEY = 'travelrocket_workspace_palette';
const ALLOWED_PALETTES = new Set(['sand-mint', 'arctic-graphite']);

const theme = ref('light');
const palette = ref('sand-mint');
let initialized = false;

const normalizePalette = (value) => (ALLOWED_PALETTES.has(value) ? value : 'sand-mint');

const applyTheme = (nextTheme) => {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.setAttribute('data-theme', nextTheme);
    document.documentElement.style.colorScheme = nextTheme;
};

const applyPalette = (nextPalette) => {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.setAttribute('data-workspace-palette', nextPalette);
};

export const initializeWorkspaceTheme = () => {
    if (initialized) {
        return;
    }

    const savedPalette = typeof window !== 'undefined'
        ? window.localStorage.getItem(PALETTE_STORAGE_KEY)
        : null;

    theme.value = 'light';
    palette.value = normalizePalette(savedPalette);
    applyTheme(theme.value);
    applyPalette(palette.value);
    initialized = true;
};

export const useWorkspaceTheme = () => {
    initializeWorkspaceTheme();

    const setPalette = (nextPalette) => {
        const normalizedPalette = normalizePalette(nextPalette);
        palette.value = normalizedPalette;

        if (typeof window !== 'undefined') {
            window.localStorage.setItem(PALETTE_STORAGE_KEY, normalizedPalette);
        }

        applyPalette(normalizedPalette);
    };

    return {
        theme,
        palette,
        isDark: computed(() => false),
        setTheme: () => {},
        toggleTheme: () => {},
        setPalette,
    };
};
