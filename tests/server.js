var http = require('http');
var fs = require('fs');
var urlBase64 = require('urlsafe-base64');
var jws = require('jws');

var num = 0;

var server = http.createServer(function(req, res) {
  var parts = req.url.split('/');
  var code = parts[1];
  var isGCM = parts[2] === 'gcm';
  var isVAPID = parts[3] === 'vapid';

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

    if (isVAPID) {
      if (!req.headers['crypto-key']) {
        res.writeHead(500);
        return;
      }

      if (req.headers['crypto-key'].indexOf('p256ecdsa=') !== 0) {
        res.writeHead(500);
        return;
      }

      var vapidKey = urlBase64.decode(req.headers['crypto-key'].substring('p256ecdsa='.length));

      if (!req.headers['authorization']) {
        res.writeHead(500);
        return;
      }

      if (req.headers['authorization'].indexOf('Bearer ') !== 0) {
        res.writeHead(500);
        return;
      }

      var jwt = req.headers['authorization'].substring('Bearer '.length);

      if (!jws.verify(jwt, 'ES256', fs.readFileSync('tests/example_ec_public_key.pem'))) {
        res.writeHead(500);
        return;
      }

      var decoded = jws.decode(jwt);
      if (decoded.header.typ !== 'JWT') {
        res.writeHead(500);
        return;
      }

      if (decoded.header.alg !== 'ES256') {
        res.writeHead(500);
        return;
      }

      if (decoded.payload.aud !== 'https://example.org') {
        res.writeHead(500);
        return;
      }

      if (decoded.payload.exp < Date.now() / 1000) {
        res.writeHead(500);
        return;
      }

      if (decoded.payload.sub !== 'mailto:webpush_ops@catfacts.example.com') {
        res.writeHead(500);
        return;
      }
    }

    res.writeHead(code);
    res.end('ok');
  }
});

server.listen(55555);

