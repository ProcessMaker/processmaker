<template>
  <div ref="colorpicker">
    <!--<input type="text" class="form-control" v-model="colorValue" @focus="showPicker()" @input="updateFromInput"/>-->
    <span class="input-group-addon">
      <span class="badge badge-pill" :style="background" @click="togglePicker()"> {{colorValue}}</span>
      <picker :value="colors" @input="updateFromPicker" v-if="displayPicker"></picker>
    </span>
  </div>
</template>

<script>

  import {Sketch} from 'vue-color';

  export default {
    components: {
      'picker': Sketch,
    },
    props: ['color'],
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
      background() {
        return 'background-color:' + this.colorValue;
      },
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
