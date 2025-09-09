// resources/js/app.ts

// Import jQuery and expose globally
import jquery from 'jquery';
window.$ = window.jQuery = jquery;

// Import Flowbite Modal and expose globally
import 'flowbite';
import { Modal } from 'flowbite';
window.Modal = Modal;

// Include other assets (images, CSS handled by Vite)
import.meta.glob([
    '../assets/**',
]);

// Initialize modals globally if needed (optional)
document.addEventListener('DOMContentLoaded', () => {
    const confirmDownloadModalEl = document.getElementById('confirmDownloadModal');
    const successDownloadModalEl = document.getElementById('successDownloadModal');
    const showOptionsDownloadModalEl = document.getElementById('showOptionsDownloadModal');
    const confirmReloadPageModalEl = document.getElementById('confirmReloadPageModal');

    if (confirmDownloadModalEl) window.confirmDownloadModal = new Modal(confirmDownloadModalEl);
    if (successDownloadModalEl) window.successDownloadModal = new Modal(successDownloadModalEl);
    if (showOptionsDownloadModalEl) window.showOptionsDownloadModal = new Modal(showOptionsDownloadModalEl);
    if (confirmReloadPageModalEl) window.confirmReloadPageModal = new Modal(confirmReloadPageModalEl);
});
