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
      discardedDependents: [],
    }
  },
  methods: {
    discardDependents(asset) {
      const { dependents } = asset[1];

      if (asset[1].implicit_discard) {
        dependents.map((d) => d.implicitDiscard = true);
      }

      dependents.forEach((dependent) => {
        if (!(dependent.uuid in this.discardedDependents)) {
          this.discardedDependents[dependent.uuid] = { implicitDiscard: dependent.implicitDiscard ? dependent.implicitDiscard : false };
        } else if (this.discardedDependents[dependent.uuid].implicitDiscard === true) {
          this.discardedDependents[dependent.uuid].implicitDiscard = dependent.implicitDiscard;
        }
      });
    },
    setInitialState(assets, rootUuid) {
      this.rootUuid = rootUuid;

      Object.entries(assets).forEach((asset) => {
        if (asset.uuid !== rootUuid) {
          this.discardDependents(asset);
        }
      });

      this.ioState = Object.entries(assets)
        .map(([uuid, asset]) => {
          let mode = this.defaultMode;

          if (this.discardedDependents[uuid]) {
            mode = this.discardedDependents[uuid].implicitDiscard ? "discard" : this.defaultMode;
          }

          return {
            uuid,
            mode,
            group: asset.type,
            forcePasswordProtect: asset.force_password_protect,
          };
        })
        .filter(a => a.uuid !== rootUuid);

        console.log("this.discardedDependents");
        console.log(this.discardedDependents);
        console.log("this.ioState");
        console.log(this.ioState);
    },
    // used for for export
    setForGroup(group, value) {
      if (this.isImport) {
        this.importMode
      }
      const mode = value ? this.defaultMode : 'discard';
      this.setModeForGroup(group, mode);
    },
    // used for for import
    setModeForGroup(group, mode) {
      this.ioState.forEach((asset, i) => {
        if (asset.group === group) {
          this.ioState[i].mode = mode;
        }
      });
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
      this.ioState.forEach((_val, i) => this.ioState[i].mode = mode)
    },
    debug(obj) {
      return JSON.parse(JSON.stringify(this.ioState));
    },
    exportOptions(rootDefaultMode = null) {
      const ioState = this.ioState.map((asset) => [
          asset.uuid,
          { mode: asset.discard ? "discard" : asset.mode },
        ]);

      ioState.push([this.rootUuid, { mode: rootDefaultMode || this.defaultMode }]);
      return Object.fromEntries(ioState);
    },
  },
  computed: {
    defaultMode() {
      return this.isImport ? 'update' : null;
    },
    operation() {
      if (this.isImport) {
        return "Import";
      }
      return "Export";
    },
    includeAll() {
      return this.ioState.every(v => v.mode === this.defaultMode);
    },
    byGroup() {
      return this.ioState.reduce((groups, item) => {
        const group = (groups[item.group] || []);
        group.push(item);
        groups[item.group] = group;
        return groups;
      }, {});
    },
    includeAllByGroup() {
      return Object.fromEntries(
        Object.entries(this.byGroup).map(([group, assets]) => {
          return [group, assets.every(item => item.mode === this.defaultMode)];
        })
      );
    },
    forcePasswordProtect() {
      return this.ioState.filter((item) => item.forcePasswordProtect && item.mode !== "discard");
    },
  },
}