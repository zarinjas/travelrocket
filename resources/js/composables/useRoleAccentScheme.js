import { computed } from 'vue';

const DEFAULT_SCHEMES = {
    owner: '#d97706',
    staff: '#0f766e',
    platform_admin: '#334155',
};

const normalizeRole = (role) => {
    const value = String(role || '').toLowerCase().trim();

    if (value.includes('platform') && value.includes('admin')) {
        return 'platform_admin';
    }

    if (value === 'staff') {
        return 'staff';
    }

    return 'owner';
};

export const useRoleAccentScheme = (roleRef, schemesRef) => {
    const roleKey = computed(() => normalizeRole(roleRef?.value));

    const resolvedSchemes = computed(() => ({
        ...DEFAULT_SCHEMES,
        ...(schemesRef?.value || {}),
    }));

    const accentColor = computed(() => resolvedSchemes.value[roleKey.value] || DEFAULT_SCHEMES.owner);

    return {
        roleKey,
        accentColor,
        DEFAULT_SCHEMES,
    };
};
