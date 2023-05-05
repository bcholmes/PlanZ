import authAxios from "../common/authAxios";
import toast from 'react-hot-toast';
import { setEmailOptions } from "./emailActions";
import store from "./store";

export function fetchEmailSettings(onLoad) {
    authAxios.get('/api/email/get_email_settings.php')
        .then(res => {
            if (onLoad) {
                onLoad(res.data.emailTo, res.data.emailFrom, res.data.emailCC);
            }
            store.dispatch(setEmailOptions(
                res.data.emailTo,
                res.data.emailFrom,
                res.data.emailCC
            ));
        })
        .catch(error => {
            toast("There was an error accessing the email settings", { className: "bg-danger text-white" });
        }
    );
}


export function sendEmails(onComplete) {
    authAxios.post('/api/email/send_emails.php', store.getState().email.emailValues)
        .then(res => {
            if (onComplete) {
                onComplete(true);
            }
        })
        .catch(error => {
            onComplete(false);
            toast("There was an error trying to send the emails. Try again later?", { className: "bg-danger text-white" });
        }
    );
}