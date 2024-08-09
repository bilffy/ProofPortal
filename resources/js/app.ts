import './bootstrap'
import '../css/app.scss'
import { createApp, h, type DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { ZiggyVue } from '../../vendor/tightenco/ziggy/src/js';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import FontAwesomeIcon from './fontawesome-icons';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    id: 'app',
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .component('font-awesome-icon', FontAwesomeIcon)
            .use(plugin)
            .use(ZiggyVue)
            .mount(el)
    },
    progress: {
      color: '#4B5563',
  },
})
