

describe('screen page', () => {
    it("should go to the screens page", function () {
      cy.visit("/designer/screens");
      
      cy.title()
        .should("include", "Screens");

      cy.importScreen('screen_error_alert.json');
      cy.wait(500);

      cy.get('#screenIndex > #search-bar > :nth-child(1) > .flex-grow-1 > #search > .input-group > #search-box').type('Screen Error Alert');
      cy.get('span').contains('Screen Error Alert').click();
      cy.get('.btn-outline-secondary').click();
      
      //no submit form Invalid
      cy.get(':nth-child(2) > .form-group > .btn').click();
      cy.get('.alert-danger').should('be.visible');
      cy.get('.alert-danger').contains('here is a validation error in your form.');

      //submit form valid
      cy.get('[data-cy="screen-field-form_input_1"]').type('12345678');
      cy.get(':nth-child(2) > .form-group > .btn').click();
      cy.on('window:alert', (str) => {
        expect(str).to.equal('Preview Form was Submitted');
      });
      cy.on('window:confirm', () => true);
      
    });
  
})
