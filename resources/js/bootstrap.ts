// @ts-nocheck
import axios from 'axios';
import jquery from 'jquery';

window.$ = jquery;
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// window.getNav = getCurrentNav;

// window.fn = {
//     test: () => "just a test",
//     // getCurrentNav: getCurrentNav,
// };

// window.fn = {
//     getCurrentNav
// }
