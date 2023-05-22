<template>
  <div ref="colorpicker">
    <button type="button" class="badge badge-pill border-0 outline-0" :style="style" @click="togglePicker()" v-uni-id="'picker-button'"> {{colorValue}}</button>
    <label class="pl-2" v-uni-for="'picker-button'">{{ title }}</label>
    <picker :value="colors" @input="updateFromPicker" v-if="displayPicker"></picker>
  </div>
</template>

<script>

  import {Sketch} from 'vue-color';
  import { createUniqIdsMixin } from "vue-uniq-ids";
  const uniqIdsMixin = createUniqIdsMixin();
  const tinycolor = require("tinycolor2");

  export default {
    components: {
      'picker': Sketch,
    },
    mixins: [uniqIdsMixin],
    props: ['color', 'title'],
    data() {
      return {
        colors: {
          hex: '#000000',
        },
        colorValue: '',
        displayPicker: false,
      }
    },
    computed: {
      style() {
        let styles = [];
        styles.push('background-color: ' + this.colorValue);
        
        if (tinycolor(this.colorValue).getBrightness() < 160) {
          styles.push('color: white');
        } else {
          styles.push('color: black');
        }
        
        return styles.join('; ');
      }
    },
    mounted() {
      this.setColor(this.color || '#000000');
    },
    methods: {
      setColor(color) {
        this.updateColors(color);
        this.colorValue = color;
      },
      updateColors(color) {
        if (color.slice(0, 1) == '#') {
          this.colors = {
            hex: color
          };
        } else if (color.slice(0, 4) == 'rgba') {
          var rgba = color.replace(/^rgba?\(|\s+|\)$/g, '').split(','),
            hex = '#' + ((1 << 24) + (parseInt(rgba[0]) << 16) + (parseInt(rgba[1]) << 8) + parseInt(rgba[2])).toString(16).slice(1);
          this.colors = {
            hex: hex,
            a: rgba[3],
          }
        }
      },
      showPicker() {
        document.addEventListener('click', this.documentClick);
        this.displayPicker = true;
      },
      hidePicker() {
        document.removeEventListener('click', this.documentClick);
        this.displayPicker = false;
      },
      togglePicker() {
        this.displayPicker ? this.hidePicker() : this.showPicker();
      },
      updateFromInput() {
        this.updateColors(this.colorValue);
      },
      updateFromPicker(color) {
        this.colors = color;
        if (color.rgba.a == 1) {
          this.colorValue = color.hex;
        } else {
          this.colorValue = 'rgba(' + color.rgba.r + ', ' + color.rgba.g + ', ' + color.rgba.b + ', ' + color.rgba.a + ')';
        }
      },
      documentClick(e) {
        var el = this.$refs.colorpicker,
          target = e.target;
        if (el !== target && !el.contains(target)) {
          this.hidePicker()
        }
      }
    },
    watch: {
      colorValue(val) {
        if (val) {
          this.updateColors(val);
          this.$emit('input', val);
          //document.body.style.background = val;
        }
      }
    }
  };
</script>
