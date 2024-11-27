import { NAV_TABS } from "./constants.helper";

export const getCurrentNav = (): string => {
    const { HOME, MANAGE_USERS, PROOFING, PHOTOGRAPHY } = NAV_TABS;
    const path = window.location.pathname.split('/')[1];

    switch(path) {
        case '':
        case 'dashboard':
          return HOME;
        case 'users':
            return MANAGE_USERS;
        case 'proofing':
            return PROOFING;
        case 'photography':
            return PHOTOGRAPHY;
        default:
          return '';
    }
};

export const getNavTabId = (tab: string): string => {
    const { HOME, MANAGE_USERS, PROOFING, PHOTOGRAPHY } = NAV_TABS;

    switch(tab) {
        case '':
        case HOME:
          return 'tabHome';
        case MANAGE_USERS:
            return 'tabManageUsers';
        case PROOFING:
            return 'tabProofing';
        case PHOTOGRAPHY:
            return 'tabPhotography';
        default:
          return 'tabHome';
    }
};
