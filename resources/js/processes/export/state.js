import DataProvider from "./DataProvider";

const ioState = [];

export default {
  data() {
    return {
      ioState,
      manifest: {},
      rootUuid: '',
      rootAsset: {},
      groups: [],
      isImport: false,
      importMode: 'update',
      file: null,
      password: '',
    }
  },
  watch: {
    ioState: {
      deep: true,
      handler() {
        // console.log("ioState", JSON.parse(JSON.stringify(this.ioState)));
      }
    }
  },
  methods: {
    setInitialState(assets, rootUuid) {
      this.manifest = assets;
      this.rootUuid = rootUuid;

      // init
      this.ioState = Object.fromEntries(
        Object.entries(assets)
          .map(([uuid, asset]) => {
            return [uuid, { mode: this.defaultMode, discardedByParent: false }];
          })
      );
    },
    // Hide the asset from UI if its parent is was discarded AND the
    // asset is not used by any other non-discarded asset.
    updateDiscardedByParent() {

      // First, we set all discardedByParent to ture.
      Object.keys(this.ioState).forEach((uuid) => {
        this.$set(this.ioState[uuid], 'discardedByParent', true);
      });
      
      const maxDepth = 20;
      const setMode = (uuid, discardedByParent, depth = 0) => {
        if (depth > maxDepth) {
          throw new Error('Max depth reached');
        }

        const asset = this.manifest[uuid];
        if (!asset) {
          // Dependent was not included in the initial payload because it was
          // marked as hidden or explicitly discarded by the backend.
          return;
        }

        let mode = this.defaultMode;
        
        mode = this.ioState[uuid].mode;
        this.$set(this.ioState[uuid], 'discardedByParent', discardedByParent);

        // If this asset's mode is 'discard', set all it's children's discardedByParent to true.
        // Additionally, if this this asset's parent was discarded, set our children to
        // discardedByParent = true
        const setChildrenDiscardedByParent = mode === 'discard' || discardedByParent;

        asset.dependents.forEach((dependent) => {
          const depUuid = dependent.uuid;
          setMode(depUuid, setChildrenDiscardedByParent, depth + 1);
        });

      };
      setMode(this.rootUuid, false);
    },
    setForGroup(group, value) {
      const mode = value ? this.defaultMode : 'discard';
      this.setModeForGroup(group, mode);
    },
    setModeForGroup(group, mode) {
      Object.entries(this.manifest).filter(([uuid, asset]) => {
        return asset.type === group;
      }).forEach(([uuid, _]) => {
        this.set(uuid, mode);
      });
      this.updateDiscardedByParent();
    },
    set(uuid, mode, discardedByParent = false) {

      const setting = this.ioState[uuid];
      if (!setting) {
        console.log(uuid + " not found in ioState");
        return;
      }

      setting.mode = mode;
      setting.discardedByParent = discardedByParent;
      this.$set(this.ioState, uuid, setting);

    },
    updatableSetting([uuid, setting]) {
      if (uuid === this.rootUuid) {
        return false;
      }
      return true;
    },
    // used for for export
    setIncludeAll(value) {
      let set = 'discard';
      if (value) {
        set = this.defaultMode;
      }
      this.setModeForAll(set, false);
    },
    // used for for import
    setModeForAll(mode, includeRoot = true) {
      Object.entries(this.ioState).filter(([uuid, settings]) => {
        return settings.mode !== 'discard' && !settings.discardedByParent;
      }).forEach(([uuid, asset]) => {
        if (uuid === this.rootUuid && !includeRoot) {
          return;
        }
        this.set(uuid, mode);
      });
    },
    debug(obj) {
      return JSON.parse(JSON.stringify(this.ioState));
    },
    exportOptions() {
      const rootUuid = this.rootUuid;
      const options = {};
      return Object.assign(options, this.ioState);
    },
    includeByGroup(method) {
      const res = Object.fromEntries(
        Object.entries(this.uuidsByGroup).map(([group, uuids]) => {
          const groupSettings = Object.entries(this.ioState)
            .filter(this.updatableSetting)
            .filter(([uuid, setting]) => {
              return uuids.includes(uuid)
            });

          const fn = ([uuid, setting]) => {
            return setting.mode !== 'discard' && !setting.discardedByParent;
          }

          let result = false;
          if (method === 'every') {
            result = groupSettings.every(fn);
          } else {
            result = groupSettings.some(fn);
          }

          return [group, result];
        })
      );
      return res;
    },
    hasSomeNotDiscardedByParent(items) {
      return items.some(item => {
          return !this.ioState[item.uuid].discardedByParent;
      });
    },
    getManifest(processId) {
      DataProvider.getManifest(processId)
        .then((response) => {
          this.rootAsset = response.root;
          this.groups = response.groups;
          this.setInitialState(response.assets, response.rootUuid);
        })
        .catch((error) => {
          console.log(error);
          ProcessMaker.alert(error, "danger");
        });
    },
  },
  computed: {
    canExport() {
      return this.rootUuid && this.rootUuid !== '';
    },
    defaultMode() {
      // return this.isImport ? 'update' : null;
      return 'update';
    },
    operation() {
      if (this.isImport) {
        return "Import";
      }
      return "Export";
    },
    includeAll() {
      const result = Object.entries(this.ioState).filter(this.updatableSetting).every(([uuid, setting]) => {
        // const asset = this.manifest[uuid];
        return setting.mode === this.defaultMode
      });
      return result;
    },
    uuidsByGroup() {
      return Object.entries(this.manifest).reduce((groups, [uuid, asset]) => {
        const group = (groups[asset.type] || []);
        group.push(uuid);
        groups[asset.type] = group;
        return groups;
      }, {});
    },
    includeAllByGroup() {
      const r = this.includeByGroup('every');
      return r;
    },
    groupsHaveSomeActive() {
      const r = this.includeByGroup('some');
      return r;
    },
    forcePasswordProtect() {
      return Object.entries(this.ioState)
        .filter(this.updatableSetting)
        .filter(([uuid, setting]) => {
          return setting.mode !== 'discard' && !setting.discardedByParent
        }).some(([uuid, item]) => {
          const asset = this.manifest[uuid];
          return asset.force_password_protect
        });
    },
  },
};
