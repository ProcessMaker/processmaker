// PHP
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

// JAVASCRIPT
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
      line = line.replace('{apiExample}',  `getUserById(id, (error, data, response) => {})`);
      format = ' * ' + line;
    }
    content.push(format);
  });

  return content.join("\n") + `\n\n\ return {};`;
});

// LUA
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
      line = line.replace('{apiExample}',  `users_api:get_users(filter, order_by, order_direction, per_page, include)`);
      format = '  ' + line;
    }
    content.push(format);
  });

  return content.join("\n") + `\n\n\ return {};`;
});

// C#
Vue.filter('csharp', function(value) {
  value = value.split("\r");
  let format = '';
  let content = [];

  value.forEach((line, i) => {
    if (i == 0) {
      format = '/* ' + line;
    } else if (i == value.length - 1) {
      format = line + ' */';
    } else {
      line = line.replace('{dataVariable}',  `data`);
      line = line.replace('{configVariable}',  `config`);
      line = line.replace('{apiExample}',  `apiInstance.GetUserById(id)`);
      format = line;
    }
    content.push(format);
  });

  return content.join("\n") + `\n\n\ return {};`;
});

// JAVA
Vue.filter('java', function(value) {
  value = value.split("\r");
  let format = '';
  let content = [];

  value.forEach((line, i) => {
    if (i == 0) {
      format = '/** ' + line;
    } else if (i == value.length - 1) {
      format = line + '*/';
    } else {
      line = line.replace('{dataVariable}',  `data`);
      line = line.replace('{configVariable}',  `config`);
      line = line.replace('{apiExample}',  `apiInstance.getUserByID(id);`);
      format = ' * ' + line;
    }
    content.push(format);
  });

  return content.join("\n") + `\n\n\ return {};`;
});
