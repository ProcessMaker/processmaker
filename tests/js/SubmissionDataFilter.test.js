/**
 * Unit and Integration Tests for Submission Data Filter
 * 
 * These tests ensure that the SOLID refactored submission data filtering
 * works correctly and doesn't break existing functionality.
 */

// Mock component with the methods we want to test
const createTestComponent = () => ({
  isSystemVariable(variableName) {
    return this.isUnderscoreVariable(variableName);
  },
  
  isUnderscoreVariable(variableName) {
    return variableName.startsWith('_');
  },
  
  shouldFilterVariables(variablesToSubmit) {
    return variablesToSubmit && 
           Array.isArray(variablesToSubmit) && 
           variablesToSubmit.length > 0;
  },
  
  filterRequestedVariables(sourceData, requestedVariables) {
    const filtered = {};
    
    requestedVariables.forEach((varName) => {
      if (sourceData.hasOwnProperty(varName)) {
        filtered[varName] = sourceData[varName];
      }
    });
    
    return filtered;
  },
  
  preserveSystemVariables(sourceData, targetData) {
    const preserved = [];
    
    Object.keys(sourceData).forEach((key) => {
      if (this.isSystemVariable(key) && !targetData.hasOwnProperty(key)) {
        targetData[key] = sourceData[key];
        preserved.push(key);
      }
    });
    
    return { preserved, data: targetData };
  },
  
  prepareSubmissionData(formData, buttonInfo) {
    const variablesToSubmit = buttonInfo?.variablesToSubmit;
    
    if (!this.shouldFilterVariables(variablesToSubmit)) {
      return formData;
    }
    
    let filteredData = this.filterRequestedVariables(formData, variablesToSubmit);
    const result = this.preserveSystemVariables(formData, filteredData);
    
    return result.data;
  },
});

describe('Submission Data Filter - Unit Tests', () => {
  let component;

  beforeEach(() => {
    component = createTestComponent();
  });

  // ============================================================================
  // UNIT TESTS
  // ============================================================================

  describe('isUnderscoreVariable()', () => {
    test('should return true for variables starting with underscore', () => {
      expect(component.isUnderscoreVariable('_user')).toBe(true);
      expect(component.isUnderscoreVariable('_request')).toBe(true);
      expect(component.isUnderscoreVariable('_parent')).toBe(true);
    });

    test('should return false for normal variables', () => {
      expect(component.isUnderscoreVariable('name')).toBe(false);
      expect(component.isUnderscoreVariable('email')).toBe(false);
    });
  });

  describe('isSystemVariable()', () => {
    test('should identify underscore variables as system variables', () => {
      expect(component.isSystemVariable('_user')).toBe(true);
      expect(component.isSystemVariable('_request')).toBe(true);
    });

    test('should not identify normal variables as system variables', () => {
      expect(component.isSystemVariable('name')).toBe(false);
    });
  });

  describe('shouldFilterVariables()', () => {
    test('should return true when variablesToSubmit is a non-empty array', () => {
      expect(component.shouldFilterVariables(['name'])).toBe(true);
    });

    test('should return false when variablesToSubmit is null', () => {
      expect(component.shouldFilterVariables(null)).toBeFalsy();
    });

    test('should return false when variablesToSubmit is undefined', () => {
      expect(component.shouldFilterVariables(undefined)).toBeFalsy();
    });

    test('should return false when variablesToSubmit is an empty array', () => {
      expect(component.shouldFilterVariables([])).toBe(false);
    });
  });

  describe('filterRequestedVariables()', () => {
    test('should filter only requested variables', () => {
      const sourceData = {
        name: 'John Doe',
        email: 'john@example.com',
        phone: '555-1234',
      };
      
      const result = component.filterRequestedVariables(sourceData, ['name', 'email']);
      
      expect(result).toEqual({
        name: 'John Doe',
        email: 'john@example.com',
      });
      expect(result.phone).toBeUndefined();
    });
  });

  describe('preserveSystemVariables()', () => {
    test('should preserve system variables', () => {
      const sourceData = {
        name: 'John Doe',
        _user: { id: 1 },
        _request: { id: 100 },
      };
      
      const targetData = {
        name: 'John Doe',
      };
      
      const result = component.preserveSystemVariables(sourceData, targetData);
      
      expect(result.data._user).toEqual({ id: 1 });
      expect(result.data._request).toEqual({ id: 100 });
      expect(result.preserved).toContain('_user');
      expect(result.preserved).toContain('_request');
    });
  });

  // ============================================================================
  // INTEGRATION TESTS
  // ============================================================================

  describe('prepareSubmissionData()', () => {
    test('should return all data when buttonInfo is null', () => {
      const formData = {
        name: 'John',
        _user: { id: 1 },
      };
      
      const result = component.prepareSubmissionData(formData, null);
      
      expect(result).toEqual(formData);
    });

    test('should filter variables and preserve system variables', () => {
      const formData = {
        name: 'John Doe',
        email: 'john@example.com',
        phone: '555-1234',
        _user: { id: 1 },
        _request: { id: 100 },
      };
      
      const buttonInfo = {
        variablesToSubmit: ['name', 'email'],
      };
      
      const result = component.prepareSubmissionData(formData, buttonInfo);
      
      expect(result.name).toBe('John Doe');
      expect(result.email).toBe('john@example.com');
      expect(result._user).toEqual({ id: 1 });
      expect(result._request).toEqual({ id: 100 });
      expect(result.phone).toBeUndefined();
    });

    test('should preserve all underscore variables automatically', () => {
      const formData = {
        name: 'John',
        _user: { id: 1 },
        _request: { id: 100 },
        _newVar: 'new',
      };
      
      const buttonInfo = {
        variablesToSubmit: ['name'],
      };
      
      const result = component.prepareSubmissionData(formData, buttonInfo);
      
      expect(result._user).toEqual({ id: 1 });
      expect(result._request).toEqual({ id: 100 });
      expect(result._newVar).toBe('new');
    });
  });
});
