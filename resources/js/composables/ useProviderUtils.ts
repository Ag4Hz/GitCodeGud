import { Github, GitlabIcon as Gitlab } from 'lucide-vue-next';
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
                    icon: Github,
                    color: 'text-gray-800 dark:text-gray-200',
                    borderColor: 'border-gray-600 dark:border-gray-400',
                    badgeColor: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                    bgColor: 'bg-gray-50 dark:bg-gray-900/20',
                };
            case 'gitlab':
                return {
                    name: 'GitLab',
                    icon: Gitlab,
                    color: 'text-orange-600 dark:text-orange-400',
                    borderColor: 'border-orange-500 dark:border-orange-400',
                    badgeColor: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                    bgColor: 'bg-orange-50 dark:bg-orange-900/20',
                };
            case 'bitbucket':
                return {
                    name: 'Bitbucket',
                    icon: Bitbucket,
                    color: 'text-blue-600 dark:text-blue-400',
                    borderColor: 'border-blue-500 dark:border-blue-400',
                    badgeColor: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                    bgColor: 'bg-blue-50 dark:bg-blue-900/20',
                };
            default:
                return {
                    name: provider ? provider.charAt(0).toUpperCase() + provider.slice(1) : 'Git',
                    icon: Github,
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

        try {
            const urlObj = new URL(url);
            const hostname = urlObj.hostname.toLowerCase();

            if (hostname.includes('github.com')) return 'github';
            if (hostname.includes('gitlab.com')) return 'gitlab';
            if (hostname.includes('bitbucket.org')) return 'bitbucket';

            return 'github';
        } catch {
            return 'github';
        }
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

const Bitbucket = {
    template: `
        <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
            <path d="M2.654 3.004A1.51 1.51 0 001.15 4.48l2.776 16.456a2.052 2.052 0 002.02 1.72h12.11a1.513 1.513 0 001.487-1.246L22.35 4.48a1.51 1.51 0 00-1.505-1.476zm10.794 12.972H9.47l-1.123-6.36h5.804z"/>
        </svg>
    `,
};
