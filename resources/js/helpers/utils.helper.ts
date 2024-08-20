import { inject } from "vue";
import { LAYOUT_RENDER, NAV_TABS } from "./constants.helper";

export const getCurrentNav = (): string => {
    const { HOME, MANAGE_USERS, PROOFING } = NAV_TABS;
    const path = window.location.pathname.split('/')[1];

    switch(path) {
        case '':
        case 'dashboard':
          return HOME;
        case 'users':
            return MANAGE_USERS;
        case 'proofing':
            return PROOFING;
        default:
          return '';
    }
};

export const getRoute = (routeName: string): string => {
    if (LAYOUT_RENDER.INERTIA === inject('layout_render')) {
        return route(routeName);
    } else {
        // return routeName with from matched mappings, or return as is
        const root = window.location.origin;
        switch (routeName) {
            case 'dashboard':
                return `${root}`
            case 'users.manage':
                return `${root}/users`;
            default:
                return `${root}/${routeName}`;
        }
    }
}
