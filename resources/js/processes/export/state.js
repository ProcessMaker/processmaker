const ioState = [];

export default {
  data: function () {
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
  methods: {
    setInitialState(assets, rootUuid) {
      this.rootUuid = rootUuid;
      this.ioState = Object.entries(assets)
        .map(([uuid, asset]) => {
          return {
            uuid,
            mode: this.defaultMode,
            group: asset.type,
          };
        })
        .filter(a => a.uuid !== rootUuid);
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
      const ioState = this.ioState.map(asset => {
        return [
          asset.uuid,
          { mode: asset.mode }
        ];
      });
      
      ioState.push([this.rootUuid, { mode: rootDefaultMode || this.defaultMode }])
      // ioState.push([this.rootUuid, { mode: 'copy' }])
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
  },
}