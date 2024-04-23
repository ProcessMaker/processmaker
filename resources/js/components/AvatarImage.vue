<template>
  <span>
    <div :class="{ 'd-inline-flex': !vertical }">
    <template v-for="(value, key) in options">
      <div class="vertical-view">
      <b-button
        ref="button"
        :variant="variant(value)"
        class="avatar-button rounded-circle overflow-hidden p-0 m-0 d-inline-flex border-0"
        :href="href(value)"
        :key="'link-' + key"
        :title="value.tooltip"
        :aria-label="value.tooltip"
        :role="role(value)"
        :aria-haspopup="ariaHasPopup(value)"
        :disabled="disabled(value)"
        :aria-expanded="ariaExpanded"
      >
        <img
          v-if="value.src"
          :src="timestamp(value.src)"
          :width="sizeImage"
          :height="sizeImage"
          :class="image"
          :alt="value.tooltip"
        >
        <span
          v-else
          :key="'button-' + key"
          class="border-0 d-inline-flex align-items-center justify-content-center text-white text-uppercase text-nowrap font-weight-normal"
          :style="styleButton"
        >
          <span v-if="value.initials">{{value.initials}}</span>
          <span v-else>PM</span>
        </span>
      </b-button>
      <span v-if="!hideName" class="text-center text-capitalize new-wrap m-1" :key="'name-' + key">
          <span v-if="value.name">{{ limitCharacters(value.name)}}</span>
          <span v-else>ProcessMaker</span>
      </span>
      </div>
    </template>
    </div>
  </span>
</template>

<script>
export default {
  props: {
    size: {
      default: null,
    },
    rounded: {
      default: true,
    },
    classContainer: {
      default: null,
    },
    classImage: {
      default: null,
    },
    inputData: {
      default: null,
    },
    hideName: {
      default: false,
    },
    popover: {
      type: Boolean,
      default: false,
    },
    characterLimit: {
      type: Number,
      default: null,
    },
    vertical: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      ariaExpanded: false,
      round: "circle",
      image: "",
      styleButton: "width: 25px; height: 25px;",
      options: []
    };
  },
  watch: {
    inputData(value) {
      this.formatInputData(value);
    },
    size(value) {
      this.formatSize(value);
    },
    rounded(value) {
      this.formatRounded(value);
    },
    classImage(value) {
      this.formatClassImage(value);
    }
  },
  methods: {
    getTarget() {
      return this.$refs.button[0];
    },
    expanded(value) {
      this.ariaExpanded = value;
    },
    href(value) {
      if (this.popover) {
        return null;
      } else {
        return value.id;
      }
    },
    role(value) {
      if (this.popover) {
        return 'button';
      } else {
        if (! value.id) {
          return 'img'
        } else {
          return 'link';
        }
      }
    },
    ariaHasPopup(value) {
      if (this.popover) {
        return 'menu';
      } else {
        return null;
      }
    },
    disabled(value) {
      if (! this.popover) {
        if (! value.id) {
          return true;
        }
      };

      return false;
    },
    variant(value) {
      if (value.src) {
        return 'secondary';
      } else {
        return 'info';
      }
    },
    timestamp(src) {
      if (src.startsWith('data:image')) {
        // Do not add cache buster to base64 encoded image
        return src
      }
      return src + '?' + new Date().getTime();
    },
    default() {
      this.displayTitle = this.hideName === undefined ? false : this.hideName;
      this.formatRounded(this.rounded);
      this.formatClassImage(this.classImage);
      this.formatInputData(this.inputData);
      this.formatSize(this.size);
    },
    formatClassImage(value) {
      this.image = value;
    },
    formatRounded(value) {
      this.round = value ? value : "circle";
    },
    formatSize(size) {
      this.sizeImage = size ? size : "25";
      this.formatSizeButton(this.sizeImage);
    },
    formatSizeButton(size) {
      this.styleButton =
        "width: " +
        size +
        "px; height: " +
        size +
        "px; font-size:" +
        size / 2.5 +
        "px; padding:0; cursor: pointer;";
    },
    formatValue(value) {
      if (value === null) {
        value = {};
      }

      let profileUrl = null;
      if (value.id) {
        if (value.id === '#') {
          profileUrl = '#';
        } else {
          profileUrl = "/profile/" + value.id
        }
      }
      return {
        id: profileUrl,
        src: value.src ? value.src : value.avatar ? value.avatar : "",
        tooltip: value.tooltip
          ? value.tooltip
          : !this.displayTitle
          ? value.title
          : value.fullname
          ? value.fullname
          : "",
        name:
          value.name
            ? value.name
            : value.fullname
            ? value.fullname
            : value.firstname && value.lastname
            ? value.firstname + ' ' + value.lastname
            : "",
        initials: value.initials
          ? value.initials
          : value.firstname && value.lastname
          ? value.firstname.match(/./u)[0] + value.lastname.match(/./u)[0]
          : ""
      };
    },
    formatInputData(data) {
      let options = [];
      if (data && Array.isArray(data)) {
        let that = this;
        data.forEach(value => {
          options.push(that.formatValue(value));
        });
      } else {
        options.push(this.formatValue(data));
      }
      this.options = options;
    },
    buttonClick(url) {
      if (url && url !== '#') {
        window.location.href = url;
      }
    },
    limitCharacters(text) {
      if (!this.characterLimit || text.length <= this.characterLimit) {
        return text;
      } else {
        return text.substring(0, this.characterLimit) + '...';
      }
    }
  },
  mounted() {
    this.default();
  }
};
</script>

<style lang="scss" scoped>
  .avatar-button.disabled,
  .avatar-button:disabled {
    opacity: 1;
    pointer-events: none;
  }
  .vertical-view {
    padding-top: 4px;
    padding-bottom: 4px;
  }
  .new-wrap {
    overflow-wrap: anywhere;
  }
</style>
