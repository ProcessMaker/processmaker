Vue.filter('php', function(value) {
  value = value.split("\r");
  let format = '';
  let content = [];

  value.forEach((line, i) => {
    if (i == 0) {
      format = '<?php \r\n/* ' + line;
    } else if (i == value.length - 1) {
      format = line + '*/';
    } else {
      line = line.replace('{dataVariable}',  `$data`);
      line = line.replace('{configVariable}',  `$config`);
      line = line.replace('{apiExample}',  `$api->users()->getUserById(1)['email']`);
      format = ' * ' + line;
    }
    content.push(format);
  });

  return content.join("\n") + `\n\n\ return [];`;
});

Vue.filter('javascript', function(value) {
  value = value.split("\r");
  let format = '';
  let content = [];

  value.forEach((line, i) => {
    if (i == 0) {
      format = '/* ' + line;
    } else if (i == value.length - 1) {
      format = line + '*/';
    } else {
      line = line.replace('{dataVariable}',  `$data`);
      line = line.replace('{configVariable}',  `$config`);
      format = ' * ' + line;
    }
    content.push(format);
  });

  return content.join("\n") + `\n\n\ return [];`;
});

Vue.filter('lua', function(value) {
  value = value.split("\r");
  let format = '';
  let content = [];

  value.forEach((line, i) => {
    if (i == 0) {
      format = '--[[ ' + line;
    } else if (i == value.length - 1) {
      format = line + ' --]]';
    } else {
      line = line.replace('{dataVariable}',  `data`);
      line = line.replace('{configVariable}',  `config`);
      format = '  ' + line;
    }
    content.push(format);
  });

  return content.join("\n") + `\n\n\ return {};`;
});
