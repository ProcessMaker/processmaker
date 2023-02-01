import ImportExportIcons from "../../components/shared/ImportExportIcons";

export default {
  doImport(file, options, password) {
    let formData = new FormData();
    const optionsBlob = new Blob([JSON.stringify(options)], {
        type: 'application/json'
    });
  
    formData.append('file', file);
    formData.append('options', optionsBlob);
    formData.append('password', password);
    
    return ProcessMaker.apiClient.post('/import/do-import', formData,
    {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    });
  },
  getManifest(processId) {
    return ProcessMaker.apiClient({
      url: `export/manifest/process/${processId}`,
      method: "GET",
    }).then((response) => {
      const rootUuid = response.data.root;
      const assets = response.data.export;
      return this.formatAssets(assets, rootUuid, response.data.passwordRequired);
    });
  },
  exportProcess(processId, password, options) {
    return ProcessMaker.apiClient({
      method: 'POST',
      url: `export/process/download/` + processId,
      responseType: 'blob',
      data: {
        options,
        password
      }
    }).then(response => {
      let header = response.headers['export-info'];
      let exportInfo = JSON.parse(header);
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement("a");
      link.href = url;
      link.setAttribute("download", exportInfo.name.replace(' ', '_') + ".json");
      document.body.appendChild(link);
      link.click();
      return exportInfo;
    });
  },
  formatAssets(assets, rootUuid, passwordRequired) {
    const groups = {};
    let root = null;
    // for (const [uuid, asset] of Object.entries(assets)) {
    Object.entries(assets).forEach(([uuid, asset]) => {
      const type = asset.type;

      const info = {
        uuid,
        type,
        typePlural: asset.type_plural,
        typeHuman: asset.type_human,
        typeHumanPlural: asset.type_human_plural,
        name: asset.name,
        categories: this.getCategories(asset, assets),
        extraAttributes: asset.extraAttributes || [],
        description: asset.description || null,
        createdAt: asset.attributes.created_at || "N/A",
        updatedAt: asset.attributes.updated_at || "N/A",
        processManager: asset.process_manager || "N/A",
        processManagerId: asset.process_manager_id || null,
        lastModifiedBy: asset.last_modified_by || "N/A",
        lastModifiedById: asset.last_modified_by_id || null,
        importMode: asset.import_mode || 'update',
        forcePasswordProtect: asset.force_password_protect,
        required: asset.required,
        hidden: asset.hidden,
        implicitDiscard: asset.implicit_discard,
      };

      if (uuid === rootUuid) {
        info.hidden = false;
        root = info;
        return;
      }

      if (!groups[type]) {
        groups[type] = [];
      }

      groups[type].push(info);
    });

    const groupedInfo = Object.entries(groups).map(([key, value]) => {
      return {
        type: key, 
        typePlural: value[0].typePlural,
        typeHuman: value[0].typeHuman,
        typeHumanPlural: value[0].typeHumanPlural,
        icon: ImportExportIcons.ICONS[key] || 'fa-code',
        items: value,
        forcePasswordProtect: value[0].forcePasswordProtect,
        required: value.every(i => i.required),
        hidden: value.every(i => i.hidden),
        discard: value.every(i => i.discard),
        implicitDiscard: value.every(i => i.implicitDiscard),
      };
    });

    return {
      root,
      rootUuid,
      assets,
      groups: groupedInfo,
      passwordRequired,
    };
  },
  // getTypeFromExporter(exporter) {
  //   const match = exporter.match(/([^\\]+)Exporter$/);
  //   return match[1] || "N/A";
  // },
  getCategories(asset, allAssets) {
    const categories = asset.dependents.filter((d) => d.type === "categories").map((category) => allAssets[category.uuid].name);
    if (categories.length === 0) {
      categories.push("Uncategorized");
    }
    return categories.join(", ");
  },
};
