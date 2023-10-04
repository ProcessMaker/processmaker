@extends('layouts.layout')

@section('title')
    {{ __('Customize UI') }}
@endsection

@section('sidebar')
    @include('layouts.sidebar', ['sidebar'=> Menu::get('sidebar_admin')])
@endsection

@section('breadcrumbs')
    @include('shared.breadcrumbs', ['routes' => [
        __('Admin') => route('admin.index'),
        __('Customize UI') => null,
    ], 'dynamic' => true])
@endsection
@section('content')
    <div class="container-fluid px-3" id="editCss" v-cloak>
        <div class="row" role="document">
            <div class="col">
                <b-nav tabs v-if="showTabs">
                    <b-nav-item
                      v-for="tab in tabs"
                      :href="tab.href"
                      :active="isActive(tab)"
                      @click.prevent="routeTo(tab)"
                    >@{{ tab.name }}</b-nav-item>
                </b-nav>
                <b-card :class="cardClass" body-class="p-3 border-top-0">
                    <component :is="this.tabs[currentTab].component"></component>
                </b-card>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @vite('resources/js/admin/cssOverride/edit.js')
    <script>
      const config = @json($config);
      const loginFooterSetting = @json($loginFooter);
      const altTextSetting = @json($altText);
      const route = @json($tab);

      new Vue({
        el: '#editCss',
        data() {
          return {
            route: null,
            tabs: [
                { name: this.$t('Site Design'), route: 'design', component: 'site-design', href: '/admin/customize-ui' },
            ],
          }
        },
        computed: {
          currentTab() {
            const tab = this.tabs.findIndex((tab) => tab.route == this.route);
            if (tab > -1) {
              return tab;
            } else {
              return 0;
            }
          },
          showTabs() {
            return this.tabs.length > 1;
          },
          cardClass() {
            if (this.showTabs) {
              return 'border-top-0';
            } else {
              return null;
            }
          }
        },
        methods: {
          isActive(tab) {
            return this.route == tab.route;
          },
          routeTo(tab) {
            history.pushState(null, null, tab.href);
            this.route = tab.route;
            this.setBreadcrumbs(tab);
          },
          setBreadcrumbs(tab) {
            if (this.showTabs) {
              window.ProcessMaker.navbar.setRoutes([
                {
                  title: 'Admin',
                  link: '{{ route('admin.index') }}',
                },
                {
                  title: 'Customize UI',
                  link: '{{ route('customize-ui.edit') }}',
                },
                {
                  title: tab.name
                }
              ]);
            }
          }
        },
        created() {
          this.route = route;
        },
        mounted() {
          if (window.ProcessMaker.cssOverrideTabs && window.ProcessMaker.cssOverrideTabs.length) {
            this.tabs = this.tabs.concat(window.ProcessMaker.cssOverrideTabs);
          }
          this.setBreadcrumbs(this.tabs[this.currentTab]);
        },
      });
    </script>

    <style lang="scss" scoped>
        .icon-container {
            display: inline-block;
            width: 8em;
            margin-bottom: 1em;

        .i {
            color: #96b0aa;
            font-size: 5em;
        }

        .svg {
            fill: #aeb5bb;
        }
        
        }
        
        .vc-sketch {
            position: absolute;
            z-index: 100;
        }
    </style>
@endsection

