
window._ = require('lodash');

/**
 * We'll load jQuery and the Bootstrap jQuery plugin which provides support
 * for JavaScript based Bootstrap features such as modals and tabs. This
 * code may be modified to fit the specific needs of your application.
 */

try {
    window.$ = window.jQuery = require('jquery');

    require('foundation-sites/dist/js/foundation'); // 'foundation.min' can also be used if you like
    // require( 'datatables.net/js/jquery.dataTables' );
    require( 'datatables.net-zf' );
    // require( 'datatables.net-responsive/js/dataTables.responsive' );
    require( 'datatables.net-responsive-zf' );
    
    require( 'datatables.net-colreorder');
    //require('footable/dist/footable');
    //
    require('easy-autocomplete/dist/jquery.easy-autocomplete');


    var Chart = require('chart.js');
    
    var Chartist = require( 'chartist/dist/chartist' );
    window.Chartist = Chartist;



    
} catch (e) {}



/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Next we will register the CSRF Token as a common header with Axios so that
 * all outgoing HTTP requests automatically have it attached. This is just
 * a simple convenience so we don't have to attach every token manually.
 */

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo'

window.Pusher = require('pusher-js');

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: '11e4ce555a8c88dc2f4c',
    cluster: 'eu',
    encrypted: true
});
