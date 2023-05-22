import 'cypress-file-upload';

// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })
Cypress.Commands.add("omitError", () => {
  Cypress.on("uncaught:exception", (err, runnable) => {
    // returning false here prevents Cypress from
    // failing the test
    return false;
  });
});

Cypress.Commands.add('importScreen', (filePath) => {
  let fileBtn = 'input[type="file"]';
  cy.get('[data-cy="button-import-screen"]').click();
  cy.window().then((win) => {
    const element = win.document.getElementById("import-file");
    element.classList.remove("d-none");
    win.document.querySelector(fileBtn).style.visibility = "visible";
    win.document.querySelector(fileBtn).style.display = "block";
    win.document.querySelector(fileBtn).style.width = "200px";
    win.document.querySelector(fileBtn).style.height = "20px";
    win.document.querySelector(fileBtn).style.position = "fixed";
    win.document.querySelector(fileBtn).style.overflow = "visible";
    win.document.querySelector(fileBtn).style.zIndex = "9999999";
    win.document.querySelector(fileBtn).style.top = "500px";
    win.document.querySelector(fileBtn).style.left = "500px";
    win.document.querySelector(fileBtn).style.right = "500px";
    win.document.querySelector(fileBtn).style.bottom = "500px";
  });
  cy.get(fileBtn).attachFile(filePath);
  cy.get('[data-cy="button-import"]').click();
  cy.get('[data-cy="button-list-screen"]').should('be.visible');
  cy.get('[data-cy="button-list-screen"]').click();
});
