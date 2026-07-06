import * as mupdf from "mupdf";
import { readFileSync, writeFileSync } from "fs";

// args: leftPdf rightPdf out.png scale
const leftSrc = process.argv[2];
const rightSrc = process.argv[3];
const out = process.argv[4] || "storage/app/sidebyside.png";
const S = parseFloat(process.argv[5] || "1.1");
const GAP = 24; // px white gutter between the two pages

function loadPage0(src) {
  const doc = mupdf.Document.openDocument(readFileSync(src), "application/pdf");
  return doc.loadPage(0);
}

const L = loadPage0(leftSrc);
const R = loadPage0(rightSrc);
const lb = L.getBounds(), rb = R.getBounds();
const lw = Math.round((lb[2] - lb[0]) * S), lh = Math.round((lb[3] - lb[1]) * S);
const rw = Math.round((rb[2] - rb[0]) * S), rh = Math.round((rb[3] - rb[1]) * S);
const W = lw + GAP + rw;
const H = Math.max(lh, rh);

const pix = new mupdf.Pixmap(mupdf.ColorSpace.DeviceRGB, [0, 0, W, H], false);
pix.clear(255);

// Left page at origin, right page shifted by lw+GAP. Matrix = [S,0,0,S,ox,oy].
const devL = new mupdf.DrawDevice([S, 0, 0, S, -lb[0] * S, -lb[1] * S], pix);
L.run(devL, mupdf.Matrix.identity); devL.close();

const devR = new mupdf.DrawDevice([S, 0, 0, S, (lw + GAP) - rb[0] * S, -rb[1] * S], pix);
R.run(devR, mupdf.Matrix.identity); devR.close();

writeFileSync(out, pix.asPNG());
console.log("wrote", out, W + "x" + H, "(left:", leftSrc, "| right:", rightSrc + ")");
