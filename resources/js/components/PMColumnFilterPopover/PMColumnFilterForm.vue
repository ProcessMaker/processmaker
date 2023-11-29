<template>
  <div class="pm-filter-form">
    <b-form>
      <b-form-group>
        <b-button variant="light"
                  @click="onSortAscending"
                  :pressed.sync="toggleAsc"
                  squared
                  size="sm">
          <PMColumnFilterIconAsc></PMColumnFilterIconAsc>
          {{$t('Sort Ascending')}}
        </b-button>
        <b-button variant="light"
                  @click="onSortDescending"
                  :pressed.sync="toggleDesc"
                  squared
                  size="sm">
          <PMColumnFilterIconDesc></PMColumnFilterIconDesc>
          {{$t('Sort Descending')}}
        </b-button>
      </b-form-group>

      <div class="pm-filter-form-area" ref="pmFilterFormArea">
        <template v-for="(item, index) in items">
          <b-form-group :key="'buttonRemove' + index">
            <div class="d-flex justify-content-between align-items-center">
              <p class="mb-0">{{$t('Filter the column')}}:</p>
              <b-button variant="light"
                        size="sm"
                        class="pm-filter-form-button"
                        @click="onClickButtonRemove(item,index)">
                <PMColumnFilterIconMinus></PMColumnFilterIconMinus>
              </b-button>
            </div>
          </b-form-group>

          <b-form-group :key="'operator' + index">
            <b-form-select v-model="item.operator" 
                           :options="getOperators()"
                           @change="onChangeOperator(item,index)"
                           size="sm">
            </b-form-select>
          </b-form-group>

          <b-form-group :key="'subject' + index">
            <b-form-input v-model="item.subject" 
                          placeholder="Type information"
                          size="sm">
            </b-form-input>
          </b-form-group>

          <b-form-group :key="'logicalOp' + index">
            <b-form-select v-model="item.logicalOp" 
                           :options="getLogicalOps()"
                           class="pm-filter-form-logical-operators"
                           @change="onChangeLogicalOp(item,index)"
                           size="sm">
            </b-form-select>
          </b-form-group>
        </template>
      </div>

      <b-form-group>
        <b-button variant="light"
                  size="sm"
                  class="pm-filter-form-button"
                  @click="onClickButtonAdd">
          <PMColumnFilterIconPlus></PMColumnFilterIconPlus>
        </b-button>
      </b-form-group>

      <b-form-group>
        <b-button variant="outline-secondary"
                  size="sm"
                  @click="onCancel">
          {{$t('Cancel')}}
        </b-button>
        <span>&nbsp;</span>
        <b-button size="sm" 
                  @click="onApply">
          {{$t('Apply')}}
        </b-button>  
      </b-form-group>
    </b-form>

  </div>
</template>

<script>
  import PMColumnFilterIconAsc from './PMColumnFilterIconAsc'
  import PMColumnFilterIconDesc from './PMColumnFilterIconDesc'
  import PMColumnFilterIconMinus from './PMColumnFilterIconMinus'
  import PMColumnFilterIconPlus from './PMColumnFilterIconPlus'

  export default {
    components: {
      PMColumnFilterIconAsc,
      PMColumnFilterIconDesc,
      PMColumnFilterIconMinus,
      PMColumnFilterIconPlus
    },
    data() {
      return {
        toggleAsc: true,
        toggleDesc: false,
        items: [],
        itemsChanged: false
      };
    },
    created() {
    },
    mounted() {
      this.addItem(0);
    },
    updated() {
      this.updatedScroll();
    },
    watch: {
      items() {
        this.itemsChanged = true;
      }
    },
    methods: {
      onSortAscending() {
        this.toggleDesc = false;
        this.$emit('onSortAscending', 'asc');
      },
      onSortDescending() {
        this.toggleAsc = false;
        this.$emit('onSortDescending', 'desc');
      },
      onApply() {
        let json = JSON.parse(JSON.stringify(this.items));
        this.$emit('onApply', json);
      },
      onCancel() {
        this.$emit('onCancel');
        this.items = [];
        this.addItem(0);
      },
      onClickButtonAdd() {
        this.addItem(this.items.length);
      },
      onClickButtonRemove(item, index) {
        if (this.items.length === 1) {
          return;
        }
        this.removeItem(index);
      },
      onChangeOperator() {
      },
      onChangeLogicalOp() {
      },
      addItem(index) {
        let item = {
          operator: '',
          subject: '',
          logicalOp: ''
        };
        this.items.splice(index, 0, item);
      },
      removeItem(index) {
        this.items.splice(index, 1);
      },
      updatedScroll() {
        if (this.itemsChanged === true) {
          let myDiv = this.$refs.pmFilterFormArea;
          myDiv.scrollTop = 185 * (this.items.length);
          this.itemsChanged = false;
        }
      },
      getOperators() {
        return [
          {value: '', text: 'Select an option'},
          {value: 'contains', text: 'Contains'},
          {value: 'matches', text: 'Matches'},
          {value: 'is', text: 'Is'},
          {value: 'isNot', text: 'Is Not'}
        ];
      },
      getLogicalOps() {
        return [
          {value: '', text: 'Select an option'},
          {value: 'and', text: 'And'},
          {value: 'or', text: 'Or'}
        ];
      }
    }
  };
</script>

<style scoped>
  .btn{
    text-transform: none;
  }
  .pm-filter-form{
    width: 285px;
  }
  .pm-filter-form-area{
    overflow-y: scroll;
    height: 185px;
  }
  .pm-filter-form-logical-operators{
    width: 160px;
  }
  .pm-filter-form-button{
    padding: 0px;
  }
</style>