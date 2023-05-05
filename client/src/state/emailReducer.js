import { SET_EMAIL_OPTIONS, SET_EMAIL_VALUES } from "./emailActions";

const initialState = {
    loading: true,
    emailCC: [],
    emailTo: [],
    emailFrom: [],
    emailValues: {}
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
        case SET_EMAIL_VALUES:
            return {
                ...state,
                emailValues: action.payload
            }
        default:
            return state;
    }
}

export default emailReducer;