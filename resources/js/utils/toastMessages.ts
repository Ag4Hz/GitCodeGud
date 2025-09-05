type Locale = 'en';
type AdminMessage = {
    [key in Locale]: {
        success: { [action: string]: string };
        error: { [action: string]: string };
    };
};
type BountyMessage = {
    [key in Locale]: {
        success: { [action: string]: string };
        error: { [action: string]: string };
    };
};
type XPSyncMessage = {
    [key in Locale]: {
        success: { [action: string]: string };
        error: { [action: string]: string };
    };
};

const adminMessages: AdminMessage = {
    en: {
        success: {
            base: 'Base settings updated successfully.',
            threshold: 'Threshold settings updated successfully.',
            skill_weights: 'Skill weights updated successfully.',
            batch_update: 'All settings updated successfully.',
            recalculate: 'XP recalculated for all users successfully.',
        },
        error: {
            base: 'Failed to update base settings.',
            threshold: 'Failed to update threshold settings.',
            skill_weights: 'Failed to update skill weights.',
            batch_update: 'Failed to update all settings.',
            recalculate: 'Failed to recalculate XP for all users.',
        },
    },
};

const bountyMessages: BountyMessage = {
    en: {
        success: {
            create: 'Bounty created successfully.',
            update: 'Bounty updated successfully.',
            delete: 'Bounty deleted successfully.',
            claim: 'Bounty claimed successfully.',
            unclaim: 'Bounty unclaimed successfully.',
            complete: 'Bounty marked as complete successfully.',
        },
        error: {
            create: 'Failed to create bounty.',
            update: 'Failed to update bounty.',
            delete: 'Failed to delete bounty.',
            claim: 'Failed to claim bounty.',
            unclaim: 'Failed to unclaim bounty.',
            complete: 'Failed to mark bounty as complete.',
        },
    },
};

const xpSyncMessages: XPSyncMessage = {
    en: {
        success: {
            sync: 'XP synchronized successfully.',
        },
        error: {
            sync: 'Failed to synchronize XP.',
        },
    },
};

export function getAdminMessage(type: 'success' | 'error', action: string, locale: Locale = 'en'): string {
    return adminMessages[locale]?.[type]?.[action] || 'Operation completed.';
}

export function getBountyMessage(type: 'success' | 'error', action: string, locale: Locale = 'en'): string {
    return bountyMessages[locale]?.[type]?.[action] || 'Operation completed.';
}

export function getXPSyncMessage(type: 'success' | 'error', action: string, locale: Locale = 'en'): string {
    return xpSyncMessages[locale]?.[type]?.[action] || 'Operation completed.';
}
