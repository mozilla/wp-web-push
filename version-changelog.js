var readline = require('readline');
var fs = require('fs');

var rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

var version;
var changelog = [];

rl.question('New version: ', answer => {
  version = answer;
  askChangelog();
});

function askChangelog() {
  rl.write('Changelog:\n');
  rl.setPrompt('');
  rl.prompt();

  rl.on('line', function(cmd) {
    if (!cmd) {
      rl.close();
    }

    changelog.push(cmd);
  });

  rl.on('close', function (cmd) {
    writeFiles();
  });
}

function writeFiles() {
  // Update version in the plugin's main file.
  var pluginMain = fs.readFileSync('wp-web-push/wp-web-push.php', 'utf8');
  var indexStart = pluginMain.indexOf('Version: ') + 'Version: '.length;
  var indexEnd = pluginMain.indexOf('\n', indexStart);
  pluginMain = pluginMain.substring(0, indexStart) + version + pluginMain.substring(indexEnd);
  fs.writeFileSync('wp-web-push/wp-web-push.php', pluginMain);

  // Update version in the package.json file.
  var packageJson = JSON.parse(fs.readFileSync('package.json', 'utf8'));
  packageJson.version = version;
  fs.writeFileSync('package.json', JSON.stringify(packageJson, null, 2) + '\n');

  // Update readme.txt to add changelog.
  var readmeTxt = fs.readFileSync('wp-web-push/readme.txt', 'utf8');
  var indexChangelog = readmeTxt.indexOf('== Changelog ==') + '== Changelog =='.length + 1;
  readmeTxt = readmeTxt.substring(0, indexChangelog) + '= ' + version + ' =\n' + changelog.join('\n') + '\n\n' + readmeTxt.substring(indexChangelog);
  fs.writeFileSync('wp-web-push/readme.txt', readmeTxt);
}
