describe('Requests page', () => {
  beforeEach(() => {
    cy.visit("/requests");
  });
  describe("Requests page", function () {
    it("should be in the Request page", function () {
      cy.title()
        .should("include", "My Requests");
    });
    it("should create a new request", function () {
      cy.get("#navbar-request-button")
        .click();
      cy.get("#requests-modal___BV_modal_content_")
        .should("be.visible");
    });
  });
  describe("In Progress Request page", function () {
    it("should be in the Request/In Progress page", function () {
      cy.get("[data-cy='In Progress'")
        .click();
      cy.title()
        .should("include", "Requests In Progress");
    });
  });
  describe("Completed page", function () {
    it("should be in the Request/Completed page", function () {
      cy.get("[data-cy='Completed'")
        .click();
      cy.title()
        .should("include", "Completed Requests");
    });
  });
  describe("All Requests page", function () {
    it("should be in the Request/All Requests page", function () {
      cy.get("[data-cy='All Requests'")
        .click();
      cy.title()
        .should("include", "All Requests");
    });
  });
});
