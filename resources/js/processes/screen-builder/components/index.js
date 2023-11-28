import { renderer } from "@processmaker/screen-builder";

async function loadScreenBuilder() {
  const ScreenBuilder = await import('@processmaker/screen-builder');
  Vue.use(ScreenBuilder.default);
  return ScreenBuilder;
}

const {
  FileDownload,
  FileUpload,
} = renderer;

export {
  FileDownload,
  FileUpload,
  loadScreenBuilder,
};
