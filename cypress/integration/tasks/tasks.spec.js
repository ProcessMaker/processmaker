describe("Tasks page", () => {
  beforeEach(() => {
    cy.visit("/tasks");
  });

  describe("Task Table", () => {
    beforeEach(() => {
      cy.title()
        .should("include", "To Do Tasks");
    });
    it("task table shouldn't be visible", function () {
      cy.get("[data-cy='no-results-message']")
        .should("be.visible")
        .contains("Congratulations");
      cy.get("[data-cy='tasks-table']")
        .should("not.be.visible");
    });
    xit("task table should be visible", function () {
      cy.get("[data-cy='no-results-message']")
        .should("not.be.visible");
      cy.get("[data-cy='tasks-table']")
        .should("be.visible");
    });
  });

  describe("Completed tab", () => {
    it("should go to the completed tab", function () {
      cy.get("[data-cy='Completed']")
        .click();
      cy.title()
        .should("include", "Completed");
    });

  });
});
