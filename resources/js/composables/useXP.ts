import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useXP() {
    const page = usePage();
    const auth = computed(() => page.props.auth);

    const formatXP = (xp: number): string => {
        if (xp >= 1000) {
            return `${(xp / 1000).toFixed(1)}k`;
        }
        return xp.toString();
    };

    const getUserXP = computed(() => (user: any) => {
        const totalXP = user?.total?.xp ?? user?.total_xp ?? 0;
        const level = user?.level ?? 1;

        return {
            totalXP,
            level,
            formattedXP: formatXP(totalXP)
        };
    });

    const userXPData = computed(() => getUserXP.value(auth.value.user));

    return {
        formatXP,
        getUserXP,
        userXPData,
        auth
    };
}
