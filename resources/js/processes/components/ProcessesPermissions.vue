<template>
  <div class="reassignment-user-selector-container p-0">
    <b-form-group v-if="showUserSelector"
                  class="floating w-100 reassignment-user-selector-search">
      <b-dropdown id="reassignmentUserSelector"
                  size="sm"
                  variant="light"
                  split-variant="light"
                  split
                  class="reassignment-user-selector-border rounded-sm">
        <template v-slot:button-content>
          <div class="d-flex align-items-center">
            <i class="fa fa-search pmql-icons"></i>
            <b-form-input v-model="selectedText"
                          :placeholder="selectedOption ? selectedOption.text : $t('Type here to search users')"
                          size="sm"
                          class="reassignment-user-selector-input"
                          autocomplete="off"
                          id="reassignmentUserSelectorInput"
                          @input="onInput"
                          @click="showMenu(toggleShowMenu=!toggleShowMenu)">
            </b-form-input>
          </div>
        </template>
        <b-dropdown-item v-for="option in users"
                         :key="option.value" 
                         :value="option"
                         @click="onSelect(option)">
          {{ option.text }}
        </b-dropdown-item>
      </b-dropdown>
    </b-form-group>
    <div class="reassignment-user-selector-body">
      <div v-for="(item, index) in items"
           class="d-flex justify-content-between">
        <div>
          {{item.fullname}}
        </div>
        <div>
          <b-button size="sm"
                    variant="outline-light"
                    class="p-0"
                    @click="confirmDelete(item.id)"
                    pill>
            <img src="/img/button-small-trash.svg" :alt="$t('Remove')"/>
          </b-button>
        </div>
      </div>  
    </div>

    <b-modal ref="deleteModal"
             :title="$t('Confirm Deletion')"
             :ok-title="$t('Remove')"
             :cancel-title="$t('Cancel')"
             ok-variant="danger"
             @ok="remove(itemToRemove)"
             centered
             >
      <p>{{ $t('Are you sure you want to delete this reassignment?') }}</p>
    </b-modal>
  </div>
</template>

<script>
  import PMFormSelectSuggest from "../../components/PMFormSelectSuggest.vue";
  export default {
    components: {
      PMFormSelectSuggest
    },
    props: {
      reassignments: {
        type: Array,
        default: () => []
      }
    },
    data() {
      return {
        showUserSelector: false,
        users: [],
        items: [],
        selectedOption: null,
        selectedText: "",
        toggleShowMenu: false
      };
    },
    mounted() {
      this.requestUser("");
      document.addEventListener("click", (event) => {
        if (event.target.id !== "reassignmentUserSelectorInput" &&
                event.target.parentNode.id !== "buttonReassignmentClicked") {
          this.showUserSelector = false;
        }
      });
    },
    watch: {
      reassignments() {
        this.items = this.reassignments;
      },
      users() {
        this.showMessageEmpty = this.users.length <= 0;
      },
      selectedOption() {
        this.selectedText = this.selectedOption?.text;
      }
    },
    methods: {
      add() {
        this.showUserSelector = !this.showUserSelector;
        if (this.showUserSelector) {
          this.requestUser("");
          this.$nextTick(() => {
            this.showMenu(true);
          });
        }
      },
      confirmDelete(id) {
        this.itemToRemove = id;
        this.$refs.deleteModal.show();
      },
      remove(id) {
        this.items = this.items.filter(item => item.id !== id);
      },
      requestUser(filter) {
        let url = "users" +
                "?page=1" +
                "&per_page=30" +
                "&filter=" + filter +
                "&order_by=firstname" +
                "&order_direction=asc";
        ProcessMaker.apiClient.get(url)
                .then(response => {
                  this.users = [];
                  for (let i in response.data.data) {
                    this.users.push({
                      text: response.data.data[i].fullname,
                      value: response.data.data[i].id
                    });
                  }
                });
      },
      onInput(value) {
        this.requestUser(value);
        this.showMenu(true);
      },
      showMenu(sw) {
        let button = document.getElementById("reassignmentUserSelector");
        if (!button) {
          return;
        }
        let obj = button.querySelector(".dropdown-menu").classList;
        if (sw === true) {
          obj.add("reassignment-user-selector-show");
        } else {
          obj.remove("reassignment-user-selector-show");
        }
      },
      onSelect(option) {
        this.selectedOption = option;
        this.showMenu(false);

        this.showUserSelector = false;
        this.onChangeUserId();
      },
      onChangeUserId() {
        if (this.selectedOption) {
          let user = {
            id: this.selectedOption.value,
            fullname: this.selectedOption.text
          };
          this.selectedOption = null;
          this.selectedText = "";

          if (this.items.find(item => item.id === user.id)) {
            ProcessMaker.alert(this.$t("This has already been assigned."), "info");
            return;
          }
          this.items.push(user);
        }
      },
      getItems() {
        return this.items;
      },
      handleClose() {
        this.showUserSelector = false;
      }
    }
  }
</script>

<style>
  .reassignment-user-selector-show {
    display: block;
    position: absolute;
    transform: translate3d(-1px, 30px, 0px);
    top: 0px;
    left: 0px;
    will-change: transform;
    margin-top: 6px;
  }
  .reassignment-user-selector-border .dropdown-menu {
    width: 100%;
    border: none !important;
    max-height: 250px;
    overflow-y: auto;
  }
  .reassignment-user-selector-border > button {
    background-color: white !important;
  }
</style>
<style scoped>
  .reassignment-user-selector-search {
    padding: 8px;
    border: 1px solid rgba(0, 0, 0, 0.125);
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.125);
    height: 300px;
  }
  .reassignment-user-selector-border {
    border: 1px solid #CDDDEE;
    box-shadow: 0 0 5px #CDDDEE;
    width: 100%;
  }
  .reassignment-user-selector-border > :first-child {
    width: 100%;
  }
  .reassignment-user-selector-input {
    border: 1px solid white;
    padding-top: 0px;
    padding-bottom: 0px;
    height: auto;
    width: 100%;
  }
  .reassignment-user-selector-input:focus {
    box-shadow: none !important;
    border: 1px solid white !important;
  }

  .reassignment-user-selector-container {
    position: relative;
  }

  .floating {
    position: absolute;
    top: 0;
    left: 0;
    background-color: white;
    z-index: 1;
  }

  .reassignment-user-selector-body {
    border: 1px solid var(--tabs-scroll-bg);
    border-radius: 5px;
    padding: 12px;
  }
  .reassignment-user-selector-body > div {
    padding-top: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--tabs-scroll-bg);
  }
  .reassignment-user-selector-body > div:first-child {
    padding-top: 0;
  }
  .reassignment-user-selector-body > div:last-child {
    padding-bottom: 0;
    border-bottom: 0;
  }
</style>