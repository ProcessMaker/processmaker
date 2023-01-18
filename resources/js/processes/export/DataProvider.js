export default {
  getManifest(processId) {
    return ProcessMaker.apiClient({
      url: `export/manifest/process/${processId}`,
      method: "GET",
    }).then((response) => {
      const rootUuid = response.data.root;
      const assets = response.data.export;
      const d = this.formatAssets(assets, rootUuid);
      return d;
    });
  },
  exportProcess(processId, options) {
    return ProcessMaker.apiClient.post(
      `export/process/download/` + processId,
      options,
    ).then(response => {
      console.log("post export response: ", response);
      return response;
    });
  },
  formatAssets(assets, rootUuid) {
    const groups = {};
    let root = null;
    // for (const [uuid, asset] of Object.entries(assets)) {
    Object.entries(assets).forEach(([uuid, asset]) => {
      const type = asset.type;

      const info = {
        type,
        typePlural: asset.type_plural,
        name: asset.name,
        categories: this.getCategories(asset, assets),
        description: asset.attributes.description || "N/A",
        createdAt: asset.attributes.created_at || "N/A",
        updatedAt: asset.attributes.updated_at || "N/A",
        processManager: asset.process_manager || "N/A",
        lastModifiedBy: asset.last_modified_by || "N/A",
      };

      if (uuid === rootUuid) {
        root = info;
        return;
      }

      if (!groups[type]) {
        groups[type] = [];
      }

      groups[type].push(info);
    });

    const groupedInfo = Object.entries(groups).map(([key, value]) => {
      return { type: key, typePlural: value[0].typePlural, items: value };
    });

    return {
      root,
      rootUuid,
      assets,
      groups: groupedInfo,
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
