var http = require('http');

var num = 0;

var server = http.createServer(function(req, res) {
  var parts = req.url.split('/');
  var code = parts[1];
  var isGCM = parts[2] === 'gcm';

  if (req.method === 'GET') {
    res.writeHead(202);
    res.end(num.toString());
    return;
  }

  if (req.method !== 'POST') {
    res.writeHead(500);
    return;
  }

  num++;

  if (isGCM) {
    if (req.headers['authorization'] !== 'key=aKey') {
      res.writeHead(500);
      return;
    }

    if (req.headers['content-type'] !== 'application/json') {
      res.writeHead(500);
      return;
    }

    if (req.headers['content-length'] !== '33') {
      res.writeHead(500);
      return;
    }

    var body = '';

    req.on('data', function(chunk) {
      body += chunk;
    });

    req.on('end', function() {
      var data = JSON.parse(body);

      if (data.registration_ids.length !== 1) {
        res.writeHead(500);
        return;
      }

      if (data.registration_ids[0] !== 'endpoint') {
        res.writeHead(500);
        return;
      }

      res.writeHead(code);
      res.end('ok');
    });
  } else {
    if (!req.headers['ttl']) {
      res.writeHead(500);
      return;
    }

    res.writeHead(code);
    res.end('ok');
  }
});

server.listen(55555);

