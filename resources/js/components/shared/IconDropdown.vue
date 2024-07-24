<template>
  <div class="multiselect-icons custom-multiselect">
    <b-input-group>
      <multiselect
        ref="multiselect"
        v-model="icon"
        track-by="value"
        label="label"
        :select-label="$t('Press enter to select')"
        :select-group-label="$t('Press enter to select group')"
        :selected-label="$t('Selected')"
        :deselect-label="$t('Press enter to remove')"
        :deselect-group-label="$t('Press enter to remove group')"
        :placeholder="placeholder"
        :show-labels="false"
        :options="list"
        :multiple="false"
        :searchable="true"
        :internal-search="false"
        :allow-empty="false"
        @input="onSelect"
        @search-change="onSearch"
        @open="onOpen"
        @close="onClose"
      >
        <template
          slot="singleLabel"
          slot-scope="props"
        >
          <span>
            <img
              class="icon-selected"
              :src="`/img/launchpad-images/icons/${props.option.value}.svg`"
              :alt="props.option.value"
            >
            {{ props.option.label }}
          </span>
        </template>
        <template
          slot="option"
          slot-scope="props"
        >
          <div
            class="icon-squares"
            @mouseover="onHover(props.option)"
          >
            <img
              class="icon-select"
              :src="`/img/launchpad-images/icons/${props.option.value}.svg`"
              :alt="props.option.value"
            >
          </div>
        </template>
      </multiselect>
    </b-input-group>
  </div>
</template>

<script>
import Icons from "./LaunchpadIcons";

export default {
  props: {
    value: {
      required: false,
    },
    default: {
      type: String,
      default: "search",
    },
  },
  data() {
    return {
      all: Icons.list(),
      icon: null,
      list: {},
      loading: true,
      defaultIcon: {
        value:'Default Icon',
        label: this.$t("Default Icon"),
      },
      placeholder: this.$t("Select Icon"),
      query: "",
    };
  },
  computed: {
    isOpen() {
      return this.$refs.multiselect.isOpen;
    },
  },
  watch: {
    value() {
      this.icon = this.find(this.value);
    },
  },
  beforeMount() {
    this.list = this.all;
  },
  mounted() {
    this.icon = this.value ? this.find(this.value) : this.defaultIcon;
    this.onSelect(this.icon);
  },
  methods: {
    onSearch(query) {
      this.query = query.toLowerCase();
      if (this.query.length) {
        this.list = this.all.filter((icon) => icon.label.toLowerCase().includes(this.query));
      } else {
        this.list = this.all;
      }
    },
    onOpen() {
      this.$refs.multiselect.search = this.query;
    },
    onClose() {
      this.placeholder = this.$t("Select Icon");
    },
    find(value) {
      return this.all.find((icon) => icon.value === value);
    },
    onHover(icon) {
      this.placeholder = icon.label;
    },
    onSelect(value) {
      this.$root.$emit("launchpadIcon", value);
    },
    setIcon(icon) {
      this.icon = {
        value: icon,
        label: icon,
      };
    },
  },
};
</script>

<style lang="scss">
$iconSize: 19px;
$multiselect-height: 33px;

.multiselect-icons.custom-multiselect {
  .input-group {
    width: 100%;
  }

  .multiselect,
  .multiselect__tags {
    height: 40px;
    min-height: 40px;
    max-height: 40px;
    border-radius: 4px;
    border-color: #cdddee;
  }

  .multiselect__Select {
    width: 28px;
    height: 33px;
  }

  .multiselect__tags {
    padding: 7px 22px 7px 12px;
  }

  .multiselect__single {
    width: 239px;
    height: 22px;
    overflow: hidden;
    text-align: left;
    text-overflow: ellipsis;
    font-size: 16px;
    font-family: inherit;
    padding: 2px 0 0 0;
    border-width: 4px 4px 0 4px;
    border-color: #000000 transparent;
    color: #556271;
    margin-top: 0px;
  }

  .multiselect__single > span {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .multiselect__select:before {
    border-width: 4px 4px 0 4px;
    top: 20px;
    border-color: #556271 transparent;
  }

  .multiselect__input {
    max-width: 239px;
    font-family: 'Open Sans', sans-serif;
    font-size: 16px;
    font-weight: 400;
    line-height: 21.79px;
    letter-spacing: -0.02em;
    color: #556271;
    padding-left: 0px;
  }

  .multiselect__content {
    padding: 7px;
  }

  .multiselect__element {
    display: inline-block;
  }

  .multiselect__element .multiselect__option {
    display: block;
    height: auto;
    margin: 0;
    padding: 0;
    width: auto;
  }

  .icon-select {
    width: 70px;
    height: 70px;
  }

  .icon-selected {
    width: 19px;
    height: 19px;
    margin-top: -4px;
  }

  .icon-squares {
    color: #788793;
    padding: 14px;
    text-align: center;
  }

  .multiselect__option--highlight {
    background: #eee;
  }

  .multiselect__option--selected {
    background: #3397e1;
    .icon-squares {
      color: white;
    }
  }

  .multiselect__input::placeholder {
    color: #788793;
    opacity: 0.5;
  }

  .multiselect__placeholder {
    color: #212529;
    font-size: 16px;
    margin-top: 0px;
    margin-left: 5px;
    padding: 0;
  }

  .multiselect-no-result {
    line-height: 1.5rem;
  }
}
</style>
