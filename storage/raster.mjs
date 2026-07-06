import * as mupdf from "mupdf";
import { readFileSync, writeFileSync } from "fs";
const doc = mupdf.Document.openDocument(readFileSync("storage/app/app-test.pdf"), "application/pdf");
const page = doc.loadPage(0);
const S = 1.4;
const b = page.getBounds();
const H = b[3]-b[1];
function crop(f0,f1,name){
  const y0=b[1]+H*f0, y1=b[1]+H*f1;
  const dbox=[Math.round(b[0]*S),Math.round(y0*S),Math.round(b[2]*S),Math.round(y1*S)];
  const pix=new mupdf.Pixmap(mupdf.ColorSpace.DeviceRGB,dbox,false);
  pix.clear(255);
  const dev=new mupdf.DrawDevice(mupdf.Matrix.scale(S,S),pix);
  page.run(dev,mupdf.Matrix.identity); dev.close();
  writeFileSync(name,pix.asPNG());
}
crop(0.15,0.37,"storage/app/id.png");
crop(0.66,1.0,"storage/app/foot.png");
console.log("done");
