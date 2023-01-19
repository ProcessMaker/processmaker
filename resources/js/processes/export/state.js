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
      treeNodesVisited: new Set(),
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
    exportOptions() {
      const ioState = this.ioState.map(asset => {
        return [
          asset.uuid,
          { mode: asset.mode }
        ];
      });
      ioState.push([this.rootUuid, { mode: this.defaultMode }])
      // ioState.push([this.rootUuid, { mode: 'copy' }])
      return Object.fromEntries(ioState);
    },
    tree() {
      return this.treeNode(this.rootUuid);
    },
    treeNode(uuid, dependentType = null) {
      this.treeNodesVisited.add(uuid);
      const asset = this.manifest[uuid];
      return {
        label: asset.name,
        type: asset.type,
        dependentType,
        children: asset.dependents.map(dependent => {
          const uuid = dependent.uuid;
          const childDependentType = dependent.type;
          if (this.treeNodesVisited.has(uuid)) {
            // return a link instead so we don't end up in an infinite loop
            const visitedAsset = this.manifest[uuid];
            return {
              link: uuid,
              dependentType: childDependentType,
              type: visitedAsset.type,
              label: visitedAsset.name,
              children: []
            };
          }
          return this.treeNode(uuid, childDependentType);
        }),
      }
    }
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