// @ts-nocheck
import axios from 'axios';
import jquery from 'jquery';
import 'flowbite';
import 'bootstrap';
import Tribute from "tributejs";

window.$ = jquery;
window.jQuery = jquery;
window.axios = axios;
window.Tribute = Tribute;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
