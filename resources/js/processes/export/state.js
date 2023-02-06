const ioState = [];

export default {
  data() {
    return {
      ioState,
      manifest: {},
      rootUuid: '',
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
            // if (this.isImport) {
            // return [uuid, { mode: this.defaultMode, explicitDiscard: false }];
            return [uuid, { mode: this.defaultMode }];
            // }
            // return [uuid, { mode: 'discard', explicitDiscard: true }];
          })
          .filter(([uuid, _]) => uuid !== rootUuid)
      );
    },
    updateChildren(uuid, mode) {
      // const maxDepth = 20;
      // const setMode = (uuid, depth = 0) => {
      //   if (depth > maxDepth) {
      //     throw new Error('Max depth reached');
      //   }

      //   const asset = this.manifest[uuid];
      //   if (!asset) {
      //     return;
      //   }

      //   // If depth is 0, it's the first element and it was already set.
      //   if (depth > 0) {
      //     this.set(uuid, this.defaultMode, false);
      //   }

      //   asset.dependents.forEach((dependent) => {
      //     const depUuid = dependent.uuid;
      //     enableAsset(depUuid, depth + 1);
      //   });

      // };
      // setMode(uuid);
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
    },
    set(uuid, mode) { //, explicitDiscard = null) {

      if (uuid === this.rootUuid) {
        return;
      }

      const setting = this.ioState[uuid];
      if (!setting) {
        console.log(uuid + " not found in ioState");
        return;
      }

      // if (explicitDiscard !== null) {
      //   setting.explicitDiscard = explicitDiscard;
      // }

      // if (!setting.explicitDiscard) {
      setting.mode = mode;
      this.$set(this.ioState, uuid, setting);
      // }

      // Use nextTicket to wait until all set()'s have been run for this action
      this.$nextTick(() => {
        this.updateChildren(uuid, mode);
      });

    },
    updatableSetting([uuid, setting]) {
      if (uuid === this.rootUuid) {
        return false;
      }
      // return !setting.explicitDiscard;
      return true;
    },
    // used for for export
    setIncludeAll(value) {
      let set = 'discard';
      if (value) {
        set = this.defaultMode;
      }
      this.setModeForAll(set);
    },
    // used for for import
    setModeForAll(mode) {
      Object.entries(this.manifest)
        .forEach(([uuid, asset]) => {
          this.set(uuid, mode);
        });
    },
    debug(obj) {
      return JSON.parse(JSON.stringify(this.ioState));
    },
    exportOptions(rootDefaultMode = null) {
      const rootUuid = this.rootUuid;
      const options = {};
      options[rootUuid] = { mode: rootDefaultMode || this.defaultMode };
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
            return setting.mode === this.defaultMode;
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
    hasSomeAvailable(items) {
      // return items.some(item => {
      //     return !this.ioState[item.uuid].explicitDiscard;
      // });
      return true;
    },
  },
  computed: {
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
          return setting.mode !== 'discard';
        }).some(([uuid, item]) => {
          const asset = this.manifest[uuid];
          return asset.force_password_protect
        });
    },
  },
}