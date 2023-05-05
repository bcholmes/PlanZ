export const SET_EMAIL_OPTIONS = 'SET_EMAIL_OPTIONS';
export const SET_EMAIL_VALUES = 'SET_EMAIL_VALUES';

export function setEmailOptions(emailTo, emailFrom, emailCC) {
    let payload = {
        emailTo: emailTo,
        emailFrom: emailFrom,
        emailCC: emailCC
    }
    return {
        type: SET_EMAIL_OPTIONS,
        payload
    }
}

export function setEmailValues(email) {
    let payload = email
    return {
        type: SET_EMAIL_VALUES,
        payload
    }
}