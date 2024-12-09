const addScriptsToDOM = async function(scripts) {
  for (const script of scripts) {
    await new Promise((resolve, reject) => {
      const scriptElement = document.createElement('script');
      scriptElement.src = script;
      scriptElement.async = false;
      scriptElement.onload = resolve;
      scriptElement.onerror = reject;
      document.head.appendChild(scriptElement);
    });
  }
};

window.Vue.component('VueFormRenderer', function (resolve, reject) {
  console.log("VUE FORM RENDERER")
  import("@processmaker/screen-builder/dist/vue-form-builder.css");

  if(screenBuilderScripts){
    console.log("SCREEN BUILDER SCRIPTS 7777", screenBuilderScripts)
    addScriptsToDOM(screenBuilderScripts).then(()=>{
      import("@processmaker/screen-builder").then((ScreenBuilder)=>{
        window.Vue.use(ScreenBuilder.default);
        window.ScreenBuilder = ScreenBuilder;
  
        const { initializeScreenCache } = ScreenBuilder;
        // Configuration Global object used by ScreenBuilder
        // @link https://processmaker.atlassian.net/browse/FOUR-6833 Cache configuration
        const screenCacheEnabled = document.head.querySelector("meta[name=\"screen-cache-enabled\"]")?.content ?? "false";
        const screenCacheTimeout = document.head.querySelector("meta[name=\"screen-cache-timeout\"]")?.content ?? "5000";
        window.ProcessMaker.screen = {
          cacheEnabled: screenCacheEnabled === "true",
          cacheTimeout: Number(screenCacheTimeout),
        };
        // Initialize screen-builder cache
        initializeScreenCache(window.ProcessMaker.apiClient, window.ProcessMaker.screen);
        console.log("VUE FORM RESOLVE")
        resolve(ScreenBuilder.VueFormRenderer);
      }).catch(reject);
    })
  }
});







