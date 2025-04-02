import { get } from 'lodash';

export default {
  methods: {
    containsHTML(text) {
      const doc = new DOMParser().parseFromString(text, 'text/html');
      return Array.from(doc.body.childNodes).some(node => node.nodeType === Node.ELEMENT_NODE);
    },
    isComponent(content) {
      if (content && typeof content === 'object') {
        return content.component && typeof content.props === 'object';
      }
      return false;
    },
    sanitize(html) {
      return this.removeScripts(html);
    },
    removeScripts(input) {
      const doc = new DOMParser().parseFromString(input, 'text/html');

      const scripts = doc.querySelectorAll('script');
      scripts.forEach((script) => {
        script.remove();
      });

      const styles = doc.querySelectorAll('style');
      styles.forEach((style) => {
        style.remove();
      });

      return doc.body.innerHTML;
    },
    changePage(page) {
      this.page = page;
      this.fetch();
    },
    changePerPage(value, page) {
      this.page = page;
      this.perPage = value;
      this.fetch();
    },
    formatAvatar(user, $usePmDefaultLabel = false, $usePmDefaultLabelProcess = false) {
      return {
        component: "AvatarImage",
        props: {
          size: "25",
          "input-data": user,
          "hide-name": false,
          "name-clickable": true,
          'use-pm-default-label': $usePmDefaultLabel,
          'use-pm-default-label-process': $usePmDefaultLabelProcess
        },
      };
    },
    formatCategory(categories) {
      return categories?.map(item => item.name).join(', ');
    },
    getNestedPropertyValue(obj, header) {
      const value = get(obj, header.field);

      if (typeof header.cb === 'function') {
        return header.cb(value, obj);
      }

      return this.format(value, header);
    },
    format(value, header) {
      let config = "";
      if (header.format === "datetime") {
        config = ProcessMaker.user.datetime_format;
        value = this.convertUTCToLocal(value, config)
      }
      if (header.format === "date") {
        config = ProcessMaker.user.datetime_format.replace(/[\sHh:msaAzZ]/g, "");
        value = this.convertUTCToLocal(value, config)
      }
      return value;
    },
    convertUTCToLocal(value, config) {
      if (value) {
        if (moment(value).isValid()) {
          return window.moment(value)
            .format(config);
        }
        return value;
      }
      return "-";
    },
    checkIfTooltipIsNeeded(e,v){
      if (e.target.offsetWidth >= e.target.scrollWidth) {
        e.preventDefault();
      }
    },
  },
};
