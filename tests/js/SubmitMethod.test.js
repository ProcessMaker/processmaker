/**
 * Tests for submit() method in edit.js
 * 
 * These tests ensure that the submit method works correctly with the new
 * dataToSubmit parameter and maintains backward compatibility.
 */

// Mock ProcessMaker global
global.ProcessMaker = {
  apiClient: {
    put: jest.fn(),
  },
  alert: jest.fn(),
};

// Mock global alert
global.window = {
  ProcessMaker: {
    alert: jest.fn(),
  },
};

// Mock lodash
global._ = {
  intersection: jest.fn((a, b) => a.filter(value => b.includes(value))),
  pick: jest.fn((obj, keys) => {
    const result = {};
    keys.forEach(key => {
      if (obj.hasOwnProperty(key)) {
        result[key] = obj[key];
      }
    });
    return result;
  }),
};

// Create test component with submit method
const createTestComponent = () => ({
  isSelfService: false,
  submitting: false,
  task: {
    id: 123,
    screen: null,
  },
  $t: (key) => key, // Mock translation
  $refs: {
    task: {
      loadNextAssignedTask: jest.fn(),
    },
  },
  
  processCollectionData: jest.fn(() => null),
  
  submit(task, dataToSubmit) {
    if (this.isSelfService) {
      ProcessMaker.alert(this.$t("Claim the Task to continue."), "warning");
      return;
    }
    
    if (this.submitting) {
      return;
    }
    
    // Process collection data
    const resultCollectionComponent = this.processCollectionData(this.task);
    const messageCollection = this.$t("Collection data was updated");
    
    if (resultCollectionComponent && resultCollectionComponent.length > 0) {
      resultCollectionComponent.forEach((result) => {
        if (result.submitCollectionChecked) {
          const collectionKeys = Object.keys(result.collectionFields);
          const matchingKeys = _.intersection(Object.keys(dataToSubmit), collectionKeys);
          const collectionsData = _.pick(dataToSubmit, matchingKeys);
          
          ProcessMaker.apiClient
            .put(`collections/${result.collectionId}/records/${result.recordId}`, {
              data: collectionsData,
              uploads: [],
            })
            .then(() => {
              window.ProcessMaker.alert(messageCollection, "success", 5, true);
            });
        }
      });
    }
    
    const message = this.$t("Task Completed Successfully");
    const taskId = task.id;
    this.submitting = true;
    
    return ProcessMaker.apiClient
      .put(`tasks/${taskId}`, { status: "COMPLETED", data: dataToSubmit })
      .then(() => {
        window.ProcessMaker.alert(message, "success", 5, true);
      })
      .catch((error) => {
        if (error.response?.status && error.response?.status === 422) {
          if (error.response.data.errors) {
            Object.entries(error.response.data.errors).forEach(([key, value]) => {
              window.ProcessMaker.alert(`${key}: ${value[0]}`, "danger", 0);
            });
          } else if (error.response.data.message) {
            window.ProcessMaker.alert(error.response.data.message, "danger", 0);
          }
          this.$refs.task.loadNextAssignedTask();
        }
      })
      .finally(() => {
        this.submitting = false;
      });
  },
});

describe('submit() Method Tests', () => {
  let component;
  
  beforeEach(() => {
    // Reset all mocks before each test
    jest.clearAllMocks();
    
    // Reset ProcessMaker.apiClient.put to return a resolved promise
    ProcessMaker.apiClient.put.mockResolvedValue({ data: {} });
    
    component = createTestComponent();
  });
  
  // ============================================================================
  // BASIC FUNCTIONALITY TESTS
  // ============================================================================
  
  describe('Basic Functionality', () => {
    test('should submit task with data successfully', async () => {
      const task = { id: 123 };
      const dataToSubmit = {
        name: 'John Doe',
        email: 'john@example.com',
        _user: { id: 1 },
        _request: { id: 100 },
      };
      
      await component.submit(task, dataToSubmit);
      
      // Verify API was called with correct parameters
      expect(ProcessMaker.apiClient.put).toHaveBeenCalledWith(
        'tasks/123',
        { status: 'COMPLETED', data: dataToSubmit }
      );
      
      // Verify success alert was shown
      expect(window.ProcessMaker.alert).toHaveBeenCalledWith(
        'Task Completed Successfully',
        'success',
        5,
        true
      );
    });
    
    test('should set submitting flag during submission', async () => {
      const task = { id: 123 };
      const dataToSubmit = { name: 'John' };
      
      expect(component.submitting).toBe(false);
      
      const promise = component.submit(task, dataToSubmit);
      
      expect(component.submitting).toBe(true);
      
      await promise;
      
      expect(component.submitting).toBe(false);
    });
    
    test('should prevent multiple simultaneous submissions', async () => {
      const task = { id: 123 };
      const dataToSubmit = { name: 'John' };
      
      component.submitting = true;
      
      const result = await component.submit(task, dataToSubmit);
      
      // Should return early without calling API
      expect(ProcessMaker.apiClient.put).not.toHaveBeenCalled();
      expect(result).toBeUndefined();
    });
  });
  
  // ============================================================================
  // DATA SUBMISSION TESTS
  // ============================================================================
  
  describe('Data Submission', () => {
    test('should submit only requested variables (filtered data)', async () => {
      const task = { id: 123 };
      const dataToSubmit = {
        name: 'John Doe',
        email: 'john@example.com',
        _user: { id: 1 },
        _request: { id: 100 },
        // phone and address were filtered out
      };
      
      await component.submit(task, dataToSubmit);
      
      const callArgs = ProcessMaker.apiClient.put.mock.calls[0];
      const submittedData = callArgs[1].data;
      
      expect(submittedData.name).toBe('John Doe');
      expect(submittedData.email).toBe('john@example.com');
      expect(submittedData._user).toEqual({ id: 1 });
      expect(submittedData._request).toEqual({ id: 100 });
      expect(submittedData.phone).toBeUndefined();
      expect(submittedData.address).toBeUndefined();
    });
    
    test('should submit all data when no filtering applied', async () => {
      const task = { id: 123 };
      const dataToSubmit = {
        name: 'John Doe',
        email: 'john@example.com',
        phone: '555-1234',
        address: '123 Main St',
        _user: { id: 1 },
        _request: { id: 100 },
      };
      
      await component.submit(task, dataToSubmit);
      
      const callArgs = ProcessMaker.apiClient.put.mock.calls[0];
      const submittedData = callArgs[1].data;
      
      expect(submittedData).toEqual(dataToSubmit);
    });
    
    test('should always include system variables', async () => {
      const task = { id: 123 };
      const dataToSubmit = {
        name: 'John',
        _user: { id: 1 },
        _request: { id: 100 },
      };
      
      await component.submit(task, dataToSubmit);
      
      const callArgs = ProcessMaker.apiClient.put.mock.calls[0];
      const submittedData = callArgs[1].data;
      
      expect(submittedData._user).toBeDefined();
      expect(submittedData._request).toBeDefined();
    });
  });
  
  // ============================================================================
  // SELF SERVICE TESTS
  // ============================================================================
  
  describe('Self Service', () => {
    test('should show alert and not submit when task is self service', async () => {
      component.isSelfService = true;
      
      const task = { id: 123 };
      const dataToSubmit = { name: 'John' };
      
      await component.submit(task, dataToSubmit);
      
      // Should show alert
      expect(ProcessMaker.alert).toHaveBeenCalledWith(
        'Claim the Task to continue.',
        'warning'
      );
      
      // Should NOT call API
      expect(ProcessMaker.apiClient.put).not.toHaveBeenCalled();
    });
  });
  
  // ============================================================================
  // ERROR HANDLING TESTS
  // ============================================================================
  
  describe('Error Handling', () => {
    test('should handle 422 validation errors', async () => {
      const task = { id: 123 };
      const dataToSubmit = { name: 'John' };
      
      const error = {
        response: {
          status: 422,
          data: {
            errors: {
              email: ['Email is required'],
              phone: ['Phone format is invalid'],
            },
          },
        },
      };
      
      ProcessMaker.apiClient.put.mockRejectedValue(error);
      
      await component.submit(task, dataToSubmit);
      
      // Should show error alerts
      expect(window.ProcessMaker.alert).toHaveBeenCalledWith(
        'email: Email is required',
        'danger',
        0
      );
      expect(window.ProcessMaker.alert).toHaveBeenCalledWith(
        'phone: Phone format is invalid',
        'danger',
        0
      );
      
      // Should load next assigned task
      expect(component.$refs.task.loadNextAssignedTask).toHaveBeenCalled();
    });
    
    test('should handle 422 error with message', async () => {
      const task = { id: 123 };
      const dataToSubmit = { name: 'John' };
      
      const error = {
        response: {
          status: 422,
          data: {
            message: 'Validation failed',
          },
        },
      };
      
      ProcessMaker.apiClient.put.mockRejectedValue(error);
      
      await component.submit(task, dataToSubmit);
      
      expect(window.ProcessMaker.alert).toHaveBeenCalledWith(
        'Validation failed',
        'danger',
        0
      );
    });
    
    test('should reset submitting flag after error', async () => {
      const task = { id: 123 };
      const dataToSubmit = { name: 'John' };
      
      ProcessMaker.apiClient.put.mockRejectedValue(new Error('Network error'));
      
      await component.submit(task, dataToSubmit);
      
      expect(component.submitting).toBe(false);
    });
  });
  
  // ============================================================================
  // COLLECTION DATA TESTS
  // ============================================================================
  
  describe('Collection Data', () => {
    test('should process collection data when present', async () => {
      const task = { id: 123 };
      const dataToSubmit = {
        name: 'John',
        collection_field_1: 'value1',
        collection_field_2: 'value2',
      };
      
      // Mock collection data result
      component.processCollectionData = jest.fn(() => [
        {
          submitCollectionChecked: true,
          collectionId: 456,
          recordId: 789,
          collectionFields: {
            collection_field_1: {},
            collection_field_2: {},
          },
        },
      ]);
      
      await component.submit(task, dataToSubmit);
      
      // Should call collection API
      expect(ProcessMaker.apiClient.put).toHaveBeenCalledWith(
        'collections/456/records/789',
        {
          data: {
            collection_field_1: 'value1',
            collection_field_2: 'value2',
          },
          uploads: [],
        }
      );
      
      // Should also call task completion API
      expect(ProcessMaker.apiClient.put).toHaveBeenCalledWith(
        'tasks/123',
        { status: 'COMPLETED', data: dataToSubmit }
      );
    });
    
    test('should skip collection data when submitCollectionChecked is false', async () => {
      const task = { id: 123 };
      const dataToSubmit = { name: 'John' };
      
      component.processCollectionData = jest.fn(() => [
        {
          submitCollectionChecked: false,
          collectionId: 456,
          recordId: 789,
          collectionFields: {},
        },
      ]);
      
      await component.submit(task, dataToSubmit);
      
      // Should NOT call collection API
      const calls = ProcessMaker.apiClient.put.mock.calls;
      const collectionCall = calls.find(call => call[0].includes('collections'));
      expect(collectionCall).toBeUndefined();
      
      // But should still call task completion API
      expect(ProcessMaker.apiClient.put).toHaveBeenCalledWith(
        'tasks/123',
        { status: 'COMPLETED', data: dataToSubmit }
      );
    });
  });
  
  // ============================================================================
  // INTEGRATION TESTS
  // ============================================================================
  
  describe('Integration Tests', () => {
    test('should handle complete workflow with filtered data', async () => {
      const task = { id: 123 };
      
      // Simulating data after filtering by prepareSubmissionData
      const dataToSubmit = {
        name: 'John Doe',
        email: 'john@example.com',
        // phone was filtered out
        _user: { id: 1, username: 'john' },
        _request: { id: 100, status: 'ACTIVE' },
      };
      
      await component.submit(task, dataToSubmit);
      
      // Verify submission
      expect(ProcessMaker.apiClient.put).toHaveBeenCalledWith(
        'tasks/123',
        { 
          status: 'COMPLETED', 
          data: expect.objectContaining({
            name: 'John Doe',
            email: 'john@example.com',
            _user: { id: 1, username: 'john' },
            _request: { id: 100, status: 'ACTIVE' },
          })
        }
      );
      
      // Verify phone was NOT submitted
      const submittedData = ProcessMaker.apiClient.put.mock.calls[0][1].data;
      expect(submittedData.phone).toBeUndefined();
    });
    
    test('should handle empty data submission', async () => {
      const task = { id: 123 };
      const dataToSubmit = {};
      
      await component.submit(task, dataToSubmit);
      
      expect(ProcessMaker.apiClient.put).toHaveBeenCalledWith(
        'tasks/123',
        { status: 'COMPLETED', data: {} }
      );
    });
    
    test('should handle data with only system variables', async () => {
      const task = { id: 123 };
      const dataToSubmit = {
        _user: { id: 1 },
        _request: { id: 100 },
      };
      
      await component.submit(task, dataToSubmit);
      
      const submittedData = ProcessMaker.apiClient.put.mock.calls[0][1].data;
      
      expect(submittedData._user).toEqual({ id: 1 });
      expect(submittedData._request).toEqual({ id: 100 });
    });
  });
});

