export function getInitials(fullName?: string): string {
    if (!fullName) return '';

    const names = fullName.trim().split(' ').filter(name => name.length > 0);

    if (names.length === 0) return '';

    if (names.length === 1) {
        const name = names[0];
        return name.length >= 2
            ? name.substring(0, 2).toUpperCase()
            : name.charAt(0).toUpperCase();
    }

    return `${names[0].charAt(0)}${names[names.length - 1].charAt(0)}`.toUpperCase();
}

export function useInitials() {
    return { getInitials };
}


