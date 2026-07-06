import * as mupdf from "mupdf";
import { readFileSync } from "fs";
const doc=mupdf.Document.openDocument(readFileSync(process.argv[2]),"application/pdf");
const page=doc.loadPage(0);
const j=JSON.parse(page.toStructuredText("preserve-whitespace").asJSON());
const fonts=new Set();
for(const blk of j.blocks||[]){for(const ln of blk.lines||[]){if(ln.font)fonts.add(JSON.stringify(ln.font));for(const s of ln.spans||[]){if(s.font)fonts.add(JSON.stringify(s.font));}}}
console.log([...fonts].join("\n"));
