<template>
  <div class="multiselect-icons">
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
          <span v-if="props.option.value">
            <img
              class="icon-selected"
              :src="`/img/launchpad-images/icons/${props.option.value}.svg`"
              :alt="props.option.value"
            >
            {{ props.option.label }}
          </span>
          <span v-else>
            {{ placeholder }}
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
import Icons from "./Icons";

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
      placeholder: this.$t("Icon"),
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
    this.icon = this.value ? this.find(this.value) : this.find(this.default);
  },
  methods: {
    onSearch(query) {
      this.query = query.toLowerCase();
      if (this.query.length) {
        this.list = this.all.filter((icon) =>
          icon.label.toLowerCase().includes(this.query)
        );
      } else {
        this.list = this.all;
      }
    },
    onOpen() {
      this.$refs.multiselect.search = this.query;
    },
    onClose() {
      this.placeholder = this.$t("Icon");
    },
    find(value) {
      return this.all.find((icon) => icon.value == value);
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
$multiselect-height: 38px;

.multiselect-icons {
  .input-group {
    width: 100%;
  }

  .multiselect,
  .multiselect__tags {
    height: 30px;
    min-height: 30px;
    max-height: 30px;
    border-radius: 4px;
    border-color: #6c757d;
  }

  .multiselect__Select {
    width: 28px;
    height: 34px;
  }

  .multiselect__tags {
    padding: 3px 22px 3px 3px;
  }

  .multiselect__single {
    font-size: 15px;
    font-family: inherit;
  }

  .multiselect__select:before {
    border-width: 3px 3px 0 3px;
    right: -8px;
    top: 50%;
    border-color: #000000 transparent;
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
    margin-top: -4px;
    padding: 0;
  }

  .multiselect-no-result {
    line-height: 1.5rem;
  }
}
</style>
