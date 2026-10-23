// Servidor local só para testar a página (node .claude/serve.js)
const http = require("http"), fs = require("fs"), path = require("path");
const root = path.join(__dirname, "..");
http.createServer((req, res) => {
  const p = decodeURIComponent(req.url.split("?")[0]);
  const file = path.join(root, p === "/" ? "index.html" : p);
  if (!file.startsWith(root)) { res.writeHead(403); return res.end(); }
  fs.readFile(file, (err, data) => {
    if (err) { res.writeHead(404); return res.end("404"); }
    res.writeHead(200, { "Content-Type": file.endsWith(".html") ? "text/html; charset=utf-8" : "application/octet-stream" });
    res.end(data);
  });
}).listen(process.env.PORT || 8080, function () { console.log("http://localhost:" + this.address().port); });
