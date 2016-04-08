var http = require('http');

var num = 0;

var server = http.createServer(function(req, res) {
  if (req.method === 'GET') {
    res.writeHead(202);
    res.end(num.toString());
    return;
  }

  if (req.method !== 'POST') {
    res.writeHead(500);
    return;
  }

  if (!req.headers['ttl']) {
    res.writeHead(500);
    return;
  }

  num++;

  var code = req.url.substr(1);
  res.writeHead(code);
  res.end('ok');
});

server.listen(55555);

