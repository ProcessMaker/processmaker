const ioState = [];

export default {
  data: function () {
    return {
      ioState,
    }
  },
  methods: {
    setInitialState(assets, rootUuid) {
      this.ioState = Object.entries(assets)
        .map(([uuid, asset]) => {
          return {
            uuid,
            mode: null,
            group: asset.type,
          };
        })
        .filter(a => a.uuid !== rootUuid);
    },
    // used for for export
    setForGroup(group, value) {
      const mode = value ? null : 'discard';
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
          set = null;
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
    exportOptions() {
      return Object.fromEntries(
        this.ioState.map(asset => {
          return [
            asset.uuid,
            { mode: asset.mode }
          ];
        })
      );
    },
  },
  computed: {
    includeAll() {
      return this.ioState.every(v => v.mode === null);
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
          return [group, assets.every(item => item.mode === null)];
        })
      );
    },
  },
}