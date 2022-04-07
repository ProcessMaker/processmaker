describe('Requests page', () => {
  beforeEach(() => {
    cy.visit('/requests');
  });
  it("should create a new request", function () {
    cy.get('#navbar-request-button').click();
    cy.get('#requests-modal___BV_modal_content_').should('be.visible');
  });
});
