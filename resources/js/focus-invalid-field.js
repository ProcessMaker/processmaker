import _ from 'lodash';

export default (error) => {

    const errorFields = _.get(error, 'response.data.errors', {});
    const selector = Object.keys(errorFields).map(field => `[name='${field}']`).join(', ');

    let firstInput = document.querySelector(selector);
    if (firstInput) {
        firstInput.focus();
    }
};