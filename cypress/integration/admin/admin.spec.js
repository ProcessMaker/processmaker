beforeEach(() => {
  cy.visit("/admin/users");
});

describe("Users page", () => {
  it("should go to the admin/users page", function () {
    cy.title()
      .should("include", "Users");
  });
});
