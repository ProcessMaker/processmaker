<template>
  <div class="pm-filter-form">
    <b-form>
      <b-form-group>
        <b-button variant="light"
                  @click="onSortAscending"
                  :pressed.sync="toggleAsc"
                  squared>
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9.49996 13H7.49996C7.36735 13 7.24018 13.0527 7.14641 13.1464C7.05264 13.2402 6.99996 13.3674 6.99996 13.5V14.5C6.99996 14.6326 7.05264 14.7598 7.14641 14.8536C7.24018 14.9473 7.36735 15 7.49996 15H9.49996C9.63257 15 9.75975 14.9473 9.85351 14.8536C9.94728 14.7598 9.99996 14.6326 9.99996 14.5V13.5C9.99996 13.3674 9.94728 13.2402 9.85351 13.1464C9.75975 13.0527 9.63257 13 9.49996 13ZM0.499961 5H1.99996V14.5C1.99996 14.6326 2.05264 14.7598 2.14641 14.8536C2.24018 14.9473 2.36735 15 2.49996 15H3.49996C3.63257 15 3.75975 14.9473 3.85351 14.8536C3.94728 14.7598 3.99996 14.6326 3.99996 14.5V5H5.49996C5.94402 5 6.16809 4.46125 5.8534 4.14656L3.3534 1.14656C3.25964 1.05287 3.13251 1.00023 2.99996 1.00023C2.86741 1.00023 2.74028 1.05287 2.64652 1.14656L0.146524 4.14656C-0.167226 4.46063 0.0552737 5 0.499961 5ZM13.5 5H7.49996C7.36735 5 7.24018 5.05268 7.14641 5.14645C7.05264 5.24021 6.99996 5.36739 6.99996 5.5V6.5C6.99996 6.63261 7.05264 6.75979 7.14641 6.85355C7.24018 6.94732 7.36735 7 7.49996 7H13.5C13.6326 7 13.7597 6.94732 13.8535 6.85355C13.9473 6.75979 14 6.63261 14 6.5V5.5C14 5.36739 13.9473 5.24021 13.8535 5.14645C13.7597 5.05268 13.6326 5 13.5 5ZM11.5 9H7.49996C7.36735 9 7.24018 9.05268 7.14641 9.14645C7.05264 9.24021 6.99996 9.36739 6.99996 9.5V10.5C6.99996 10.6326 7.05264 10.7598 7.14641 10.8536C7.24018 10.9473 7.36735 11 7.49996 11H11.5C11.6326 11 11.7597 10.9473 11.8535 10.8536C11.9473 10.7598 12 10.6326 12 10.5V9.5C12 9.36739 11.9473 9.24021 11.8535 9.14645C11.7597 9.05268 11.6326 9 11.5 9ZM15.5 1H7.49996C7.36735 1 7.24018 1.05268 7.14641 1.14645C7.05264 1.24021 6.99996 1.36739 6.99996 1.5V2.5C6.99996 2.63261 7.05264 2.75979 7.14641 2.85355C7.24018 2.94732 7.36735 3 7.49996 3H15.5C15.6326 3 15.7597 2.94732 15.8535 2.85355C15.9473 2.75979 16 2.63261 16 2.5V1.5C16 1.36739 15.9473 1.24021 15.8535 1.14645C15.7597 1.05268 15.6326 1 15.5 1Z" fill="#6A7888"/>
          </svg>
          {{$t('Sort Ascending')}}
        </b-button>
        <b-button variant="light"
                  @click="onSortDescending"
                  :pressed.sync="toggleDesc"
                  squared>
          <svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M7.50002 2H9.50002C9.63263 2 9.75981 1.94732 9.85358 1.85355C9.94735 1.75979 10 1.63261 10 1.5V0.5C10 0.367392 9.94735 0.240215 9.85358 0.146447C9.75981 0.0526784 9.63263 0 9.50002 0H7.50002C7.36742 0 7.24024 0.0526784 7.14647 0.146447C7.0527 0.240215 7.00002 0.367392 7.00002 0.5V1.5C7.00002 1.63261 7.0527 1.75979 7.14647 1.85355C7.24024 1.94732 7.36742 2 7.50002 2ZM7.50002 6H11.5C11.6326 6 11.7598 5.94732 11.8536 5.85355C11.9473 5.75979 12 5.63261 12 5.5V4.5C12 4.36739 11.9473 4.24021 11.8536 4.14645C11.7598 4.05268 11.6326 4 11.5 4H7.50002C7.36742 4 7.24024 4.05268 7.14647 4.14645C7.0527 4.24021 7.00002 4.36739 7.00002 4.5V5.5C7.00002 5.63261 7.0527 5.75979 7.14647 5.85355C7.24024 5.94732 7.36742 6 7.50002 6ZM15.5 12H7.50002C7.36742 12 7.24024 12.0527 7.14647 12.1464C7.0527 12.2402 7.00002 12.3674 7.00002 12.5V13.5C7.00002 13.6326 7.0527 13.7598 7.14647 13.8536C7.24024 13.9473 7.36742 14 7.50002 14H15.5C15.6326 14 15.7598 13.9473 15.8536 13.8536C15.9473 13.7598 16 13.6326 16 13.5V12.5C16 12.3674 15.9473 12.2402 15.8536 12.1464C15.7598 12.0527 15.6326 12 15.5 12ZM7.50002 10H13.5C13.6326 10 13.7598 9.94732 13.8536 9.85355C13.9473 9.75979 14 9.63261 14 9.5V8.5C14 8.36739 13.9473 8.24021 13.8536 8.14645C13.7598 8.05268 13.6326 8 13.5 8H7.50002C7.36742 8 7.24024 8.05268 7.14647 8.14645C7.0527 8.24021 7.00002 8.36739 7.00002 8.5V9.5C7.00002 9.63261 7.0527 9.75979 7.14647 9.85355C7.24024 9.94732 7.36742 10 7.50002 10ZM5.50002 10H4.00002V0.5C4.00002 0.367392 3.94735 0.240215 3.85358 0.146447C3.75981 0.0526784 3.63263 0 3.50002 0H2.50002C2.36742 0 2.24024 0.0526784 2.14647 0.146447C2.0527 0.240215 2.00002 0.367392 2.00002 0.5V10H0.500024C0.0565866 10 -0.167788 10.5387 0.147212 10.8534L2.64721 13.8534C2.74097 13.9471 2.8681 13.9998 3.00065 13.9998C3.1332 13.9998 3.26033 13.9471 3.35409 13.8534L5.85409 10.8534C6.16721 10.5394 5.9444 10 5.50002 10Z" fill="#6A7888"/>
          </svg>
          {{$t('Sort Descending')}}
        </b-button>
      </b-form-group>

      <b-form-group>
        {{$t('Filter the column')}}:
      </b-form-group>

      <template v-for="(item, index) in items">
        <b-form-group :key="'operator' + index">
          <b-form-select v-model="item.operator" 
                         :options="getOperators()"
                         @change="onChangeOperator(item,index)">
          </b-form-select>
        </b-form-group>

        <b-form-group :key="'subject' + index" v-show="item._showSubject">
          <b-form-input v-model="item.subject" 
                        placeholder="Type information">
          </b-form-input>
        </b-form-group>

        <b-form-group :key="'logicalOp' + index" v-show="item._showLogicalOp">
          <b-form-select v-model="item.logicalOp" 
                         :options="getLogicalOps()"
                         class="pm-filter-form-logical-operators"
                         @change="onChangeLogicalOp(item,index)">
          </b-form-select>
        </b-form-group>
      </template>

      <b-button variant="outline-secondary"
                @click="onCancel">
        {{$t('Cancel')}}
      </b-button>
      <span>&nbsp;</span>
      <b-button @click="onApply">
        {{$t('Apply')}}
      </b-button>
    </b-form>

  </div>
</template>

<script>
  export default {
    components: {
    },
    data() {
      return {
        toggleAsc: true,
        toggleDesc: false,
        items: []
      };
    },
    created() {
    },
    mounted() {
      this.addItem(0);
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
        this.$emit('onApply', this.items);
      },
      onCancel() {
        this.$emit('onCancel');
        this.items = [];
        this.addItem(0);
      },
      onChangeOperator(item, index) {
        if (item.operator === '') {
          item.subject = '';
          item.logicalOp = '';
          this.onChangeLogicalOp(item, index);
        }
        //let sw = item.operator !== '';
        //item._showSubject = sw;
        //item._showLogicalOp = sw;
      },
      onChangeLogicalOp(item, index) {
        let next = index + 1;
        if (item._addedCondition === false) {
          item._addedCondition = true;
          this.addItem(next);
        }
        if (item.logicalOp === '') {
          item.logicalOp = this.items[next].logicalOp;
          item._addedCondition = item.logicalOp !== '';
          this.removeItem(next);
        }
      },
      addItem(index) {
        let item = {
          operator: '',
          subject: '',
          logicalOp: '',
          _showSubject: true,
          _showLogicalOp: true,
          _addedCondition: false
        };
        this.items.splice(index, 0, item);
      },
      removeItem(index) {
        this.items.splice(index, 1);
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
    width: 350px;
  }
  .pm-filter-form-logical-operators{
    width: 160px;
  }
</style>