/**
 * This file implements a "shared" vue instance that represents a more global event bus if needed for larger scope component communication in the 
 * designer application
 */
import Vue from 'vue';

// Create our Vue instance which will work as an emitter and listen source
const EventBus = new Vue();

// Export our Vue instance as the EventBus representation
export default EventBus;