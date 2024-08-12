import { NAV_TABS } from "./constants.helper";

export const getCurrentNav = (): string => {
    const { HOME, MANAGE_USERS } = NAV_TABS;
    const path = window.location.pathname.split('/')[1];

    switch(path) {
        case '':
        case 'dashboard':
          return HOME;
        case 'users':
            return MANAGE_USERS;
        default:
          return '';
    }
};
