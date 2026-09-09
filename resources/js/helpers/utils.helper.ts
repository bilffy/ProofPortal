import { NAV_TABS } from "./constants.helper";

export const getCurrentNav = (): string => {
    const { HOME, FRANCHISE_DASHBOARD, MANAGE_USERS, PROOFING, CONFIG_SCHOOL, PHOTOGRAPHY , ORDER, APP_SETTINGS, REPORTS, EMAILS} = NAV_TABS;
    const segments = window.location.pathname.split('/').filter(Boolean);
    const path = segments[0] ?? '';
    const subPath = segments[1] ?? '';
    
    switch(path) {
        case '':
        case 'dashboard':
          return HOME;
        case 'franchise-dashboard':
          return FRANCHISE_DASHBOARD;
        case 'users':
            return MANAGE_USERS;
        case 'proofing':
            return PROOFING;
        case 'config-school':
            return CONFIG_SCHOOL;
        case 'photography':
            return PHOTOGRAPHY;
        case 'order':
            return ORDER;    
        case 'settings':
            return APP_SETTINGS;
        case 'reports':
            return REPORTS;
        case 'emails':
            return EMAILS;
        default:
          return '';
    }
};

export const getNavTabId = (tab: string): string => {
    const { HOME, FRANCHISE_DASHBOARD, MANAGE_USERS, PROOFING, CONFIG_SCHOOL, PHOTOGRAPHY , ORDER, APP_SETTINGS, REPORTS, EMAILS} = NAV_TABS;

    switch(tab) {
        case '':
        case HOME:
          return 'tabHome';
        case FRANCHISE_DASHBOARD:
            return 'tabFranchiseDashboard';
        case MANAGE_USERS:
            return 'tabManageUsers';
        case PROOFING:
            return 'tabProofing';
        case REPORTS:
            return 'tabReports';
        case EMAILS:
            return 'tabEmails';
        case CONFIG_SCHOOL:
            return 'tabSchoolConfig';
        case PHOTOGRAPHY:
            return 'tabPhotography';
        case ORDER:
            return 'tabOrder';    
        case APP_SETTINGS:
            return 'tabManageSettings';    
        default:
          return 'tabHome';
    }
};
