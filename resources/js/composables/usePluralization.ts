export function usePluralization() {
    const pluralize = (count: number, singular: string, plural?: string): string => {
        if (count === 1) {
            return singular;
        }
        return plural || `${singular}s`;
    };

    const formatCount = (count: number, singular: string, plural?: string): string => {
        return `${count} ${pluralize(count, singular, plural)}`;
    };

    return {
        pluralize,
        formatCount,
    };
}
