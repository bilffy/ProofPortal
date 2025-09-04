// @ts-nocheck
import axios from 'axios';
import jquery from 'jquery';
import 'flowbite';
import 'bootstrap';

window.$ = jquery;
window.jQuery = jquery;
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
