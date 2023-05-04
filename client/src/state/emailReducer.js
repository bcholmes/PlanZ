import { SET_EMAIL_OPTIONS } from "./emailActions";

const initialState = {
    loading: true,
    emailCC: [],
    emailTo: [],
    emailFrom: []
}

const emailReducer = (state = initialState, action) => {
    switch (action.type) {
        case SET_EMAIL_OPTIONS:
            return {
                ...state,
                loading: false,
                emailTo: action.payload.emailTo ?? [],
                emailFrom: action.payload.emailFrom ?? [],
                emailCC: action.payload.emailCC ?? []
            }
        default:
            return state;
    }
}

export default emailReducer;