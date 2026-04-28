function php(value) {
  value = value.split("\r");
  let format = "";
  const content = [];

  value.forEach((line, i) => {
    if (i == 0) {
      format = `<?php \r\n/* ${line}`;
    } else if (i == value.length - 1) {
      format = `${line}*/`;
    } else {
      line = line.replace("{accessEnvVar}", "getenv(\"ENV_VAR_NAME\")");
      line = line.replace("{dataVariable}", "$data");
      line = line.replace("{configVariable}", "$config");
      line = line.replace("{apiExample}", "$api->users()->getUserById(1)['email']");
      line = line.replace("{apiDocsUrl}", "https://github.com/ProcessMaker/docker-executor-php/tree/master/docs/sdk");
      format = ` * ${line}`;
    }
    content.push(format);
  });

  return `${content.join("\n")}\n\n\ return [];`;
}

function javascript(value) {
  value = value.split("\r");
  let format = "";
  const content = [];

  value.forEach((line, i) => {
    if (i == 0) {
      format = `/* ${line}`;
    } else if (i == value.length - 1) {
      format = `${line}*/`;
    } else {
      line = line.replace("{accessEnvVar}", "process.env.ENV_VAR_NAME");
      line = line.replace("{dataVariable}", "data");
      line = line.replace("{configVariable}", "config");
      line = line.replace("{apiExample}", "getUserById(id, (error, data, response) => {})");
      line = line.replace("{apiDocsUrl}", "https://github.com/ProcessMaker/docker-executor-node/tree/master/docs/sdk");
      format = ` * ${line}`;
    }
    content.push(format);
  });

  return `${content.join("\n")}\n\n\ return {};`;
}

function lua(value) {
  value = value.split("\r");
  let format = "";
  const content = [];

  value.forEach((line, i) => {
    if (i == 0) {
      format = `--[[ ${line}`;
    } else if (i == value.length - 1) {
      format = `${line} --]]`;
    } else {
      line = line.replace("{accessEnvVar}", "os.getenv(\"ENV_VAR_NAME\")");
      line = line.replace("{dataVariable}", "data");
      line = line.replace("{configVariable}", "config");
      line = line.replace("{apiExample}", "users_api:get_users(filter, order_by, order_direction, per_page, include)");
      line = line.replace("{apiDocsUrl}", "https://docs.processmaker.com/designing-processes/scripts/script-editor#processmaker-and-environment-variable-syntax-usage-sdk-and-examples");
      format = `  ${line}`;
    }
    content.push(format);
  });

  return `${content.join("\n")}\n\n\ return {};`;
}

function csharp(value) {
  value = value.split("\r");
  let format = "";
  const content = [];

  value.forEach((line, i) => {
    if (i == 0) {
      format = `/* ${line}`;
    } else if (i == value.length - 1) {
      format = `${line} */`;
    } else {
      line = line.replace("{accessEnvVar}", "System.Environment.GetEnvironmentVariable('ENV_VAR_NAME')");
      line = line.replace("{dataVariable}", "data");
      line = line.replace("{configVariable}", "config");
      line = line.replace("{apiExample}", "apiInstance.GetUserById(id)");
      line = line.replace("{apiDocsUrl}", "https://docs.processmaker.com/designing-processes/scripts/script-editor#processmaker-and-environment-variable-syntax-usage-sdk-and-examples");
      format = line;
    }
    content.push(format);
  });

  return `${content.join("\n")}\n\n\ return {};`;
}

function java(value) {
  value = value.split("\r");
  let format = "";
  const content = [];

  value.forEach((line, i) => {
    if (i == 0) {
      format = `/** ${line}`;
    } else if (i == value.length - 1) {
      format = `${line}*/`;
    } else {
      line = line.replace("{accessEnvVar}", "System.getenv(\"ENV_VAR_NAME\")");
      line = line.replace("{dataVariable}", "data");
      line = line.replace("{configVariable}", "config");
      line = line.replace("{apiExample}", "apiInstance.getUserByID(id);");
      line = line.replace("{apiDocsUrl}", "https://docs.processmaker.com/designing-processes/scripts/script-editor#processmaker-and-environment-variable-syntax-usage-sdk-and-examples");
      format = ` * ${line}`;
    }
    content.push(format);
  });

  return `${content.join("\n")}\n\n\ return {};`;
}

function python(value) {
  value = value.split("\r");
  let format = "";
  const content = [];

  value.shift();
  value.forEach((line, i) => {
    line = line.replace("{accessEnvVar}", "os.environ['ENV_VAR_NAME']");
    line = line.replace("{dataVariable}", "the data variable");
    line = line.replace("{configVariable}", "the config variable");
    line = line.replace("{apiExample}", ":");
    line = line.replace("{apiDocsUrl}", "https://docs.processmaker.com/designing-processes/scripts/script-editor#processmaker-and-environment-variable-syntax-usage-sdk-and-examples");
    format = `# ${line}`;
    content.push(format);
  });
  content.push("#  users_api_instance = pmsdk.UsersApi(pmsdk.ApiClient(configuration))");
  content.push("#  user = users_api_instance.get_user_by_id(1)");
  content.push("#  output = {\"name\": user.fullname}");

  return `${content.join("\n")}\n\noutput={};`;
}

export default {
  php,
  javascript,
  lua,
  csharp,
  java,
  python,
};
