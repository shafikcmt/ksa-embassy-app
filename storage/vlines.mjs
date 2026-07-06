import * as mupdf from "mupdf"; import { readFileSync } from "fs";
const src=process.argv[2], f0=+process.argv[3], f1=+process.argv[4];
const S=4; const doc=mupdf.Document.openDocument(readFileSync(src),"application/pdf");
const page=doc.loadPage(0); const b=page.getBounds(); const H=b[3]-b[1];
const dbox=[Math.round(b[0]*S),Math.round((b[1]+H*f0)*S),Math.round(b[2]*S),Math.round((b[1]+H*f1)*S)];
const pix=new mupdf.Pixmap(mupdf.ColorSpace.DeviceRGB,dbox,false); pix.clear(255);
const dev=new mupdf.DrawDevice(mupdf.Matrix.scale(S,S),pix); page.run(dev,mupdf.Matrix.identity); dev.close();
const w=pix.getWidth(),h=pix.getHeight(),n=pix.getNumberOfComponents(),buf=pix.getPixels();
const cols=[]; for(let x=0;x<w;x++){let d=0;for(let y=0;y<h;y++){const i=(y*w+x)*n;if((buf[i]+buf[i+1]+buf[i+2])/3<100)d++;}cols.push(d/h);}
const L=[]; for(let x=1;x<w-1;x++){if(cols[x]>0.55&&cols[x]>=cols[x-1]&&cols[x]>=cols[x+1])L.push(x);}
const m=[]; for(const x of L){if(m.length&&x-m[m.length-1]<=3)continue;m.push(x);}
console.log("v% :", m.map(x=>(x/w*100).toFixed(1)).join(", "));
