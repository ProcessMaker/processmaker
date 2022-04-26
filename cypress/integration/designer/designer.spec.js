describe('Designer page', () => {
  beforeEach(() => {
    cy.visit("/processes");
  });
  describe("Processes page", () => {
    it("title should contain Processes", () => {
      cy.title()
        .should("include", "Processes");
    });
  });
  describe("Scripts page", () => {
    it("should go to the scripts page", function () {
      cy.get("[data-cy='Scripts']")
        .click();
      cy.title()
        .should("include", "Scripts");
    });
  });
  describe("Screens page", () => {
    it("should go to the screens page", function () {
      cy.get("[data-cy='Screens']")
        .click();
      cy.title()
        .should("include", "Screens");
    });
  });
  describe("Environment Variables page", () => {
    it("should go to the environment variables page", function () {
      cy.get("[data-cy='Environment Variables']")
        .click();
      cy.title()
        .should("include", "Environment Variables");
    });
  });
  describe("Signals page", () => {
    it("should go to the signals page", function () {
      cy.get("[data-cy='Signals']")
        .click();
      cy.title()
        .should("include", "Signals");
    });
  });
})
