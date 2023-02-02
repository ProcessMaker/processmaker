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
    // discardDependents(asset) {
    //   const { dependents } = asset[1];

    //   if (asset[1].implicit_discard) {
    //     dependents.map((d) => d.implicitDiscard = true);
    //   }

    //   dependents.forEach((dependent) => {
    //     if (!(dependent.uuid in this.discardedDependents)) {
    //       this.discardedDependents[dependent.uuid] = { implicitDiscard: dependent.implicitDiscard ? dependent.implicitDiscard : false };
    //     } else if (this.discardedDependents[dependent.uuid].implicitDiscard === true) {
    //       this.discardedDependents[dependent.uuid].implicitDiscard = dependent.implicitDiscard;
    //     }
    //   });
    // },
    setInitialState(assets, rootUuid) {
      this.manifest = assets;
      this.rootUuid = rootUuid;

      // Object.entries(assets).forEach((asset) => {
      //   if (asset.uuid !== rootUuid) {
      //     this.discardDependents(asset);
      //   }
      // });

      // init
      this.ioState = Object.fromEntries(
        Object.entries(assets)
          .map(([uuid, asset]) => {
            // let mode = asset.explicit_discard ? 'discard' : this.defaultMode;

            // if (this.discardedDependents[uuid]) {
            //   mode = this.discardedDependents[uuid].implicitDiscard ? "discard" : this.defaultMode;
            // }

            return [uuid, { mode: 'discard', explicitDiscard: true }];
              // group: asset.type,
              // forcePasswordProtect: asset.force_password_protect,
            // };
          })
          .filter(([uuid, _]) => uuid !== rootUuid)
      );


      // Traverse tree and enable assets
console.log("Manifest", this.manifest);
Object.keys(this.ioState).forEach((uuid) => {
  this.set(uuid, 'update', false);
})
      // assets[rootUuid].forEach((asset) => {
      //   if (asset.explicit_discard) {
      //     return;
      //   } else {
      //     this.ioState[asset.uuid].mode = 'update';
      //     this.ioState[asset.uuid].explicitDiscard = 'false';
      //   }
      //   asset.dependents.forEach((dependent) => {
      //     const dependent = assets[dependent.uuid];
      //     // recurse
      //   })
      // });


      console.log("this.discardedDependents");
      console.log(this.discardedDependents);
      console.log("this.ioState");
      console.log(this.ioState);
    },
    // used for for export
    setForGroup(group, value) {
      const mode = value ? this.defaultMode : 'discard';
      this.setModeForGroup(group, mode);
    },
    // used for for import
    setModeForGroup(group, mode) {
      Object.entries(this.manifest).filter(([uuid, asset]) => {
        return asset.type === group;
      }).forEach(([uuid, _]) => {
        this.set(uuid, mode);
      });
    },
    set(uuid, mode, explicitDiscard = null) {

      if (uuid === this.rootUuid) {
        return;
      }

      const setting = this.ioState[uuid];
      if (!setting) {
        console.log(uuid + " not found in ioState");
        return;
      }

      if (explicitDiscard !== null) {
        setting.explicitDiscard = explicitDiscard;
      }

      if (!setting.explicitDiscard) {
        setting.mode = mode;
        this.$set(this.ioState, uuid, setting);
      }

    },
    // updatableAssets([uuid, asset]) {
    //   // return this.explicitDiscardAssets.has(item.uuid);
    //   return !this.ioState[uuid].explicitDiscard;
    // },
    updatableSetting([uuid, setting]) {
      if (uuid === this.rootUuid) {
        return false;
      }
      return !setting.implicitDiscard;
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
      // const ioState = this.ioState.map((asset) => [
      //     asset.uuid,
      //     { mode: asset.discard ? "discard" : asset.mode },
      //   ]);

      // ioState.push([this.rootUuid, { mode: rootDefaultMode || this.defaultMode }]);
      // return Object.fromEntries(ioState);
      const rootUuid = this.rootUuid;
      return {
        rootUuid: { mode: rootDefaultMode || this.defaultMode },
        ...this.ioState,
      };
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
      return Object.entries(this.ioState).filter(this.updatableSetting).every(([_, setting]) => {
        return setting.mode === this.defaultMode
      });
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
      const res = Object.fromEntries(
        Object.entries(this.uuidsByGroup).map(([group, uuids]) => {
          const allAreIncluded = Object.entries(this.ioState)
            .filter(this.updatableSetting)
            .filter(([uuid, setting]) => {
              return uuids.includes(uuid)
            }).every(([uuid, setting]) => setting.mode === this.defaultMode);
          return [group, allAreIncluded];
        })
      );
      return res;
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