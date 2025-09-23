// @ts-nocheck
import axios from 'axios';
import jquery from 'jquery';
import moment from 'moment';
import flatpickr from 'flatpickr';
import 'flowbite';
import 'bootstrap';

window.$ = jquery;
window.jQuery = jquery;
window.axios = axios;
window.moment = moment;
window.flatpickr = flatpickr;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
