import 'script-loader!../node_modules/admin-lte/plugins/jQuery/jquery-2.2.3.min';
import 'script-loader!../node_modules/admin-lte/bootstrap/js/bootstrap.min';
import 'script-loader!../node_modules/admin-lte/dist/js/app.min';
import errorLogDashboard from './error-log-dashboard';
import Vue from 'vue';

const vm = new Vue(errorLogDashboard);

vm.$mount('#app');
