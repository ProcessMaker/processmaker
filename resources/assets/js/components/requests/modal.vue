<template>
    <div>
        <button id="navbar-request-button" class="btn btn-success  btn-sm" @click="toggleRequestModal"><i class="fas fa-plus"></i> Request</button>
        <div class="arrow" :class="{show: show}" :style="arrowStyle"></div>
        <div id="requests-modal" class="requests-modal" :class="{show: show}">
            <div class="header"> 
                <div class="title">
                We've made it easy for you to make the following requests
                </div>
                <div class="search">
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text"><i class="fas fa-search"></i></div>
                        </div>
                        <input class="form-control form-control-sm" v-model="filter" placeholder="Search...">
                      </div>
                </div>
                <div class="actions">
                    <i class="fas fa-times" @click="toggleRequestModal"></i>
                </div>
            </div>
            <div class="header-bar"></div>
            <div class="process-list">
                <div class="category" v-for="(processList, index) in processes" :key="index">
                    <h3 class="name">{{index}}</h3>
                    <div class="processes">
                        <process-card v-for="(process,index) in processList" :filter="filter" :key="index" :title="process.title" :description="process.description"></process-card>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import card from "./card";
export default {
  components: {
    "process-card": card
  },
  data() {
    return {
      show: false,
      filter: "",
      arrowStyle: {
        top: "0px",
        left: "0px"
      },
      processes: {
          'Test': [
              {
                  title: 'One process',
                  description: 'This is a sample description',
                  url: '/'
              }

          ],
          'Another': [
              {
                  title: 'two process',
                  description: 'This is a sample description',
                  url: '/'
              },
              {
                  title: 'three process',
                  description: 'This is a sample description',
                  url: '/'
              }
          ],
          'Yet Another': [
              {
                  title: 'four process',
                  description: 'This is a sample description',
                  url: '/'
              }
 
          ]
      }

    };
  },
  methods: {
    toggleRequestModal() {
      this.show = !this.show;
    }
  },
  mounted() {
    this.arrowStyle.top = $("#navbar-request-button").offset().top + 45 + "px";
    this.arrowStyle.left =
      $("#navbar-request-button").offset().left + 32 + "px";

    window.addEventListener("resize", () => {
      this.arrowStyle.top =
        $("#navbar-request-button").offset().top + 42 + "px";
      this.arrowStyle.left =
        $("#navbar-request-button").offset().left + 32 + "px";
    });
  }
};
</script>

<style lang="scss" scoped>
.arrow {
  transform: rotate(45deg);
  width: 25px;
  height: 25px;
  border: 1px solid rgba(0, 0, 0, 0.1);
  position: fixed;
  top: 0px;
  right: 50px;
  background-color: white;
  display: none;

  &.show {
    display: block;
  }
}

.requests-modal {
  // Do not display by default
  display: none;
  position: fixed;
  border-radius: 2px;
  background-color: #ffffff;
  box-shadow: 0 4px 18px 0 rgba(0, 0, 0, 0.1);
  z-index: 90000;
  height: 70%;
  width: calc(100% - 160px);
  position: fixed;
  top: 70px;
  left: 50%;
  transform: translateX(-50%);

  .header {
    min-height: 74px;
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: center;

    .title {
      flex-grow: 1;
      font-size: 16px;
      font-weight: normal;
      font-style: normal;
      font-stretch: normal;
      line-height: normal;
      letter-spacing: normal;
      color: #333333;
      vertical-align: middle;
      padding-left: 60px;
    }

    .search {
      padding: 0px 32px;
    }

    .actions {
      padding-right: 32px;
      font-size: 19px;
    }
  }

  .header-bar {
    width: 100%;
    height: 3px;
    opacity: 0.05;
    background-image: linear-gradient(to bottom, rgba(0, 0, 0, 0), #000000);
  }

  .process-list {
    //flex-grow: 1;
    overflow: auto;
    padding: 32px 60px;

    .category {
      padding-bottom: 32px;

      .name {
        font-size: 16px;
        font-weight: bold;
        font-style: normal;
        font-stretch: normal;
        line-height: normal;
        letter-spacing: normal;
        color: #788793;
      }
    }

    .processes {
      display: flex;
      flex-flow: row wrap;
    }
  }

  &.show {
    display: flex;
    flex-direction: column;
  }
}
</style>


