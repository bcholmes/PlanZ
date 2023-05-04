import authAxios from "../common/authAxios";
import toast from 'react-hot-toast';
import { setEmailOptions } from "./emailActions";
import store from "./store";

export function fetchEmailSettings() {
    authAxios.get('/api/email/get_email_settings.php')
        .then(res => {
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