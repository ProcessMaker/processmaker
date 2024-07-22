// We can also use the following command:
// npx svgtofont --sources ./resources/icons --output ./resources/fonts/font-pm --fontName fp
// But this command generates other unnecessary files

const svgtofont = require("svgtofont");
const path = require("path");

svgtofont({
  src: path.resolve(process.cwd(), "resources/icons"), // svg path
  dist: path.resolve(process.cwd(), "resources/fonts/font-pm"), // output path
  fontName: "fp", // font name
  css: true, // Create CSS files.
}).then(() => {
  console.log("done!");
});
