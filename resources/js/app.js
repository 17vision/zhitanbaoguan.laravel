import './bootstrap';
import NProgress from "nprogress";
import $ from "jquery";

// 进度条
NProgress.configure({ showSpinner: false });

NProgress.start();

window.$ = window.jQuery = $ ;

$(window).on('load', function() {
    NProgress.done();
})
