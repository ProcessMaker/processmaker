import { i18n, alert, Mustache } from "../variables/index";
import { updateCollection } from "../api/index";

export default {};

export const isMustache = (record) => /\{\{.*\}\}/.test(record);

// Get data in FormCollectionRecordControl components
export const getCollectionDataFromTask = (task, formData) => {
  const results = [];
  // Verify if object "screen" exists
  if (task.screen) {
    // Verify if "config" array exists and it has at least one element
    if (Array.isArray(task.screen.config) && task.screen.config.length > 0) {
      // Iteration on "config" array
      for (const configItem of task.screen.config) {
        // Verify if "items" array exists
        if (Array.isArray(configItem.items)) {
          // Iteration over each "items" element
          for (const item of configItem.items) {
            // Verify if component "FormCollectionRecordControl" is inside the screen
            if (item.component === "FormCollectionRecordControl") {
              // Access to FormCollectionRecordControl "config" object
              const { config } = item;

              // Saving values into variables
              const collectionFields = config.collection.data[0];
              const submitCollectionChecked = config.collectionmode.submitCollectionCheck;
              let recordId = "";
              const { record } = config;

              if (isMustache(record)) {
                recordId = Mustache.render(record, formData);
              } else {
                recordId = parseInt(record, 10);
              }
              const { collectionId } = config.collection;
              // Save the values into the results array
              results.push({
                submitCollectionChecked, recordId, collectionId, collectionFields,
              });
            }
          }
        }
      }
    }
  }
  return results.length > 0 ? results : null;
};

// Submit collection data
export const submitCollectionData = async (task, formData) => {
  // If screen has CollectionControl components saves collection data (if submit check is true)
  const resultCollectionComponent = getCollectionDataFromTask(task, formData);

  if (resultCollectionComponent && resultCollectionComponent.length > 0) {
    resultCollectionComponent.forEach(async (result) => {
      if (result.submitCollectionChecked) {
        const collectionKeys = Object.keys(result.collectionFields);
        const matchingKeys = _.intersection(Object.keys(formData), collectionKeys);
        const collectionsData = _.pick(formData, matchingKeys);

        updateCollection({
          collectionId: result.collectionId,
          recordId: result.recordId,
          data: {
            data: collectionsData,
            uploads: [],
          },
        }).then(() => {
          alert(i18n.t("Collection data was updated"), "success", 5, true);
        });
      }
    });
  }
  return null;
};
