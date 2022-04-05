describe('Tasks page', () => {
  beforeEach(() => {
    cy.visit("/tasks");
  });

  describe("Task Table", () => {
    it("should task table be visible", function () {
      cy.get(".table-card")
        .should("be.visible");
      cy.title().should("include", "To Do Tasks");
    });
  });

  // TODO add data-cy to nav bar
  xdescribe('Completed tab', () => {
    it("should go to the completed tab", function () {
      cy.get('[aria-label]="completed"').click();
      cy.title().should("include", "Completed Tasks");
    });
  })
});
