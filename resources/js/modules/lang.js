let json = require('../../lang/en.json');

function file(lang = 'en') {

    try {

        json = require(`../../lang/${lang}.json`);

    } catch (e) { }

    return json;

}

function translate(value) {
    let language = document.querySelector("html").getAttribute('lang');

    if (file(language)[value] !== undefined) {
        return file(language)[value];
    } else {
        return value
    }
}
module.exports = translate;