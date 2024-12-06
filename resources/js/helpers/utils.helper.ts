import { NAV_TABS } from "./constants.helper";

export const getCurrentNav = (): string => {
    const { HOME, MANAGE_USERS, PROOFING, CONFIG_SCHOOL, PHOTOGRAPHY } = NAV_TABS;
    const path = window.location.pathname.split('/')[1];

    switch(path) {
        case '':
        case 'dashboard':
          return HOME;
        case 'users':
            return MANAGE_USERS;
        case 'proofing':
            return PROOFING;
        case 'config-school':
            return CONFIG_SCHOOL;
        case 'photography':
            return PHOTOGRAPHY;
        default:
          return '';
    }
};

export const getNavTabId = (tab: string): string => {
    const { HOME, MANAGE_USERS, PROOFING, CONFIG_SCHOOL, PHOTOGRAPHY } = NAV_TABS;

    switch(tab) {
        case '':
        case HOME:
          return 'tabHome';
        case MANAGE_USERS:
            return 'tabManageUsers';
        case PROOFING:
            return 'tabProofing';
        case CONFIG_SCHOOL:
            return 'tabSchoolConfig';
        case PHOTOGRAPHY:
            return 'tabPhotography';
        default:
          return 'tabHome';
    }
};
