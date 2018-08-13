require('./bootstrap');
let Vue = window.Vue;

import avatar from './components/common/avatar';
import requestModal from './components/requests/modal'
import notifications from './components/requests/notifications'
import { Navbar } from 'bootstrap-vue/es/components';


// Assign our navbar component to our global ProcessMaker object
window.ProcessMaker.navbar = new Vue({
  el: '#navbar',
  components: {
    Navbar,
    requestModal,
    notifications,
    avatar
  },
  data() {
    return {
      messages: ProcessMaker.notifications,
      alertShow: false,
      alertText: '',
      alertVariant: ''
    }
  },
  mounted: function () {
    Vue.nextTick() // This is needed to override the default alert method.
  .then(function () {
    if (document.querySelector("meta[name='alert']")) {
      ProcessMaker.alert(
        document.querySelector("meta[name='alertMessage']").getAttribute("content"),
        document.querySelector("meta[name='alertVariant']").getAttribute("content")
      )
    }
  })

  }
});

// Set our own specific alert function at the ProcessMaker global object that could
// potentially be overwritten by some custom theme support
window.ProcessMaker.alert = function (msg, variant) {
  ProcessMaker.navbar.alertText = msg;
  ProcessMaker.navbar.alertShow = true;
  ProcessMaker.navbar.alertVariant = String(variant);
}

// Setup our api client interceptor to handle errors and reflect the error
// in our skin.
window.ProcessMaker.apiClient.interceptors.response.use(function (response) {
  // No need to handle success responses
  return response;
}, function (error) {
  // When access token expires: Refresh the token and retry the request
  const apiRequest = error.config;
  if (error.response.status === 401 && !apiRequest.retryApiRequest) {
    apiRequest.retryApiRequest = true;
    const refreshToken = window.localStorage.getItem('refreshToken');
    return window.ProcessMaker.apiClient({url: '/auth/refresh', baseURL: '/', method: 'post'}, {})
      .then(({data}) => {
        window.ProcessMaker.apiClient.defaults.headers.common['Authorization'] = 'Bearer ' + data.access_token;
        apiRequest.headers['Authorization'] = 'Bearer ' + data.access_token;
        apiRequest.baseURL = '';
        return window.ProcessMaker.apiClient(apiRequest);
      });
  }
  let elem = document.getElementById('content-inner');
  if (error.response.status != 422 && error.response.status != 404 && elem !== null) {
    // Replace our content div with our error div
    // Remove our #content-inner
    elem.parentNode.removeChild(elem);
    // Now show our #api-error div
    elem = document.getElementById('api-error');
    elem.setAttribute('style', 'display: block');
  }
  if (error.response.data && error.response.data.message) {
    window.ProcessMaker.alert(error.response.data.message, 'danger');
  }
  return Promise.reject(error);
});

// Use this method to trigger the sidebar menu to open and closed
$("#menu-toggle").click(function (e) {
  e.preventDefault();

  if (document.getElementById("sidebar-inner").classList.contains("closed")) {

    document.getElementById("sidebar").style.maxWidth = "250px";
    document.getElementById("sidebar").classList.remove('closed');
    document.getElementById("sidebar-inner").classList.remove('closed');

  } else {

    document.getElementById("sidebar").style.maxWidth = "58px";
    document.getElementById("sidebar").classList.add('closed');
    document.getElementById("sidebar-inner").classList.add('closed');
    document.getElementById("mainbody").style.maxWidth = "100%";

  }
});
