// We can also use the following command:
// npx svgtofont --sources ./resources/icons --output ./resources/fonts/font-pm --fontName fp
// But this command generates other unnecessary files

import svgtofont from "svgtofont";
import path from "path";

svgtofont({
  src: path.resolve(process.cwd(), "./devhub/pm-font/svg"), // svg path
  dist: path.resolve(process.cwd(), "./devhub/pm-font/dist"), // output path
  fontName: "processmaker-font", // font name
  css: true, // Create CSS files.
  startUnicode: 0xea01, // unicode start number
  svgicons2svgfont: {
    fontHeight: 1000,
    normalize: true,
  },
  classNamePrefix: "fp",
  website: {
    template: path.join(process.cwd(), "./devhub/pm-font/template.ejs"),
    title: "ProcessMaker Icons",
    // Must be a .svg format image.
    version: "0.0.1",
    meta: {
      description: "Icons generated with svgtofont. For add new icons, please check the README file",
      keywords: "svgtofont,TTF,EOT,WOFF,WOFF2,SVG",
    },
    description: "",
    // Add a Github corner to your website
    // Like: https://github.com/uiwjs/react-github-corners
    corners: {
      url: "https://github.com/ProcessMaker/processmaker",
      width: 62, // default: 60
      height: 62, // default: 60
      bgColor: "#dc3545", // default: '#151513'
    },
    links: [
      {
        title: "ProcessMaker GitHub",
        url: "https://github.com/ProcessMaker/processmaker",
      },
    ],
    footerInfo: "SVGTOFONT is Licensed under MIT. (Yes it's free and open-sourced",
  },
}).then(() => {
  console.log("done!");
});
