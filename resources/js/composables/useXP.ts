export function formatXP(xp: number): string {
    if (xp >= 1000) {
        return `${(xp / 1000).toFixed(1)}k`;
    }
    return xp.toString();
}

export function getUserXP(user: any) {
    const totalXP = user?.total?.xp ?? user?.total_xp ?? 0;
    const level = user?.level ?? 1;

    return {
        totalXP,
        level,
        formattedXP: formatXP(totalXP)
    };
}

export function useXP() {
    return {
        formatXP,
        getUserXP
    };
}
