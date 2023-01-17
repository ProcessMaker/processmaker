export default {
  getManifest(processId) {
    return ProcessMaker.apiClient({
      url: `export/manifest/process/${processId}`,
      method: "GET",
    }).then((response) => {
      console.log("data", response.data);
      const rootUuid = response.data.root;
      const assets = response.data.export;
      const d = this.formatAssets(assets, rootUuid);
      console.log("formatted", d);
      return d;
    });
  },
  formatAssets(assets, rootUuid) {
    const groups = {};
    let root = null;
    // for (const [uuid, asset] of Object.entries(assets)) {
    Object.entries(assets).forEach(([uuid, asset]) => {
      const { exporter } = asset;
      const type = this.getTypeFromExporter(exporter);

      const info = {
        type,
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

    const groupedInfo = Object.entries(groups).map(([key, value]) => ({ type: key, items: value }));

    return {
      root,
      groups: groupedInfo,
    };
  },
  getTypeFromExporter(exporter) {
    const match = exporter.match(/([^\\]+)Exporter$/);
    return match[1] || "N/A";
  },
  getCategories(asset, allAssets) {
    const categories = asset.dependents.filter((d) => d.type === "categories").map((category) => allAssets[category.uuid].name);
    if (categories.length === 0) {
      categories.push("Uncategorized");
    }
    return categories.join(", ");
  },
};
