import {get, debounce} from 'lodash';

export default {
  props: {
    value: null,
    placeholder: String,
    trackBy: {
      type: String,
      default: 'id'
    },
    label: {
      type: String,
      default: 'name'
    },
    api: {
      type: String,
      default: 'process'
    },
    multiple: {
      type: Boolean,
      default: false
    },
    storeId: {
      type: Boolean,
      default: true
    }
  },
  data () {
    return {
      pmql: null,
      options: [],
      selectedOption: null
    };
  },
  watch: {
    value: {
      immediate: true,
      handler (value) {
        this.selectedOption = this.storeId
          ? this.options.find(option => get(option, this.trackBy) == value)
          : value;
        return value && !this.selectedOption ? this.loadSelected(value) : null;
      }
    }
  },
  methods: {
    change (value) {
      this.$emit('input', this.storeId ? get(value, this.trackBy) : value);
    },
    loadOptions (filter) {
      window.ProcessMaker.apiClient
        .get(this.api, {params: {filter, pmql: this.pmql}})
        .then((response) => {
          this.options = response.data.data || [];
        });
    },
    loadSelected (value) {
      window.ProcessMaker.apiClient
        .get(`${this.api}/${value}`)
        .then((response) => {
          this.selectedOption = response.data;
        });
    },
    loadOptionsDebounced () {}
  },
  mounted () {
    this.loadOptionsDebounced = debounce(this.loadOptions, 1000);
  }
};
