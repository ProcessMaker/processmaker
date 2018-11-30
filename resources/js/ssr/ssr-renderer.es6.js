import Vue from 'vue'
import renderVueComponentToString from 'vue-server-renderer/basic';

var app = new Vue({
    template: '<div>Test SSR</div>',
});

renderVueComponentToString(app, (err, html) => {
	if (err) {
		throw new Error(err);
	}
	console.log(html)
});
