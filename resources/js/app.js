import Vue from 'vue';
import axios from 'axios';
import router from './router';
import App from './Application';
import vuetify from './vuetify';
import store from './store';
import Helpers from './mixins/Helpers.js';

axios.defaults.headers.common = {
    'X-CSRF-TOKEN': document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute('content'),
    'X-Requested-With': 'XMLHttpRequest',
    Accept: 'application/json',
    'Content-Type': 'application/json'
};

Vue.prototype.$http = axios;

Vue.mixin(Helpers);

const app = new Vue({
    el: '#app',
    router,
    vuetify,
    store,
    components: {
        app: App
    }
});

export default app;
