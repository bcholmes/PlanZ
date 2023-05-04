import { createStore, combineReducers } from 'redux'
import brainstormReducer from './brainstormReducer';
import moduleReducer from './moduleReducer';
import volunteerReducer from './volunteerReducer';
import assignmentsReducer from './assignmentsReducer';
import emailReducer from './emailReducer';



const reducer = combineReducers({
    modules: moduleReducer,
    volunteering: volunteerReducer,
    assignments: assignmentsReducer,
    brainstorm: brainstormReducer,
    email: emailReducer
})
const store = createStore(reducer);

export default store;