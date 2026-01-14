import type { Component } from 'vue';

export interface ProviderConfig {
    name: string;
    icon: Component;
    color: string;
    borderColor: string;
    badgeColor: string;
    bgColor: string;
}

export function useProviderUtils() {
    const getProviderConfig = (provider: string | undefined): ProviderConfig => {
        const normalizedProvider = (provider || 'github').toLowerCase();

        switch (normalizedProvider) {
            case 'github':
                return {
                    name: 'GitHub',
                    icon: GithubIcon,
                    color: 'text-gray-800 dark:text-gray-200',
                    borderColor: 'border-gray-600 dark:border-gray-400',
                    badgeColor: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                    bgColor: 'bg-gray-50 dark:bg-gray-900/20',
                };
            case 'gitlab':
                return {
                    name: 'GitLab',
                    icon: GitlabIcon,
                    color: 'text-orange-600 dark:text-orange-400',
                    borderColor: 'border-orange-500 dark:border-orange-400',
                    badgeColor: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                    bgColor: 'bg-orange-50 dark:bg-orange-900/20',
                };
            case 'bitbucket':
                return {
                    name: 'Bitbucket',
                    icon: BitbucketIcon,
                    color: 'text-blue-600 dark:text-blue-400',
                    borderColor: 'border-blue-500 dark:border-blue-400',
                    badgeColor: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                    bgColor: 'bg-blue-50 dark:bg-blue-900/20',
                };
            default:
                return {
                    name: provider ? provider.charAt(0).toUpperCase() + provider.slice(1) : 'Git',
                    icon: GithubIcon,
                    color: 'text-gray-600 dark:text-gray-400',
                    borderColor: 'border-gray-400 dark:border-gray-500',
                    badgeColor: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                    bgColor: 'bg-gray-50 dark:bg-gray-900/20',
                };
        }
    };

    const getProviderIcon = (provider: string | undefined): Component => {
        return getProviderConfig(provider).icon;
    };

    const getProviderName = (provider: string | undefined): string => {
        return getProviderConfig(provider).name;
    };

    const getProviderColor = (provider: string | undefined): string => {
        return getProviderConfig(provider).color;
    };

    const getProviderBorderColor = (provider: string | undefined): string => {
        return getProviderConfig(provider).borderColor;
    };

    const getProviderBadgeColor = (provider: string | undefined): string => {
        return getProviderConfig(provider).badgeColor;
    };

    const getProviderBgColor = (provider: string | undefined): string => {
        return getProviderConfig(provider).bgColor;
    };

    const extractProviderFromUrl = (url: string | undefined): string => {
        if (!url) return 'github';

        const urlObj = new URL(url);
        const hostname = urlObj.hostname.toLowerCase();

        if (hostname.includes('github.com')) return 'github';
        if (hostname.includes('gitlab.com')) return 'gitlab';
        if (hostname.includes('bitbucket.org')) return 'bitbucket';

        return 'github';
    };

    return {
        getProviderConfig,
        getProviderIcon,
        getProviderName,
        getProviderColor,
        getProviderBorderColor,
        getProviderBadgeColor,
        getProviderBgColor,
        extractProviderFromUrl,
    };
}

const GithubIcon: Component = {
    template: `
        <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
        </svg>
    `,
};

const GitlabIcon: Component = {
    template: `
        <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
            <path d="M2.4 3A1.3 1.3 0 0 0 1.1 4.5l2.7 15.9c.1.5.6.9 1.2.9h13a1.3 1.3 0 0 0 1.2-1.1l2.7-15.7A1.3 1.3 0 0 0 20.7 3H2.4zm9.6 12.3H9.3l-.9-6.6h7.2l-.9 6.6h-2.7z"/>
        </svg>
    `,
};

const BitbucketIcon: Component = {
    template: `
        <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
            <path d="M2.39 9.73L12 22l9.61-12.27a.7.7 0 0 0-.25-.97L19.07 7 16.7 1.27a.7.7 0 0 0-1.32 0L12 7.33 8.62 1.27a.7.7 0 0 0-1.32 0L4.93 7 2.64 8.76a.7.7 0 0 0-.25.97Z"/>
        </svg>
    `,
};
