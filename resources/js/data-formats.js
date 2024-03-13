import { DataTypeProperty, Currencies } from "@processmaker/screen-builder";

const formats = DataTypeProperty.config.options;
const masks = Currencies;

export default class {
  static formats() {
    if (!formats.find((format) => format.value == "boolean")) {
      formats.push({ value: "boolean", content: "Boolean" });
    }
    if (!formats.find((format) => format.value == "array")) {
      formats.push({ value: "array", content: "Array" });
      formats.push({ value: "file", content: "File" });
    }
    return formats;
  }

  static format(value) {
    let format = { value: "string", content: "Text" };

    if (value) {
      const found = this.formats().find((option) => option.value == value);
      if (found) {
        format = found;
      }
    }

    return format;
  }

  static masks() {
    return masks;
  }

  static mask(value) {
    let mask = null;

    if (value) {
      if (typeof value === "object" && value.code) {
        mask = this.masks().default.find((option) => option.code == value.code);
      }
      if (typeof value === "string") {
        mask = this.masks().default.find((option) => option.code == value);
      }
    }

    return mask;
  }
}
