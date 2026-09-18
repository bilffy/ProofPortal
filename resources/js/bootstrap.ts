// @ts-nocheck
import axios from 'axios';
import jquery from 'jquery';
import moment from 'moment';
import flatpickr from 'flatpickr';
import { Modal } from 'flowbite';
import 'bootstrap';
import Tribute from "tributejs";

// Prefer legacy proofing jquery (loaded in app.blade with select2/plugins) when present.
// Overwriting it with Vite's copy breaks select2 and change handlers on Configure.
if (!window.jQuery) {
    window.$ = jquery;
    window.jQuery = jquery;
} else {
    window.$ = window.jQuery;
}
window.axios = axios;
window.moment = moment;
window.flatpickr = flatpickr;
window.Tribute = Tribute;
// Several inline page scripts (proofing master layout, photography, the
// authenticated layout) call `new Modal(...)` expecting a global - they
// don't import it themselves, so it has to be exposed here.
window.Modal = Modal;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
