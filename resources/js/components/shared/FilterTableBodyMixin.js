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
    formatAvatar(user) {
      return {
        component: "AvatarImage",
        props: {
          size: "25",
          "input-data": user,
          "hide-name": false,
        },
      };
    },
    formatCategory(categories) {
      return categories.map(item => item.name).join(', ');
    },
    getNestedPropertyValue(obj, path) {
      return get(obj, path);
    },
  },
};
