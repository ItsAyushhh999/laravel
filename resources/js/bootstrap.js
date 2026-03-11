import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true; // send cookies
axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN'; // default in Laravel
axios.defaults.xsrfCookieName = 'XSRF-TOKEN';

axios.get('https://login.example.com/sanctum/csrf-cookie')
  .then(() => {
    // login
    return axios.post('https://api.login.example.com/login', {
      email: 'user@example.com',
      password: 'secret',
    });
  })
  .then(response => {
    console.log('Logged in:', response.data);
  })
  .catch(error => {
    console.error('Error logging in', error);
  });

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
