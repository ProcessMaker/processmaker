beforeEach(() => {
  cy.log('Login in');
  cy.visit("/login");
  cy.get("#username")
    .type("admin");
  cy.get("#password")
    .type("admin");
  cy.omitError();
  cy.get(".btn-success")
    .click();
  cy.title()
    .should("include", "My Requests");
});
