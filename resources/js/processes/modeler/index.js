import Vue from "vue";
import ModelerApp from "./components/ModelerApp";


let renderWhenTranslationAvailable = () => {
    if(ProcessMaker.i18n.exists('Save') === false) {
        window.setTimeout(() => renderWhenTranslationAvailable(), 100);
    }
    else {
        new Vue({
            render: h => h(ModelerApp)
            }).$mount("#modeler-app");
    }
};


renderWhenTranslationAvailable();

// new Vue({
//     render: h => {
//         let renderWhenTranslationAvailable = (h) => {
//             if(ProcessMaker.i18n.exists('Save') === false) {
//                 window.setTimeout(() => renderWhenTranslationAvailable(h), 100);
//             }
//             else {
//                 return h(ModelerApp);
//             }
//         };
//
//         return renderWhenTranslationAvailable(h)
// }}).$mount("#modeler-app");
