import store from './store';
import { setAllShiftAssignements, setShiftAssignements, setVolunteerJobs, setVolunteerShifts } from './volunteerActions';
import authAxios from '../common/authAxios';

export function fetchJobs() {
    authAxios.get('/api/volunteer/get_volunteer_jobs.php')
        .then(res => {
            store.dispatch(setVolunteerJobs(res.data));
        })
        .catch(error => {
            let message = "The list of jobs could not be downloaded."
            store.dispatch(setVolunteerJobs({}, message));
        }
    );
}

export function fetchMyShiftAssignments() {
    authAxios.get('/api/volunteer/my_shift_assignments.php')
        .then(res => {
            store.dispatch(setShiftAssignements(res.data));
        })
        .catch(error => {
            let message = "The list of assignments could not be downloaded."
            store.dispatch(setShiftAssignements({}, message));
        }
    );
}

export function fetchAllShiftAssignments() {
    authAxios.get('/api/volunteer/get_all_volunteer_signups.php')
        .then(res => {
            store.dispatch(setAllShiftAssignements(res.data));
        })
        .catch(error => {
            let message = "The list of assignments could not be downloaded."
            store.dispatch(setAllShiftAssignements({}, message));
        }
    );
}
export function fetchShifts() {
    authAxios.get('/api/volunteer/get_volunteer_shifts.php')
        .then(res => {
            store.dispatch(setVolunteerShifts(res.data));
        })
        .catch(error => {
            let message = "The list of shifts could not be downloaded."
            store.dispatch(setVolunteerShifts({}, message));
        }
    );
}
