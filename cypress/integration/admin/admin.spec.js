describe("Admin page", function () {
  beforeEach(() => {
    cy.visit("/admin/users");
  });

  describe("Users page", () => {
    it("should go to the admin/users page", function () {
      cy.title()
        .should("include", "Users");
    });
  });
  describe("Groups page", () => {
    it("should go to the admin/groups page", function () {
      cy.get("[data-cy='Groups']")
        .click();
      cy.title()
        .should("include", "Groups");
    });
  });
  describe("Auth Clients page", () => {
    it("should go to the admin/auth page", function () {
      cy.get("[data-cy='Auth Clients']")
        .click();
      cy.title()
        .should("include", "Auth Clients");
    });
  });
  describe("Customize UI page", () => {
    it("should go to the admin/customize ui page", function () {
      cy.get("[data-cy='Customize UI']")
        .click();
      cy.title()
        .should("include", "Customize UI");
    });
  });
  describe("Queue Management page", () => {
    it("should go to the admin/queue page", function () {
      cy.get("[data-cy='Queue Management']")
        .click();
      cy.title()
        .should("include", "Queue Management");
    });
  });
  describe("Scripts Executors page", () => {
    it("should go to the admin/scripts executors page", function () {
      cy.get("[data-cy='Script Executors']")
        .click();
      cy.title()
        .should("include", "Script Executors");
    });
  });
});
