<template>
  <div class="color-scheme-selector" :class="selectorSize">
    <multiselect
      v-if="!single"
      v-model="scheme"
      track-by="name"
      label="name"
      :show-labels="false"
      :placeholder="$t('Color Scheme')"
      :options="schemes"
      :multiple="false"
      :searchable="true"
      :internal-search="true"
      :allow-empty="false"
      >
      <template slot="singleLabel" slot-scope="props">
        <div class="scheme-name text-dark">{{ props.option.name }}</div>
        <div class="color-squares color-squares-single float-right">
          <div class="color-square" v-for="color in props.option.colors" :style="{backgroundColor: color}"></div>
        </div>
      </template>
      <template slot="option" slot-scope="props">
        <div class="scheme-name text-dark">{{ props.option.name }}</div>
        <div class="color-squares color-squares-list float-right">
          <div class="color-square" v-for="color in props.option.colors" :style="{backgroundColor: color}"></div>
        </div>
      </template>
    </multiselect>
    <div v-else>
      <div class="color-circles">
        <button v-for="(color, index) in singleColors" :key="index" type="button" class="d-inline-flex color-circle justify-content-center align-items-center" :class="{selected: singleColorIsSelected(color)}" @click="onClick(color)">
          <div class="color-circle-inner" :style="{backgroundColor: displayColor(color)}"></div>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import 'chartjs-plugin-colorschemes';

export default {
  props: {
    value: {
      type: Object,
      default: null
    },
    single: {
      type: Boolean,
      default: false
    },
    size: {
      type: String,
      default: 'lg'
    }
  },
  data() {
    return {
      scheme: null,
      schemes: [],
      singleColors: [],
    };
  },
  beforeMount() {
    this.reset();
  },
  computed: {
    selectorSize() {
      return `size-${this.size}`;
    }  
  },
  watch: {
    scheme(value) {
      this.$emit('input', this.scheme);
    },
    single(value) {
      this.reset();
    }
  },
  methods: {
    displayColor(color) {
      if (color == '#fff') {
        return '#dadada';
      }
      return color;
    },
    reset() {
      this.schemes = this.parseColorSchemes();
      this.setDefaultSingleColors();
      this.setDefaultScheme();
    },
    setDefaultScheme() {
      let scheme;

      if (this.single) {
        if (this.value) {
          if (this.value.name.includes('Single Color')) {
            scheme = this.setSingleColor(this.value.colors[0]);
          }
        }

        if (! scheme) {
          scheme = this.setSingleColor(this.singleColors[0]);
        }
      }
      
      if (! scheme) {
        if (this.value) {
          let schemeId = this.schemes.findIndex(element => element.name == this.value.name);
          if (schemeId !== null) {
            scheme = this.schemes[schemeId];
          }
        }
      }

      if (! scheme) {
        scheme = this.schemes[40];
      }

      return this.scheme = scheme;
    },
    setDefaultSingleColors() {
      let colors = JSON.parse(JSON.stringify(this.schemes[40].colors));
      colors.push('#333', '#888', '#fff');
      this.singleColors = colors;
    },
    parseColorSchemes() {
      let list = [];
      let names = [];
      let primaryNames = [];
      let colors = Chart.colorschemes;
      let categories = Object.keys(colors);
      
      categories.forEach(category => {
        let schemes = colors[category];
        let schemeNames = Object.keys(schemes).reverse();
        schemeNames.forEach(name => {
          let primaryName = name.match(/\D+/)[0];
          if (! primaryNames.includes(primaryName)) {
            list.push({
              name: this.unStudlyCase(primaryName),
              colors: colors[category][name]
            });
            names.push(name);
            primaryNames.push(primaryName);
          }
        });
      })
      
      return list.sort((a, b) => {
        if (a.name > b.name) return 1;
        if (b.name > a.name) return -1;
        return 0;
      });
    },
    onClick(color) {
      this.scheme = this.setSingleColor(color);
    },
    setSingleColor(color) {
      return {
        name: `Single Color - ${color}`,
        colors: [color]
      };
    },
    singleColorIsSelected(color) {
      if (this.scheme && this.scheme.name && this.scheme.colors) {
        if (this.scheme.name.includes('Single Color') && this.scheme.colors[0] == color) {
          return true;
        }
      }

      return false;
    },
    unStudlyCase(string) {
      return string.replace(/([A-Z])/g, ' $1')
                   .replace(/^./, str => {
                     return str.toUpperCase();
                   })
                   .trim();
    }
  },
}
</script>

<style lang="scss" scoped>
.color-scheme-selector {
  &.size-sm {
    width: 160px;
  }
  
  &.size-md {
    width: 300px;
  }
}

.color-squares {
  margin-top: 1px;
}

.color-square {
  border: 1px solid black;
  display: inline-flex;
  height: 14px;
  margin-right: 2px;
  transition: background-color 0.1s ease;
  width: 14px;
}

.color-circles {
  margin-left: -5px;
}

.color-circle {
  border: 2px solid white;
  border-radius: 100%;
  height: 34px;
  margin-right: 0;
  padding: 0;
  width: 34px;

  &:active,
  &:focus {
    box-shadow: none !important;
    outline: 0 !important;
  }

  &:hover {
    border-color: #ddd;
  }

  &.selected {
    border-color: #3397E1;
  }

  .color-circle-inner {
    border: 3px solid white;
    border-radius: 100%;
    height: 28px;
    width: 28px;
  }
}

.scheme-name {
  display: inline-block;
}
</style>