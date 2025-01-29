import { FileDownload, FileUpload } from "@processmaker/screen-builder";

async function loadScreenBuilder() {
  const ScreenBuilder = await import('@processmaker/screen-builder');
  Vue.use(ScreenBuilder.default);
  return ScreenBuilder;
}

export {
  FileDownload,
  FileUpload,
  loadScreenBuilder,
};
