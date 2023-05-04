export const SET_EMAIL_OPTIONS = 'SET_EMAIL_OPTIONS';

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