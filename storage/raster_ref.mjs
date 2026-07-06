import * as mupdf from "mupdf";
import { readFileSync, writeFileSync } from "fs";

const src = process.argv[2];
const outPrefix = process.argv[3] || "storage/app/ref";
const S = parseFloat(process.argv[4] || "1.3");

const doc = mupdf.Document.openDocument(readFileSync(src), "application/pdf");
const n = doc.countPages();
console.log("pages:", n);
for (let i = 0; i < n; i++) {
  const page = doc.loadPage(i);
  const b = page.getBounds();
  const dbox = [Math.round(b[0]*S), Math.round(b[1]*S), Math.round(b[2]*S), Math.round(b[3]*S)];
  const pix = new mupdf.Pixmap(mupdf.ColorSpace.DeviceRGB, dbox, false);
  pix.clear(255);
  const dev = new mupdf.DrawDevice(mupdf.Matrix.scale(S,S), pix);
  page.run(dev, mupdf.Matrix.identity); dev.close();
  const fn = `${outPrefix}-p${i+1}.png`;
  writeFileSync(fn, pix.asPNG());
  console.log("wrote", fn, dbox[2]-dbox[0], "x", dbox[3]-dbox[1]);
}
console.log("done");
