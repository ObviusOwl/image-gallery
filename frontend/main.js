import Vue from 'vue';

import App from './components/App.vue';
import { Store } from './store.js';

var store = new Store();

export { store };

export default function () {

    const app = new Vue({
        data: { 
            store: store 
        },
        provide: function(){
            return {
                store: this.store
            };
        },
        render: (h) => h(App),
    });

    app.$mount('#app');
};